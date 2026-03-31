<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\CustomerInterface as CoreCustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class CustomerRegisteredDaysAgoRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'customer_age_days';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $customer = $this->resolveCustomer($context);
        if ($customer === null) {
            return false;
        }

        $createdAt = $customer->getCreatedAt();
        if ($createdAt === null) {
            return false;
        }

        $daysAgo = (int) $createdAt->diff(new \DateTimeImmutable())->days;
        $compareValue = (int) $value;

        return match ($operator) {
            'gt' => $daysAgo > $compareValue,
            'lt' => $daysAgo < $compareValue,
            'gte' => $daysAgo >= $compareValue,
            'lte' => $daysAgo <= $compareValue,
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
