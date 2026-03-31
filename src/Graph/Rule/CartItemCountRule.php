<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\OrderInterface;

final class CartItemCountRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'cart_item_count';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $subject = $context->getSubject();
        if (!$subject instanceof OrderInterface) {
            return false;
        }

        $count = $subject->getTotalQuantity();
        $compareValue = (int) $value;

        return match ($operator) {
            'is' => $count === $compareValue,
            'is_not' => $count !== $compareValue,
            'gt' => $count > $compareValue,
            'lt' => $count < $compareValue,
            'gte' => $count >= $compareValue,
            'lte' => $count <= $compareValue,
            default => false,
        };
    }
}
