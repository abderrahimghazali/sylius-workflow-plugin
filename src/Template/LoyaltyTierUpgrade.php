<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Template;

final class LoyaltyTierUpgrade implements WorkflowTemplateInterface
{
    public function getName(): string
    {
        return 'Loyalty Tier Upgrade';
    }

    public function getDescription(): string
    {
        return 'Congratulate customers on a loyalty tier upgrade with a bonus and email notification.';
    }

    public function getCategory(): string
    {
        return 'loyalty';
    }

    public function getThumbnailIcon(): string
    {
        return '🏆';
    }

    public function getGraph(): array
    {
        return [
            'nodes' => [
                ['id' => 'node-1', 'type' => 'trigger', 'position' => ['x' => 300, 'y' => 40], 'config' => ['event' => 'loyalty.tier_upgraded']],
                ['id' => 'node-2', 'type' => 'action', 'position' => ['x' => 300, 'y' => 200], 'config' => ['type' => 'send_email', 'template' => '@SyliusWorkflowPlugin/email/tier_upgrade.html.twig', 'subject' => 'Congratulations! You\'ve been upgraded']],
                ['id' => 'node-3', 'type' => 'action', 'position' => ['x' => 300, 'y' => 360], 'config' => ['type' => 'add_loyalty_points', 'amount' => '50', 'reason' => 'Tier upgrade bonus']],
            ],
            'edges' => [
                ['id' => 'edge-1-2', 'source' => 'node-1', 'target' => 'node-2'],
                ['id' => 'edge-2-3', 'source' => 'node-2', 'target' => 'node-3'],
            ],
        ];
    }
}
