<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderContainsProductRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'order_product';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $subject = $context->getSubject();
        if (!$subject instanceof OrderInterface) {
            return false;
        }

        $productCode = $value;
        $found = false;

        foreach ($subject->getItems() as $item) {
            $variant = $item->getVariant();
            if ($variant === null) {
                continue;
            }

            $product = $variant->getProduct();
            if ($product === null) {
                continue;
            }

            if ($product->getCode() === $productCode) {
                $found = true;
                break;
            }
        }

        return match ($operator) {
            'contains' => $found,
            'not_contains' => !$found,
            default => false,
        };
    }
}
