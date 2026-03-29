<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\CustomerInterface;
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

        if ($subject instanceof CustomerInterface) {
            $customer = $subject;
        } elseif ($subject instanceof OrderInterface) {
            $customer = $subject->getCustomer();
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
