<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductInterface as CoreProductInterface;

final class OrderContainsTaxonRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'order_taxon';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $subject = $context->getSubject();
        if (!$subject instanceof OrderInterface) {
            return false;
        }

        $taxonCodes = array_map('trim', explode(',', $value));
        $taxonCodes = array_filter($taxonCodes);
        $found = false;

        foreach ($subject->getItems() as $item) {
            $variant = $item->getVariant();
            if ($variant === null) {
                continue;
            }

            $product = $variant->getProduct();
            if (!$product instanceof CoreProductInterface) {
                continue;
            }

            // Check main taxon
            $mainTaxon = $product->getMainTaxon();
            if ($mainTaxon !== null && \in_array($mainTaxon->getCode(), $taxonCodes, true)) {
                $found = true;
                break;
            }

            // Check all taxons
            foreach ($product->getTaxons() as $taxon) {
                if (\in_array($taxon->getCode(), $taxonCodes, true)) {
                    $found = true;
                    break 2;
                }
            }
        }

        return match ($operator) {
            'contains' => $found,
            'not_contains' => !$found,
            default => false,
        };
    }
}
