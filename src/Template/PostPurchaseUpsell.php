<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Template;

final class PostPurchaseUpsell implements WorkflowTemplateInterface
{
    public function getName(): string
    {
        return 'Post-Purchase Upsell';
    }

    public function getDescription(): string
    {
        return 'Send an upsell suggestion email 3 days after a first-time buyer completes an order.';
    }

    public function getCategory(): string
    {
        return 'win_back';
    }

    public function getThumbnailIcon(): string
    {
        return '📦';
    }

    public function getGraph(): array
    {
        return [
            'nodes' => [
                ['id' => 'node-1', 'type' => 'trigger', 'position' => ['x' => 300, 'y' => 40], 'config' => ['event' => 'order.completed']],
                ['id' => 'node-2', 'type' => 'delay', 'position' => ['x' => 300, 'y' => 200], 'config' => ['amount' => 3, 'unit' => 'days']],
                ['id' => 'node-3', 'type' => 'condition', 'position' => ['x' => 300, 'y' => 360], 'config' => ['rule' => 'customer_first_order', 'operator' => 'is', 'value' => 'true']],
                ['id' => 'node-4', 'type' => 'action', 'position' => ['x' => 300, 'y' => 520], 'config' => ['type' => 'send_email', 'template' => '@SyliusWorkflowPlugin/email/upsell_suggestion.html.twig', 'subject' => 'You might also like...']],
            ],
            'edges' => [
                ['id' => 'edge-1-2', 'source' => 'node-1', 'target' => 'node-2'],
                ['id' => 'edge-2-3', 'source' => 'node-2', 'target' => 'node-3'],
                ['id' => 'edge-3-4', 'source' => 'node-3', 'target' => 'node-4'],
            ],
        ];
    }
}
