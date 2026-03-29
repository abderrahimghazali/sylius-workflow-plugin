import { ACTION_TYPE_LABELS } from '../utils/nodeLabels';

const EMAIL_TEMPLATES = {
    '@SyliusWorkflowPlugin/email/abandoned_cart.html.twig': 'Abandoned Cart Recovery',
    '@SyliusWorkflowPlugin/email/review_request.html.twig': 'Post-Purchase Review Request',
    '@SyliusWorkflowPlugin/email/win_back.html.twig': 'Win Back / Coupon Offer',
    '@SyliusWorkflowPlugin/email/birthday_coupon.html.twig': 'Birthday Coupon',
    '@SyliusWorkflowPlugin/email/tier_upgrade.html.twig': 'Loyalty Tier Upgrade',
    '@SyliusWorkflowPlugin/email/welcome.html.twig': 'New Customer Welcome',
    '@SyliusWorkflowPlugin/email/upsell_suggestion.html.twig': 'Upsell Suggestion',
    '@SyliusWorkflowPlugin/email/payment_failed_recovery.html.twig': 'Payment Failed Recovery',
};

export default function ActionConfig({ config, onChange }) {
    const update = (field, value) => onChange({ ...config, [field]: value });
    const actionType = config.type || '';

    return (
        <>
            <div className="swp-panel__section">
                <label className="swp-panel__label">Action Type</label>
                <select
                    className="swp-panel__select"
                    value={actionType}
                    onChange={(e) => onChange({ type: e.target.value })}
                >
                    <option value="">Select an action...</option>
                    {Object.entries(ACTION_TYPE_LABELS).map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
                    ))}
                </select>
            </div>

            {actionType === 'send_email' && (
                <>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Email Template</label>
                        <select
                            className="swp-panel__select"
                            value={config.template || ''}
                            onChange={(e) => update('template', e.target.value)}
                        >
                            <option value="">Select a template...</option>
                            {Object.entries(EMAIL_TEMPLATES).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                    </div>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Subject</label>
                        <input
                            className="swp-panel__input"
                            type="text"
                            value={config.subject || ''}
                            onChange={(e) => update('subject', e.target.value)}
                            placeholder="Email subject line..."
                        />
                    </div>
                </>
            )}

            {actionType === 'generate_coupon' && (
                <>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Promotion Code</label>
                        <input
                            className="swp-panel__input"
                            type="text"
                            value={config.promotion || ''}
                            onChange={(e) => update('promotion', e.target.value)}
                            placeholder="PROMO_CODE"
                        />
                    </div>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Discount %</label>
                        <input
                            className="swp-panel__input"
                            type="number"
                            value={config.discount || ''}
                            onChange={(e) => update('discount', e.target.value)}
                            placeholder="10"
                        />
                    </div>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Expires in (days)</label>
                        <input
                            className="swp-panel__input"
                            type="number"
                            value={config.expires_in_days || ''}
                            onChange={(e) => update('expires_in_days', e.target.value)}
                            placeholder="30"
                        />
                    </div>
                </>
            )}

            {actionType === 'add_customer_tag' && (
                <div className="swp-panel__section">
                    <label className="swp-panel__label">Tag Name</label>
                    <input
                        className="swp-panel__input"
                        type="text"
                        value={config.tag || ''}
                        onChange={(e) => update('tag', e.target.value)}
                        placeholder="vip-customer"
                    />
                </div>
            )}

            {actionType === 'remove_customer_tag' && (
                <div className="swp-panel__section">
                    <label className="swp-panel__label">Tag Name</label>
                    <input
                        className="swp-panel__input"
                        type="text"
                        value={config.tag || ''}
                        onChange={(e) => update('tag', e.target.value)}
                        placeholder="tag-to-remove"
                    />
                </div>
            )}

            {actionType === 'add_loyalty_points' && (
                <div className="swp-panel__section">
                    <label className="swp-panel__label">Points Amount</label>
                    <input
                        className="swp-panel__input"
                        type="number"
                        value={config.amount || ''}
                        onChange={(e) => update('amount', e.target.value)}
                        placeholder="100"
                    />
                </div>
            )}

            {actionType === 'send_webhook' && (
                <>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Webhook URL</label>
                        <input
                            className="swp-panel__input"
                            type="text"
                            value={config.url || ''}
                            onChange={(e) => update('url', e.target.value)}
                            placeholder="https://example.com/webhook"
                        />
                    </div>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Method</label>
                        <select
                            className="swp-panel__select"
                            value={config.method || 'POST'}
                            onChange={(e) => update('method', e.target.value)}
                        >
                            <option value="POST">POST</option>
                            <option value="PUT">PUT</option>
                        </select>
                    </div>
                </>
            )}

            {actionType === 'add_order_note' && (
                <div className="swp-panel__section">
                    <label className="swp-panel__label">Note</label>
                    <input
                        className="swp-panel__input"
                        type="text"
                        value={config.note || ''}
                        onChange={(e) => update('note', e.target.value)}
                        placeholder="Order note text..."
                    />
                </div>
            )}
        </>
    );
}
