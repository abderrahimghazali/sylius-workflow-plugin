<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Enum;

enum ActionType: string
{
    case SendEmail = 'send_email';
    case GenerateCoupon = 'generate_coupon';
    case AddCustomerTag = 'add_customer_tag';
    case RemoveCustomerTag = 'remove_customer_tag';
    case AddLoyaltyPoints = 'add_loyalty_points';
    case SendWebhook = 'send_webhook';
    case AddOrderNote = 'add_order_note';
}
