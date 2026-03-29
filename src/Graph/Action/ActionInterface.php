<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Action;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;

interface ActionInterface
{
    public function supports(string $actionType): bool;

    /**
     * @return array{success: bool, message: string}
     */
    public function execute(array $config, WorkflowContext $context): array;
}
