<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Enum;

enum TriggerEvent: string
{
    case OrderCompleted = 'order.completed';
    case OrderCancelled = 'order.cancelled';
    case OrderShipped = 'order.shipped';
    case CartAbandoned = 'cart.abandoned';
    case CustomerRegistered = 'customer.registered';
    case CustomerBirthday = 'customer.birthday';
    case LoyaltyTierUpgraded = 'loyalty.tier_upgraded';
    case PaymentFailed = 'payment.failed';
}
