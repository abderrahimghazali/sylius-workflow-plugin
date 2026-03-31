<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\CustomerInterface as CoreCustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class CustomerLifetimeOrderCountRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'customer_order_count';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $customer = $this->resolveCustomer($context);
        if ($customer === null) {
            return false;
        }

        $count = $customer->getOrders()->count();
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

    private function resolveCustomer(WorkflowContext $context): ?CoreCustomerInterface
    {
        $subject = $context->getSubject();

        if ($subject instanceof CoreCustomerInterface) {
            return $subject;
        }

        if ($subject instanceof OrderInterface) {
            $c = $subject->getCustomer();
            return $c instanceof CoreCustomerInterface ? $c : null;
        }

        return null;
    }
}
