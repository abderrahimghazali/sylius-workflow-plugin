<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;

final class LoyaltyTierRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'loyalty_tier';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $tier = $context->get('loyalty_tier');

        if ($tier === null) {
            return false;
        }

        return match ($operator) {
            'is' => $tier === $value,
            'is_not' => $tier !== $value,
            default => false,
        };
    }
}
