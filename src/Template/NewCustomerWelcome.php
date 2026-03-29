<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Template;

final class NewCustomerWelcome implements WorkflowTemplateInterface
{
    public function getName(): string
    {
        return 'New Customer Welcome';
    }

    public function getDescription(): string
    {
        return 'Send a warm welcome email 1 hour after a customer registers.';
    }

    public function getCategory(): string
    {
        return 'onboarding';
    }

    public function getThumbnailIcon(): string
    {
        return '👋';
    }

    public function getGraph(): array
    {
        return [
            'nodes' => [
                ['id' => 'node-1', 'type' => 'trigger', 'position' => ['x' => 300, 'y' => 40], 'config' => ['event' => 'customer.registered']],
                ['id' => 'node-2', 'type' => 'delay', 'position' => ['x' => 300, 'y' => 200], 'config' => ['amount' => 1, 'unit' => 'hours']],
                ['id' => 'node-3', 'type' => 'action', 'position' => ['x' => 300, 'y' => 360], 'config' => ['type' => 'send_email', 'template' => '@SyliusWorkflowPlugin/email/welcome.html.twig', 'subject' => 'Welcome to our store!']],
            ],
            'edges' => [
                ['id' => 'edge-1-2', 'source' => 'node-1', 'target' => 'node-2'],
                ['id' => 'edge-2-3', 'source' => 'node-2', 'target' => 'node-3'],
            ],
        ];
    }
}
