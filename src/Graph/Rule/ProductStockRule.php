<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\OrderInterface;

final class ProductStockRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'product_stock';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $subject = $context->getSubject();
        if (!$subject instanceof OrderInterface) {
            return false;
        }

        $threshold = (int) $value;

        foreach ($subject->getItems() as $item) {
            $variant = $item->getVariant();
            if ($variant === null || !$variant->isTracked()) {
                continue;
            }

            $stock = $variant->getOnHand() - $variant->getOnHold();

            $match = match ($operator) {
                'out_of_stock' => $stock <= 0,
                'low_stock' => $stock > 0 && $stock <= $threshold,
                'in_stock' => $stock > $threshold,
                default => false,
            };

            if ($match) {
                return true;
            }
        }

        return false;
    }
}
