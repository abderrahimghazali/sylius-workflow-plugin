<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\CustomerInterface as CoreCustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class CustomerLifetimeSpendRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'customer_lifetime_spend';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $customer = $this->resolveCustomer($context);
        if ($customer === null) {
            return false;
        }

        $totalSpend = 0;
        foreach ($customer->getOrders() as $order) {
            if (method_exists($order, 'getTotal')) {
                $totalSpend += $order->getTotal();
            }
        }

        $compareValue = (int) $value;

        return match ($operator) {
            'is' => $totalSpend === $compareValue,
            'is_not' => $totalSpend !== $compareValue,
            'gt' => $totalSpend > $compareValue,
            'lt' => $totalSpend < $compareValue,
            'gte' => $totalSpend >= $compareValue,
            'lte' => $totalSpend <= $compareValue,
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
