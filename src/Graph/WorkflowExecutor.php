<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowRun;
use Abderrahim\SyliusWorkflowPlugin\Enum\NodeType;
use Abderrahim\SyliusWorkflowPlugin\Graph\Action\ActionInterface;
use Abderrahim\SyliusWorkflowPlugin\Graph\Node\ActionNode;
use Abderrahim\SyliusWorkflowPlugin\Graph\Node\ConditionNode;
use Abderrahim\SyliusWorkflowPlugin\Graph\Node\DelayNode;
use Abderrahim\SyliusWorkflowPlugin\Graph\Node\TriggerNode;
use Abderrahim\SyliusWorkflowPlugin\Graph\Rule\RuleInterface;
use Abderrahim\SyliusWorkflowPlugin\Messenger\DelayedWorkflowMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

final class WorkflowExecutor
{
    /** @var iterable<RuleInterface> */
    private iterable $rules;

    /** @var iterable<ActionInterface> */
    private iterable $actions;

    public function __construct(
        iterable $rules,
        iterable $actions,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        $this->rules = $rules;
        $this->actions = $actions;
    }

    public function execute(WorkflowCampaign $campaign, WorkflowContext $context, ?WorkflowRun $existingRun = null, ?string $resumeFromNodeId = null): WorkflowRun
    {
        $graph = $campaign->getGraph();
        $nodes = $graph['nodes'] ?? [];
        $edges = $graph['edges'] ?? [];

        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeMap[$node['id']] = $node;
        }

        $adjacency = $this->buildAdjacency($edges);

        // Create or reuse run
        $run = $existingRun ?? $this->createRun($campaign, $context);

        // Determine start node
        $startNodeId = $resumeFromNodeId ?? $this->findTriggerNodeId($nodes);
        if ($startNodeId === null) {
            $run->markFailed('No trigger node found.');
            $this->entityManager->flush();
            return $run;
        }

        // Store campaign ID and subject ID in context for rules
        if ($campaign->getId() !== null) {
            $context->set('campaign_id', $campaign->getId());
        }
        $subject = $context->getSubject();
        if (method_exists($subject, 'getId') && $subject->getId() !== null) {
            $context->set('subject_id', $subject->getId());
        }
        $context->set('workflow_name', $campaign->getName());

        // Walk the graph using a queue (supports parallel branches)
        $queue = [$startNodeId];
        $visited = [];
        $stepCount = 0;
        $maxSteps = 200;
        $hasDelay = false;

        while (!empty($queue)) {
            $currentNodeId = array_shift($queue);

            // Skip already visited nodes (prevents re-execution in diamond patterns)
            if (isset($visited[$currentNodeId])) {
                continue;
            }
            $visited[$currentNodeId] = true;

            if (++$stepCount > $maxSteps) {
                $run->markFailed('Execution exceeded maximum step limit (' . $maxSteps . ').');
                $this->entityManager->flush();
                return $run;
            }

            $run->setCurrentNodeId($currentNodeId);

            if (!isset($nodeMap[$currentNodeId])) {
                $run->addLogEntry($currentNodeId, 'failed', 'Node not found in graph.');
                continue;
            }

            $nodeData = $nodeMap[$currentNodeId];
            $nodeType = NodeType::from($nodeData['type']);

            $result = $this->processNode($nodeType, $nodeData, $context, $run, $campaign, $adjacency, $currentNodeId);

            if ($result === null) {
                // Delay node dispatched a message — stop this branch
                $hasDelay = true;
                continue;
            }

            if ($result === false) {
                // Condition failed — check for "otherwise" branch
                if ($nodeType === NodeType::Condition) {
                    $falseNext = $this->getNextNodeId($currentNodeId, $edges, 'exit-false');
                    if ($falseNext !== null) {
                        $queue[] = $falseNext;
                    }
                }
                // This branch stops here — other branches in the queue continue
                continue;
            }

            // Enqueue next nodes — for conditions, follow "then" branch
            if ($nodeType === NodeType::Condition) {
                $trueNext = $this->getNextNodeId($currentNodeId, $edges, 'exit-true')
                    ?? $this->getNextNodeId($currentNodeId, $edges, null);
                if ($trueNext !== null) {
                    $queue[] = $trueNext;
                }
            } else {
                // Enqueue ALL outgoing edges (supports parallel branches)
                foreach ($adjacency[$currentNodeId] ?? [] as $nextNodeId) {
                    $queue[] = $nextNodeId;
                }
            }
        }

        if ($hasDelay) {
            // At least one branch hit a delay — run stays running
            $this->entityManager->flush();
            return $run;
        }

