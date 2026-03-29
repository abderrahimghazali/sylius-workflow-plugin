<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Enum\NodeType;
use Abderrahim\SyliusWorkflowPlugin\Graph\Node\ActionNode;
use Abderrahim\SyliusWorkflowPlugin\Graph\Node\ConditionNode;
use Abderrahim\SyliusWorkflowPlugin\Graph\Node\DelayNode;
use Abderrahim\SyliusWorkflowPlugin\Graph\Node\TriggerNode;
use Abderrahim\SyliusWorkflowPlugin\Graph\Rule\RuleInterface;

final class DryRunWorkflowExecutor
{
    /** @var iterable<RuleInterface> */
    private iterable $rules;

    public function __construct(iterable $rules)
    {
        $this->rules = $rules;
    }

    /**
     * @return array{path: string[], results: array<string, array{status: string, message: string}>}
     */
    public function execute(WorkflowCampaign $campaign, WorkflowContext $context): array
    {
        $graph = $campaign->getGraph();
        $nodes = $graph['nodes'] ?? [];
        $edges = $graph['edges'] ?? [];

        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeMap[$node['id']] = $node;
        }

        $adjacency = [];
        foreach ($edges as $edge) {
            $adjacency[$edge['source']][] = $edge['target'];
        }

        $startNodeId = null;
        foreach ($nodes as $node) {
            if (($node['type'] ?? '') === NodeType::Trigger->value) {
                $startNodeId = $node['id'];
                break;
            }
        }

        if ($startNodeId === null) {
            return ['path' => [], 'results' => []];
        }

        $path = [];
        $results = [];
        $currentNodeId = $startNodeId;

        while ($currentNodeId !== null) {
            if (!isset($nodeMap[$currentNodeId])) {
                break;
            }

            $path[] = $currentNodeId;
            $nodeData = $nodeMap[$currentNodeId];
            $nodeType = NodeType::from($nodeData['type']);

            $result = match ($nodeType) {
                NodeType::Trigger => $this->processTrigger($nodeData, $context),
                NodeType::Condition => $this->processCondition($nodeData, $context),
                NodeType::Action => $this->processAction($nodeData),
                NodeType::Delay => $this->processDelay($nodeData),
            };

            $results[$currentNodeId] = $result;

            if ($result['status'] === 'skipped') {
                // Check for "otherwise" branch on conditions
                if ($nodeType === NodeType::Condition) {
                    $falseNext = $this->getNextNodeId($currentNodeId, $edges, 'exit-false');
                    if ($falseNext !== null) {
                        $currentNodeId = $falseNext;
                        continue;
                    }
                }
                break;
            }

            if ($nodeType === NodeType::Condition) {
                $currentNodeId = $this->getNextNodeId($currentNodeId, $edges, 'exit-true')
                    ?? ($adjacency[$currentNodeId][0] ?? null);
            } else {
                $nextNodes = $adjacency[$currentNodeId] ?? [];
                $currentNodeId = !empty($nextNodes) ? $nextNodes[0] : null;
            }
        }

        return ['path' => $path, 'results' => $results];
    }

    private function getNextNodeId(string $currentNodeId, array $edges, ?string $sourceHandle): ?string
    {
        foreach ($edges as $edge) {
            if (($edge['source'] ?? '') !== $currentNodeId) {
                continue;
            }
            $edgeHandle = $edge['sourceHandle'] ?? null;
            if ($sourceHandle !== null && $edgeHandle === $sourceHandle) {
                return $edge['target'] ?? null;
            }
            if ($sourceHandle !== null && $edgeHandle === null) {
                return $edge['target'] ?? null;
            }
        }
        return null;
    }

    /**
     * @return array{status: string, message: string}
     */
    private function processTrigger(array $nodeData, WorkflowContext $context): array
    {
        $triggerNode = TriggerNode::fromArray($nodeData);

        if ($triggerNode->getEvent() !== $context->getEvent()) {
            return ['status' => 'skipped', 'message' => 'Event mismatch: expected ' . $triggerNode->getEvent()];
        }

        return ['status' => 'passed', 'message' => 'Trigger matched: ' . $triggerNode->getEvent()];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function processCondition(array $nodeData, WorkflowContext $context): array
    {
        $conditionNode = ConditionNode::fromArray($nodeData);

        foreach ($this->rules as $rule) {
            if ($rule->supports($conditionNode->getRule())) {
                $result = $rule->evaluate($conditionNode->getOperator(), $conditionNode->getValue(), $context);

                if ($result) {
                    return ['status' => 'passed', 'message' => sprintf('Condition "%s" passed', $conditionNode->getRule())];
                }

                return ['status' => 'skipped', 'message' => sprintf('Condition "%s" not met', $conditionNode->getRule())];
            }
        }

        return ['status' => 'skipped', 'message' => sprintf('No evaluator for rule "%s"', $conditionNode->getRule())];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function processAction(array $nodeData): array
    {
        $actionNode = ActionNode::fromArray($nodeData);

        return ['status' => 'dry_run', 'message' => sprintf('Would execute: %s', $actionNode->getActionType())];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function processDelay(array $nodeData): array
    {
        $delayNode = DelayNode::fromArray($nodeData);

        return ['status' => 'dry_run', 'message' => sprintf('Would wait %d %s', $delayNode->getAmount(), $delayNode->getUnit())];
    }
}
