<?php

declare(strict_types=1);

namespace Tests\Abderrahim\SyliusWorkflowPlugin\Graph;

use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowCampaign;
use Abderrahim\SyliusWorkflowPlugin\Entity\WorkflowRun;
use Abderrahim\SyliusWorkflowPlugin\Enum\WorkflowStatus;
use Abderrahim\SyliusWorkflowPlugin\Graph\Action\ActionInterface;
use Abderrahim\SyliusWorkflowPlugin\Graph\Rule\RuleInterface;
use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowExecutor;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class WorkflowExecutorTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private MessageBusInterface&MockObject $messageBus;
    private WorkflowExecutor $executor;

    /** @var RuleInterface&MockObject */
    private RuleInterface&MockObject $orderTotalRule;

    /** @var ActionInterface&MockObject */
    private ActionInterface&MockObject $sendEmailAction;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            // Simulate ID assignment on persist for WorkflowRun
            if ($entity instanceof WorkflowRun && $entity->getId() === null) {
                $ref = new \ReflectionProperty(WorkflowRun::class, 'id');
                $ref->setValue($entity, random_int(1, 99999));
            }
        });
        $this->entityManager->method('flush')->willReturn(null);

        $this->messageBus = $this->createMock(MessageBusInterface::class);

        $this->orderTotalRule = $this->createMock(RuleInterface::class);
        $this->orderTotalRule->method('supports')->willReturnCallback(fn (string $rule) => $rule === 'order_total');

        $this->sendEmailAction = $this->createMock(ActionInterface::class);
        $this->sendEmailAction->method('supports')->willReturnCallback(fn (string $type) => $type === 'send_email');

        $this->executor = new WorkflowExecutor(
            rules: [$this->orderTotalRule],
            actions: [$this->sendEmailAction],
            messageBus: $this->messageBus,
            entityManager: $this->entityManager,
            logger: new NullLogger(),
        );
    }

    public function testExecuteSimpleWorkflow(): void
    {
        $campaign = $this->createCampaign([
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email', 'template' => 'test.html.twig']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
            ],
        ]);

        $subject = $this->createSubject();
        $context = new WorkflowContext('order.completed', $subject, 'default');

        $this->sendEmailAction->expects(self::once())
            ->method('execute')
            ->willReturn(['success' => true, 'message' => 'Email sent.']);

        $run = $this->executor->execute($campaign, $context);

        self::assertSame(WorkflowRun::STATUS_COMPLETED, $run->getStatus());
        self::assertNotEmpty($run->getExecutionLog());
    }

    public function testConditionPassesContinuesExecution(): void
    {
        $campaign = $this->createCampaign([
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
                ['id' => 'condition-1', 'type' => 'condition', 'config' => ['rule' => 'order_total', 'operator' => 'gt', 'value' => '5000']],
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'condition-1'],
                ['source' => 'condition-1', 'target' => 'action-1'],
            ],
        ]);

        $subject = $this->createSubject();
        $context = new WorkflowContext('order.completed', $subject, 'default');

        $this->orderTotalRule->expects(self::once())
            ->method('evaluate')
            ->with('gt', '5000', $context)
            ->willReturn(true);

        $this->sendEmailAction->expects(self::once())
            ->method('execute')
            ->willReturn(['success' => true, 'message' => 'Email sent.']);

        $run = $this->executor->execute($campaign, $context);

        self::assertSame(WorkflowRun::STATUS_COMPLETED, $run->getStatus());
    }

    public function testConditionFailsStopsExecution(): void
    {
        $campaign = $this->createCampaign([
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
                ['id' => 'condition-1', 'type' => 'condition', 'config' => ['rule' => 'order_total', 'operator' => 'gt', 'value' => '5000']],
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'condition-1'],
                ['source' => 'condition-1', 'target' => 'action-1'],
            ],
        ]);

        $subject = $this->createSubject();
        $context = new WorkflowContext('order.completed', $subject, 'default');

        $this->orderTotalRule->expects(self::once())
            ->method('evaluate')
            ->willReturn(false);

        $this->sendEmailAction->expects(self::never())
            ->method('execute');

        $run = $this->executor->execute($campaign, $context);

        self::assertSame(WorkflowRun::STATUS_COMPLETED, $run->getStatus());
    }

    public function testTriggerEventMismatchSkips(): void
    {
        $campaign = $this->createCampaign([
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
            ],
        ]);

        $subject = $this->createSubject();
        $context = new WorkflowContext('order.cancelled', $subject, 'default');

        $this->sendEmailAction->expects(self::never())
            ->method('execute');

        $run = $this->executor->execute($campaign, $context);

        self::assertSame(WorkflowRun::STATUS_COMPLETED, $run->getStatus());
    }

    public function testDelayNodeDispatchesMessageAndStops(): void
    {
        $campaign = $this->createCampaign([
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
                ['id' => 'delay-1', 'type' => 'delay', 'config' => ['amount' => 2, 'unit' => 'hours']],
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'delay-1'],
                ['source' => 'delay-1', 'target' => 'action-1'],
            ],
        ]);

        $subject = $this->createSubject();
        $context = new WorkflowContext('order.completed', $subject, 'default');

        $this->messageBus->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(fn (Envelope $envelope) => $envelope);

        $this->sendEmailAction->expects(self::never())
            ->method('execute');

        $run = $this->executor->execute($campaign, $context);

        // Run should still be running (waiting for delay)
        self::assertSame(WorkflowRun::STATUS_RUNNING, $run->getStatus());
    }

    public function testNoTriggerNodeFailsRun(): void
    {
        $campaign = $this->createCampaign([
            'nodes' => [
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email']],
            ],
            'edges' => [],
        ]);

        $subject = $this->createSubject();
        $context = new WorkflowContext('order.completed', $subject, 'default');

        $run = $this->executor->execute($campaign, $context);

        self::assertSame(WorkflowRun::STATUS_FAILED, $run->getStatus());
        self::assertStringContainsString('No trigger node', $run->getErrorMessage());
    }

    public function testActionFailureContinuesWorkflow(): void
    {
        $campaign = $this->createCampaign([
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
            ],
        ]);

        $subject = $this->createSubject();
        $context = new WorkflowContext('order.completed', $subject, 'default');

        $this->sendEmailAction->expects(self::once())
            ->method('execute')
            ->willReturn(['success' => false, 'message' => 'Template not found.']);

        $run = $this->executor->execute($campaign, $context);

        // Workflow completes even when action fails (graceful degradation)
        self::assertSame(WorkflowRun::STATUS_COMPLETED, $run->getStatus());

        $log = $run->getExecutionLog();
        $actionLog = array_filter($log, fn (array $entry) => $entry['nodeId'] === 'action-1');
        $actionEntry = array_values($actionLog)[0];
        self::assertSame('failed', $actionEntry['status']);
    }

    private function createCampaign(array $graph): WorkflowCampaign
    {
        static $nextId = 1;
        $campaign = new WorkflowCampaign();

        // Set ID via reflection since entity is not persisted in tests
        $ref = new \ReflectionProperty(WorkflowCampaign::class, 'id');
        $ref->setValue($campaign, $nextId++);

        $campaign->setName('Test Campaign');
        $campaign->setEnabled(true);
        $campaign->setStatus(WorkflowStatus::Active);
        $campaign->setGraph($graph);

        return $campaign;
    }

    private function createSubject(): object
    {
        return new class {
            public function getId(): int
            {
                return 42;
            }

            public function getTotal(): int
            {
                return 10000;
            }
        };
    }
}
