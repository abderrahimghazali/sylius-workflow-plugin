<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\CustomerInterface as CoreCustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class CustomerGroupRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'customer_group';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $customer = $this->resolveCustomer($context);
        if ($customer === null) {
            return false;
        }

        $group = $customer->getGroup();
        if ($group === null) {
            return $operator === 'is_not';
        }

        $groupCode = $group->getCode();

        return match ($operator) {
            'is' => $groupCode === $value,
            'is_not' => $groupCode !== $value,
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
