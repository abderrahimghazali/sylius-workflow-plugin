<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderCurrencyRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'order_currency';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $subject = $context->getSubject();
        if (!$subject instanceof OrderInterface) {
            return false;
        }

        $currency = $subject->getCurrencyCode();

        return match ($operator) {
            'is' => $currency === $value,
            'is_not' => $currency !== $value,
            default => false,
        };
    }
}
