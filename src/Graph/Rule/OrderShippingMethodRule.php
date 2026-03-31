<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderShippingMethodRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'order_shipping_method';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $subject = $context->getSubject();
        if (!$subject instanceof OrderInterface) {
            return false;
        }

        $found = false;
        foreach ($subject->getShipments() as $shipment) {
            $method = $shipment->getMethod();
            if ($method !== null && $method->getCode() === $value) {
                $found = true;
                break;
            }
        }

        return match ($operator) {
            'is' => $found,
            'is_not' => !$found,
            default => false,
        };
    }
}
