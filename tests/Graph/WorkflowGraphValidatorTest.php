<?php

declare(strict_types=1);

namespace Tests\Abderrahim\SyliusWorkflowPlugin\Graph;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowGraphValidator;
use PHPUnit\Framework\TestCase;

final class WorkflowGraphValidatorTest extends TestCase
{
    private WorkflowGraphValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new WorkflowGraphValidator();
    }

    public function testValidGraphReturnsNoErrors(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
                ['id' => 'condition-1', 'type' => 'condition', 'config' => ['rule' => 'order_total', 'operator' => 'gt', 'value' => '5000']],
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email', 'template' => 'test.html.twig']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'condition-1'],
                ['source' => 'condition-1', 'target' => 'action-1'],
            ],
        ];

        $errors = $this->validator->validate($graph);

        self::assertEmpty($errors);
    }

    public function testEmptyGraphReturnsError(): void
    {
        $errors = $this->validator->validate(['nodes' => [], 'edges' => []]);

        self::assertNotEmpty($errors);
        self::assertStringContainsString('at least one node', $errors[0]);
    }

    public function testMissingTriggerNodeReturnsError(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email']],
            ],
            'edges' => [],
        ];

        $errors = $this->validator->validate($graph);

        self::assertNotEmpty($errors);
        self::assertStringContainsString('exactly one trigger node', $errors[0]);
    }

    public function testMultipleTriggerNodesReturnsError(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
                ['id' => 'trigger-2', 'type' => 'trigger', 'config' => ['event' => 'order.cancelled']],
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
                ['source' => 'trigger-2', 'target' => 'action-1'],
            ],
        ];

        $errors = $this->validator->validate($graph);

        self::assertNotEmpty($errors);
        $triggerErrors = array_filter($errors, fn (string $e) => str_contains($e, 'exactly one trigger'));
        self::assertNotEmpty($triggerErrors);
    }

    public function testEdgesWithInvalidNodeIdsReturnErrors(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'nonexistent-node'],
            ],
        ];

        $errors = $this->validator->validate($graph);

        self::assertNotEmpty($errors);
        $edgeErrors = array_filter($errors, fn (string $e) => str_contains($e, 'does not match'));
        self::assertNotEmpty($edgeErrors);
    }

    public function testCycleDetectionReturnsError(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email']],
                ['id' => 'action-2', 'type' => 'action', 'config' => ['type' => 'send_webhook']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
                ['source' => 'action-1', 'target' => 'action-2'],
                ['source' => 'action-2', 'target' => 'action-1'], // cycle
            ],
        ];

        $errors = $this->validator->validate($graph);

        $cycleErrors = array_filter($errors, fn (string $e) => str_contains($e, 'cycle'));
        self::assertNotEmpty($cycleErrors);
    }

    public function testDanglingNodeReturnsError(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email']],
                ['id' => 'action-2', 'type' => 'action', 'config' => ['type' => 'send_webhook']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'action-1'],
                // action-2 has no incoming edge — dangling
            ],
        ];

        $errors = $this->validator->validate($graph);

        $danglingErrors = array_filter($errors, fn (string $e) => str_contains($e, 'dangling') || str_contains($e, 'no incoming'));
        self::assertNotEmpty($danglingErrors);
    }

    public function testGraphWithDelayNodeIsValid(): void
    {
        $graph = [
            'nodes' => [
                ['id' => 'trigger-1', 'type' => 'trigger', 'config' => ['event' => 'order.completed']],
                ['id' => 'delay-1', 'type' => 'delay', 'config' => ['amount' => 1, 'unit' => 'hours']],
                ['id' => 'action-1', 'type' => 'action', 'config' => ['type' => 'send_email']],
            ],
            'edges' => [
                ['source' => 'trigger-1', 'target' => 'delay-1'],
                ['source' => 'delay-1', 'target' => 'action-1'],
            ],
        ];

        $errors = $this->validator->validate($graph);

        self::assertEmpty($errors);
    }
}
