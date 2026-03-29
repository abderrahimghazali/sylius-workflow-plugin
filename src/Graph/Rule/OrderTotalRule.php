<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderTotalRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'order_total';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $subject = $context->getSubject();
        if (!$subject instanceof OrderInterface) {
            return false;
        }

        $orderTotal = $subject->getTotal();
        $compareValue = (int) $value;

        return $this->compare($orderTotal, $operator, $compareValue);
    }

    private function compare(int $actual, string $operator, int $expected): bool
    {
        return match ($operator) {
            'is' => $actual === $expected,
            'is_not' => $actual !== $expected,
            'gt' => $actual > $expected,
            'lt' => $actual < $expected,
            'gte' => $actual >= $expected,
            'lte' => $actual <= $expected,
            default => false,
        };
    }
}
