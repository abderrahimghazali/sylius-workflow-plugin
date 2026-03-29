<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Template;

final class AbandonedCartRecovery implements WorkflowTemplateInterface
{
    public function getName(): string
    {
        return 'Abandoned Cart Recovery';
    }

    public function getDescription(): string
    {
        return 'Send a reminder email 1 hour after a cart is abandoned to recover lost sales.';
    }

    public function getCategory(): string
    {
        return 'abandoned_cart';
    }

    public function getThumbnailIcon(): string
    {
        return '🛒';
    }

    public function getGraph(): array
    {
        return [
            'nodes' => [
                ['id' => 'node-1', 'type' => 'trigger', 'position' => ['x' => 300, 'y' => 40], 'config' => ['event' => 'cart.abandoned']],
                ['id' => 'node-2', 'type' => 'delay', 'position' => ['x' => 300, 'y' => 200], 'config' => ['amount' => 1, 'unit' => 'hours']],
                ['id' => 'node-3', 'type' => 'action', 'position' => ['x' => 300, 'y' => 360], 'config' => ['type' => 'send_email', 'template' => '@SyliusWorkflowPlugin/email/abandoned_cart.html.twig', 'subject' => 'You left something behind!']],
            ],
            'edges' => [
                ['id' => 'edge-1-2', 'source' => 'node-1', 'target' => 'node-2'],
                ['id' => 'edge-2-3', 'source' => 'node-2', 'target' => 'node-3'],
            ],
        ];
    }
}
