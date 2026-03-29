<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;

interface RuleInterface
{
    public function supports(string $rule): bool;

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool;
}
