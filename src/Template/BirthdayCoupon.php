<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Template;

final class BirthdayCoupon implements WorkflowTemplateInterface
{
    public function getName(): string
    {
        return 'Birthday Coupon';
    }

    public function getDescription(): string
    {
        return 'Celebrate customer birthdays with a personalized 15% discount coupon.';
    }

    public function getCategory(): string
    {
        return 'loyalty';
    }

    public function getThumbnailIcon(): string
    {
        return '🎂';
    }

    public function getGraph(): array
    {
        return [
            'nodes' => [
                ['id' => 'node-1', 'type' => 'trigger', 'position' => ['x' => 300, 'y' => 40], 'config' => ['event' => 'customer.birthday']],
                ['id' => 'node-2', 'type' => 'action', 'position' => ['x' => 300, 'y' => 200], 'config' => ['type' => 'generate_coupon', 'promotion' => 'BIRTHDAY', 'discount' => '15', 'expires_in_days' => '7']],
                ['id' => 'node-3', 'type' => 'action', 'position' => ['x' => 300, 'y' => 360], 'config' => ['type' => 'send_email', 'template' => '@SyliusWorkflowPlugin/email/birthday_coupon.html.twig', 'subject' => 'Happy Birthday! Here\'s a gift for you']],
            ],
            'edges' => [
                ['id' => 'edge-1-2', 'source' => 'node-1', 'target' => 'node-2'],
                ['id' => 'edge-2-3', 'source' => 'node-2', 'target' => 'node-3'],
            ],
        ];
    }
}
