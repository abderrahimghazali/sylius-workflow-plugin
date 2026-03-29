<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Template;

final class WinBackInactiveCustomer implements WorkflowTemplateInterface
{
    public function getName(): string
    {
        return 'Win Back Inactive Customer';
    }

    public function getDescription(): string
    {
        return 'Re-engage customers who haven\'t ordered in 30 days with a 10% discount coupon.';
    }

    public function getCategory(): string
    {
        return 'win_back';
    }

    public function getThumbnailIcon(): string
    {
        return '💌';
    }

    public function getGraph(): array
    {
        return [
            'nodes' => [
                ['id' => 'node-1', 'type' => 'trigger', 'position' => ['x' => 300, 'y' => 40], 'config' => ['event' => 'cart.abandoned']],
                ['id' => 'node-2', 'type' => 'action', 'position' => ['x' => 300, 'y' => 200], 'config' => ['type' => 'generate_coupon', 'promotion' => 'WIN_BACK', 'discount' => '10', 'expires_in_days' => '14']],
                ['id' => 'node-3', 'type' => 'action', 'position' => ['x' => 300, 'y' => 360], 'config' => ['type' => 'send_email', 'template' => '@SyliusWorkflowPlugin/email/win_back.html.twig', 'subject' => 'We miss you! Here\'s 10% off']],
            ],
            'edges' => [
                ['id' => 'edge-1-2', 'source' => 'node-1', 'target' => 'node-2'],
                ['id' => 'edge-2-3', 'source' => 'node-2', 'target' => 'node-3'],
            ],
        ];
    }
}
