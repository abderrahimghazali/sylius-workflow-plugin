<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;

final class CustomerFirstOrderRule implements RuleInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {
    }

    public function supports(string $rule): bool
    {
        return $rule === 'customer_first_order';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $subject = $context->getSubject();
        $customer = null;

        if ($subject instanceof CustomerInterface) {
            $customer = $subject;
        } elseif ($subject instanceof OrderInterface) {
            $customer = $subject->getCustomer();
        }

        if ($customer === null) {
            return false;
        }

        $completedOrders = $this->orderRepository->countByCustomer($customer);
        $isFirstOrder = $completedOrders <= 1;

        $expectedValue = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return match ($operator) {
            'is' => $isFirstOrder === $expectedValue,
            'is_not' => $isFirstOrder !== $expectedValue,
            default => false,
        };
    }
}
