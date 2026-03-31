const TRIGGER_EVENT_LABELS = {
    'order.completed': 'Order completed',
    'order.cancelled': 'Order cancelled',
    'order.shipped': 'Order shipped',
    'cart.abandoned': 'Cart abandoned',
    'customer.registered': 'Customer registered',
    'customer.birthday': 'Customer birthday',
    'loyalty.tier_upgraded': 'Loyalty tier upgraded',
    'payment.failed': 'Payment failed',
};

const RULE_LABELS = {
    'order_total': 'Order total',
    'customer_first_order': 'First order',
    'customer_tag': 'Customer tag',
    'customer_country': 'Customer country',
    'loyalty_tier': 'Loyalty tier',
    'order_product': 'Order contains product',
    'product_stock': 'Product stock level',
    'order_has_coupon': 'Order has coupon',
    'workflow_run_count': 'Workflow run count',
};

const OPERATOR_LABELS = {
    'is': 'is',
    'is_not': 'is not',
    'gt': '>',
    'lt': '<',
    'gte': '≥',
    'lte': '≤',
    'contains': 'contains',
    'not_contains': 'does not contain',
    'out_of_stock': 'is out of stock',
    'low_stock': 'is low stock (≤ threshold)',
    'in_stock': 'is in stock (> threshold)',
    'contains': 'contains',
};

const ACTION_TYPE_LABELS = {
    'send_email': 'Send email',
    'generate_coupon': 'Generate coupon',
    'add_customer_tag': 'Add customer tag',
    'remove_customer_tag': 'Remove customer tag',
    'add_loyalty_points': 'Add loyalty points',
    'send_webhook': 'Send webhook',
    'add_order_note': 'Add order note',
};

const DELAY_UNIT_LABELS = {
    'minutes': 'minute(s)',
    'hours': 'hour(s)',
    'days': 'day(s)',
    'weeks': 'week(s)',
};

export function getTriggerLabel(config) {
    const event = config.event || '';
    return TRIGGER_EVENT_LABELS[event] || 'Select an event...';
}

export function getConditionLabel(config) {
    const rule = config.rule || '';
    const operator = config.operator || '';
    const value = config.value || '';

    if (!rule) return 'Set a condition...';

    const ruleLabel = RULE_LABELS[rule] || rule;
    const opLabel = OPERATOR_LABELS[operator] || operator;

    return `${ruleLabel} ${opLabel} ${value}`.trim();
}

export function getActionLabel(config) {
    const type = config.type || '';
    const label = ACTION_TYPE_LABELS[type] || 'Select an action...';

    switch (type) {
        case 'send_email':
            return config.subject ? `Email: "${config.subject}"` : label;
        case 'generate_coupon':
            return config.discount ? `Coupon: ${config.discount}% off` : label;
        case 'add_customer_tag':
            return config.tag ? `Add tag: "${config.tag}"` : label;
        case 'remove_customer_tag':
            return config.tag ? `Remove tag: "${config.tag}"` : label;
        case 'add_loyalty_points':
            return config.amount ? `Add ${config.amount} points` : label;
        case 'send_webhook':
            return config.url ? `Webhook: ${config.url}` : label;
        case 'add_order_note':
            return config.note ? `Note: "${config.note.substring(0, 30)}..."` : label;
        default:
            return label;
    }
}

export function getDelayLabel(config) {
    const amount = config.amount || '';
    const unit = config.unit || 'minutes';
    if (!amount) return 'Set delay...';
    return `Wait ${amount} ${DELAY_UNIT_LABELS[unit] || unit}`;
}

export function getNodeLabel(nodeType, config) {
    switch (nodeType) {
        case 'trigger': return getTriggerLabel(config);
        case 'condition': return getConditionLabel(config);
        case 'action': return getActionLabel(config);
        case 'delay': return getDelayLabel(config);
        default: return 'Unknown node';
    }
}

export {
    TRIGGER_EVENT_LABELS,
    RULE_LABELS,
    OPERATOR_LABELS,
    ACTION_TYPE_LABELS,
    DELAY_UNIT_LABELS,
};
