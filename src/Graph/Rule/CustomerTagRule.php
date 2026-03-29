<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;

final class CustomerTagRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'customer_tag';
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

        $tags = $context->get('customer_tags', []);
        if (empty($tags) && method_exists($customer, 'getTags')) {
            $tags = $customer->getTags();
        }

        $hasTag = \in_array($value, $tags, true);

        return match ($operator) {
            'is', 'contains' => $hasTag,
            'is_not' => !$hasTag,
            default => false,
        };
    }
}
