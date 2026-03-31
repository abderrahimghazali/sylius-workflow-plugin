<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderIsGuestRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'order_is_guest';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $subject = $context->getSubject();
        if (!$subject instanceof OrderInterface) {
            return false;
        }

        $isGuest = $subject->isCreatedByGuest();
        $expectedValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return match ($operator) {
            'is' => $isGuest === $expectedValue,
            'is_not' => $isGuest !== $expectedValue,
            default => false,
        };
    }
}
