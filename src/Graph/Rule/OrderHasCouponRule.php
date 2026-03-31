<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph\Rule;

use Abderrahim\SyliusWorkflowPlugin\Graph\WorkflowContext;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderHasCouponRule implements RuleInterface
{
    public function supports(string $rule): bool
    {
        return $rule === 'order_has_coupon';
    }

    public function evaluate(string $operator, string $value, WorkflowContext $context): bool
    {
        $subject = $context->getSubject();
        if (!$subject instanceof OrderInterface) {
            return false;
        }

        $coupon = $subject->getPromotionCoupon();

        if ($value === '') {
            // Check if any coupon exists
            $hasCoupon = $coupon !== null;
        } else {
            // Check for specific coupon code
            $hasCoupon = $coupon !== null && $coupon->getCode() === $value;
        }

        return match ($operator) {
            'is' => $hasCoupon,
            'is_not' => !$hasCoupon,
            default => false,
        };
    }
}
