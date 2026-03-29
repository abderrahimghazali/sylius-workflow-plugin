<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Template;

final class PaymentFailedRecovery implements WorkflowTemplateInterface
{
    public function getName(): string
    {
        return 'Payment Failed Recovery';
    }

    public function getDescription(): string
    {
        return 'Send a recovery email 2 hours after a payment fails, helping customers retry.';
    }

    public function getCategory(): string
    {
        return 'abandoned_cart';
    }

    public function getThumbnailIcon(): string
    {
        return '💳';
    }

    public function getGraph(): array
    {
        return [
            'nodes' => [
                ['id' => 'node-1', 'type' => 'trigger', 'position' => ['x' => 300, 'y' => 40], 'config' => ['event' => 'payment.failed']],
                ['id' => 'node-2', 'type' => 'delay', 'position' => ['x' => 300, 'y' => 200], 'config' => ['amount' => 2, 'unit' => 'hours']],
                ['id' => 'node-3', 'type' => 'action', 'position' => ['x' => 300, 'y' => 360], 'config' => ['type' => 'send_email', 'template' => '@SyliusWorkflowPlugin/email/payment_failed_recovery.html.twig', 'subject' => 'There was an issue with your payment']],
            ],
            'edges' => [
                ['id' => 'edge-1-2', 'source' => 'node-1', 'target' => 'node-2'],
                ['id' => 'edge-2-3', 'source' => 'node-2', 'target' => 'node-3'],
            ],
        ];
    }
}
