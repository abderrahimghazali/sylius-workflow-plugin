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
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Recipient (optional)</label>
                        <input
                            className="swp-panel__input"
                            type="email"
                            value={config.recipient || ''}
                            onChange={(e) => update('recipient', e.target.value)}
                            placeholder="Leave empty to send to customer"
                        />
                        <div className="swp-panel__preview" style={{ marginTop: '6px' }}>
                            {config.recipient ? `Sends to: ${config.recipient}` : 'Sends to the customer email'}
                        </div>
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
                        <label className="swp-panel__label">Format</label>
                        <select
                            className="swp-panel__select"
                            value={config.format || 'json'}
                            onChange={(e) => update('format', e.target.value)}
                        >
                            <option value="json">JSON (generic)</option>
                            <option value="discord">Discord</option>
                            <option value="slack">Slack</option>
                        </select>
                    </div>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Webhook URL</label>
                        <input
                            className="swp-panel__input"
                            type="text"
                            value={config.url || ''}
                            onChange={(e) => update('url', e.target.value)}
                            placeholder={
                                config.format === 'discord' ? 'https://discord.com/api/webhooks/...' :
                                config.format === 'slack' ? 'https://hooks.slack.com/services/...' :
                                'https://example.com/webhook'
                            }
                        />
                    </div>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Message</label>
                        <textarea
                            className="swp-panel__input"
                            rows={3}
                            value={config.message || ''}
                            onChange={(e) => update('message', e.target.value)}
                            placeholder={'🛒 New order {order_number}!\nCustomer: {customer_name} ({customer_email})\nEvent: {event} on {channel}'}
                            style={{ resize: 'vertical' }}
                        />
                        <div className="swp-panel__preview" style={{ marginTop: '6px' }}>
                            Variables: {'{event}'}, {'{subject_id}'}, {'{order_number}'}, {'{customer_email}'}, {'{customer_name}'}, {'{workflow}'}, {'{channel}'}
                        </div>
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

            {actionType === 'add_order_tag' && (
                <div className="swp-panel__section">
                    <label className="swp-panel__label">Tag</label>
                    <input
                        className="swp-panel__input"
                        type="text"
                        value={config.tag || ''}
                        onChange={(e) => update('tag', e.target.value)}
                        placeholder="e.g. priority, fragile, vip-order"
                    />
                </div>
            )}

            {actionType === 'subscribe_newsletter' && (
                <div className="swp-panel__section">
                    <div className="swp-panel__preview">
                        Subscribes the customer to the newsletter automatically.
                    </div>
                </div>
            )}

            {actionType === 'send_sms' && (
                <>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Phone (optional)</label>
                        <input
                            className="swp-panel__input"
                            type="text"
                            value={config.phone || ''}
                            onChange={(e) => update('phone', e.target.value)}
                            placeholder="Leave empty to use customer phone"
                        />
                    </div>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Message</label>
                        <textarea
                            className="swp-panel__input"
                            rows={3}
                            value={config.message || ''}
                            onChange={(e) => update('message', e.target.value)}
                            placeholder="Your order {order_number} has been shipped!"
                            style={{ resize: 'vertical' }}
                        />
                        <div className="swp-panel__preview" style={{ marginTop: '6px' }}>
                            Dispatches workflow.sms.send event. Register a listener with your SMS provider (Twilio, Vonage, etc.)
                        </div>
                    </div>
                </>
            )}

            {actionType === 'track_event' && (
                <>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Event Name</label>
                        <input
                            className="swp-panel__input"
                            type="text"
                            value={config.event_name || ''}
                            onChange={(e) => update('event_name', e.target.value)}
                            placeholder="e.g. purchase_completed, vip_upgrade"
                        />
                    </div>
                    <div className="swp-panel__section">
                        <label className="swp-panel__label">Properties (optional JSON)</label>
                        <textarea
                            className="swp-panel__input"
                            rows={2}
                            value={config.properties || ''}
                            onChange={(e) => update('properties', e.target.value)}
                            placeholder='{"source": "workflow", "category": "order"}'
                            style={{ resize: 'vertical' }}
                        />
                        <div className="swp-panel__preview" style={{ marginTop: '6px' }}>
                            Dispatches workflow.analytics.track event. Register a listener for GA4, Segment, etc.
                        </div>
                    </div>
                </>
            )}

            {actionType === 'assign_customer_group' && (
                <div className="swp-panel__section">
                    <label className="swp-panel__label">Group Code</label>
                    <input
                        className="swp-panel__input"
                        type="text"
                        value={config.group_code || ''}
                        onChange={(e) => update('group_code', e.target.value)}
                        placeholder="e.g. wholesale, vip, b2b"
                    />
                </div>
            )}
        </>
    );
}
