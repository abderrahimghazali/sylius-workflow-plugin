<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\CustomerInterface as CoreCustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class CustomerCountryRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'customer_country';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $subject = $context->getSubject();
        $customer = null;

        if ($subject instanceof CoreCustomerInterface) {
            $customer = $subject;
        } elseif ($subject instanceof OrderInterface) {
            $c = $subject->getCustomer();
            $customer = $c instanceof CoreCustomerInterface ? $c : null;
        }

        if ($customer === null) {
            return false;
        }

        $address = $customer->getDefaultAddress();
        if ($address === null) {
            return false;
        }

        $countryCode = $address->getCountryCode();

        return match ($operator) {
            'is' => $countryCode === $value,
            'is_not' => $countryCode !== $value,
            default => false,
        };
    }
}