        $run->markCompleted();
        $campaign->incrementRunCount();
        $campaign->setLastRunAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $run;
    }

    /**
     * @return bool|null true = continue, false = stop, null = delayed
     */
    private function processNode(
        NodeType $nodeType,
        array $nodeData,
        WorkflowContext $context,
        WorkflowRun $run,
        WorkflowCampaign $campaign,
        array $adjacency,
        string $currentNodeId,
    ): ?bool {
        return match ($nodeType) {
            NodeType::Trigger => $this->processTriggerNode($nodeData, $context, $run),
            NodeType::Condition => $this->processConditionNode($nodeData, $context, $run),
            NodeType::Action => $this->processActionNode($nodeData, $context, $run),
            NodeType::Delay => $this->processDelayNode($nodeData, $run, $campaign, $context, $adjacency, $currentNodeId),
        };
    }

    private function processTriggerNode(array $nodeData, WorkflowContext $context, WorkflowRun $run): bool
    {
        $triggerNode = TriggerNode::fromArray($nodeData);

        if ($triggerNode->getEvent() !== $context->getEvent()) {
            $run->addLogEntry($triggerNode->getId(), 'skipped', 'Event mismatch.');
            return false;
        }

        $run->addLogEntry($triggerNode->getId(), 'passed', 'Trigger matched.');
        return true;
    }

    private function processConditionNode(array $nodeData, WorkflowContext $context, WorkflowRun $run): bool
    {
        $conditionNode = ConditionNode::fromArray($nodeData);

        foreach ($this->rules as $rule) {
            if ($rule->supports($conditionNode->getRule())) {
                $result = $rule->evaluate($conditionNode->getOperator(), $conditionNode->getValue(), $context);

                if ($result) {
                    $run->addLogEntry($conditionNode->getId(), 'passed', sprintf('Condition "%s" passed.', $conditionNode->getRule()));
                    return true;
                }

                $run->addLogEntry($conditionNode->getId(), 'skipped', sprintf('Condition "%s" not met.', $conditionNode->getRule()));
                return false;
            }
        }

        $run->addLogEntry($conditionNode->getId(), 'failed', sprintf('No rule evaluator found for "%s".', $conditionNode->getRule()));
        $this->logger->warning('No rule evaluator found for rule: ' . $conditionNode->getRule());

        return false;
    }

    private function processActionNode(array $nodeData, WorkflowContext $context, WorkflowRun $run): bool
    {
        $actionNode = ActionNode::fromArray($nodeData);

        foreach ($this->actions as $action) {
            if ($action->supports($actionNode->getActionType())) {
                $result = $action->execute($actionNode->getConfig(), $context);

                $status = $result['success'] ? 'completed' : 'failed';
                $run->addLogEntry($actionNode->getId(), $status, $result['message']);

                // Continue workflow even if action fails (graceful degradation)
                return true;
            }
        }

        $run->addLogEntry($actionNode->getId(), 'failed', sprintf('No action executor found for "%s".', $actionNode->getActionType()));
        $this->logger->warning('No action executor found for type: ' . $actionNode->getActionType());

        return true;
    }

    private function processDelayNode(
        array $nodeData,
        WorkflowRun $run,
        WorkflowCampaign $campaign,
        WorkflowContext $context,
        array $adjacency,
        string $currentNodeId,
    ): ?bool {
        $delayNode = DelayNode::fromArray($nodeData);
        $delaySeconds = $delayNode->getDelayInSeconds();

        // Find the next node after the delay
        $nextNodes = $adjacency[$currentNodeId] ?? [];
        if (empty($nextNodes)) {
            $run->addLogEntry($delayNode->getId(), 'completed', 'Delay node has no next node. Workflow complete.');
            return false;
        }

        $resumeNodeId = $nextNodes[0];

        $run->addLogEntry($delayNode->getId(), 'delayed', sprintf(
            'Delaying %d %s. Will resume from node "%s".',
            $delayNode->getAmount(),
            $delayNode->getUnit(),
            $resumeNodeId,
        ));

        $campaignId = $campaign->getId();
        $runId = $run->getId();
        if ($campaignId === null || $runId === null) {
            $run->addLogEntry($delayNode->getId(), 'failed', 'Cannot delay: campaign or run not yet persisted.');
            return false;
        }

        $subject = $context->getSubject();
        $message = new DelayedWorkflowMessage(
            campaignId: $campaignId,
            runId: $runId,
            resumeFromNodeId: $resumeNodeId,
            event: $context->getEvent(),
            subjectType: $this->resolveSubjectType($subject),
            subjectId: method_exists($subject, 'getId') ? (int) $subject->getId() : 0,
            channel: $context->getChannel(),
        );

        $this->messageBus->dispatch(
            new Envelope($message, [new DelayStamp($delaySeconds * 1000)])
        );

        return null; // Signal to stop current execution
    }

    private function createRun(WorkflowCampaign $campaign, WorkflowContext $context): WorkflowRun
    {
        $run = new WorkflowRun();
        $run->setCampaign($campaign);

        $subject = $context->getSubject();
        $run->setSubjectType($this->resolveSubjectType($subject));
        $run->setSubjectId(method_exists($subject, 'getId') ? (int) $subject->getId() : 0);

        $this->entityManager->persist($run);

        return $run;
    }

    private function resolveSubjectType(object $subject): string
    {
        $className = \get_class($subject);

        if (str_contains($className, 'Order')) {
            return 'order';
        }
        if (str_contains($className, 'Customer')) {
            return 'customer';
        }
        if (str_contains($className, 'Cart')) {
            return 'cart';
        }

        return 'unknown';
    }

    private function findTriggerNodeId(array $nodes): ?string
    {
        foreach ($nodes as $node) {
            if (($node['type'] ?? '') === NodeType::Trigger->value) {
                return $node['id'];
            }
        }

        return null;
    }

    private function getNextNodeId(string $currentNodeId, array $edges, ?string $sourceHandle = null): ?string
    {
        foreach ($edges as $edge) {
            if (($edge['source'] ?? '') !== $currentNodeId) {
                continue;
            }
            $edgeHandle = $edge['sourceHandle'] ?? null;

            // Both null — legacy linear edge
            if ($sourceHandle === null && $edgeHandle === null) {
                return $edge['target'] ?? null;
            }

            // Exact handle match
            if ($sourceHandle !== null && $edgeHandle === $sourceHandle) {
                return $edge['target'] ?? null;
            }
        }
        return null;
    }

    private function buildAdjacency(array $edges): array
    {
        $adjacency = [];
        foreach ($edges as $edge) {
            $source = $edge['source'] ?? '';
            $target = $edge['target'] ?? '';
            if ($source !== '' && $target !== '') {
                $adjacency[$source][] = $target;
            }
        }

        return $adjacency;
    }
}
