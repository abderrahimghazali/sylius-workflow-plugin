import { RULE_LABELS } from '../utils/nodeLabels';
import ProductAutocomplete from './ProductAutocomplete';

// Operators per rule type
const RULE_OPERATORS = {
    order_total: { 'gt': '>', 'lt': '<', 'gte': '≥', 'lte': '≤', 'is': '=', 'is_not': '≠' },
    customer_first_order: { 'is': 'is', 'is_not': 'is not' },
    customer_tag: { 'contains': 'has tag', 'not_contains': 'does not have tag' },
    customer_country: { 'is': 'is', 'is_not': 'is not' },
    loyalty_tier: { 'is': 'is', 'is_not': 'is not' },
    order_product: { 'contains': 'contains product', 'not_contains': 'does not contain product' },
    product_stock: { 'out_of_stock': 'out of stock', 'low_stock': 'low stock (≤ threshold)', 'in_stock': 'in stock' },
    order_has_coupon: { 'is': 'has coupon', 'is_not': 'does not have coupon' },
    workflow_run_count: { 'gt': '>', 'lt': '<', 'gte': '≥', 'lte': '≤', 'is': '=', 'is_not': '≠' },
    cart_item_count: { 'gt': '>', 'lt': '<', 'gte': '≥', 'lte': '≤', 'is': '=', 'is_not': '≠' },
    order_currency: { 'is': 'is', 'is_not': 'is not' },
    order_shipping_method: { 'is': 'is', 'is_not': 'is not' },
    customer_order_count: { 'gt': '>', 'lt': '<', 'gte': '≥', 'lte': '≤', 'is': '=', 'is_not': '≠' },
    customer_lifetime_spend: { 'gt': '>', 'lt': '<', 'gte': '≥', 'lte': '≤', 'is': '=', 'is_not': '≠' },
    customer_age_days: { 'gt': '>', 'lt': '<', 'gte': '≥', 'lte': '≤' },
    customer_group: { 'is': 'is', 'is_not': 'is not' },
    order_taxon: { 'contains': 'contains', 'not_contains': 'does not contain' },
    order_is_guest: { 'is': 'is', 'is_not': 'is not' },
};

// Value config per rule
const VALUE_CONFIG = {
    order_total: { type: 'number', placeholder: 'e.g. 5000 ($50.00)', hint: 'Value is in cents (e.g. 5000 = $50.00)' },
    customer_first_order: { type: 'select', options: { 'true': 'Yes', 'false': 'No' } },
    customer_tag: { type: 'text', placeholder: 'e.g. vip' },
    customer_country: { type: 'text', placeholder: 'e.g. US, FR, DE' },
    loyalty_tier: { type: 'text', placeholder: 'e.g. Gold, Silver' },
    order_product: { type: 'product_autocomplete' },
    product_stock: { type: 'number', placeholder: 'Stock threshold (e.g. 5)', hint: 'Number of units. Used for "low stock" check.' },
    order_has_coupon: { type: 'text', placeholder: 'Leave empty = any coupon, or enter code' },
    workflow_run_count: { type: 'number', placeholder: 'e.g. 3' },
    cart_item_count: { type: 'number', placeholder: 'e.g. 3' },
    order_currency: { type: 'text', placeholder: 'e.g. EUR, USD' },
    order_shipping_method: { type: 'text', placeholder: 'Shipping method code' },
    customer_order_count: { type: 'number', placeholder: 'e.g. 5' },
    customer_lifetime_spend: { type: 'number', placeholder: 'e.g. 50000 ($500.00)', hint: 'Value is in cents' },
    customer_age_days: { type: 'number', placeholder: 'e.g. 30 (days since registration)' },
    customer_group: { type: 'text', placeholder: 'e.g. wholesale, vip' },
    order_taxon: { type: 'text', placeholder: 'Taxon code (e.g. t-shirts)' },
    order_is_guest: { type: 'select', options: { 'true': 'Yes', 'false': 'No' } },
};

export default function ConditionConfig({ config, onChange, productSearchUrl }) {
    const update = (field, value) => onChange({ ...config, [field]: value });
    const rule = config.rule || '';
    const operators = RULE_OPERATORS[rule] || {};
    const valueConfig = VALUE_CONFIG[rule] || { type: 'text', placeholder: 'Enter value...' };

    return (
        <>
            <div className="swp-panel__section">
                <label className="swp-panel__label">Rule</label>
                <select
                    className="swp-panel__select"
                    value={rule}
                    onChange={(e) => onChange({ rule: e.target.value, operator: '', value: '' })}
                >
                    <option value="">Select a rule...</option>
                    {Object.entries(RULE_LABELS).map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
                    ))}
                </select>
            </div>

            {rule && (
                <div className="swp-panel__section">
                    <label className="swp-panel__label">Operator</label>
                    <select
                        className="swp-panel__select"
                        value={config.operator || ''}
                        onChange={(e) => update('operator', e.target.value)}
                    >
                        <option value="">Select operator...</option>
                        {Object.entries(operators).map(([value, label]) => (
                            <option key={value} value={value}>{label}</option>
                        ))}
                    </select>
                </div>
            )}

            {rule && valueConfig.type !== 'hidden' && (
                <div className="swp-panel__section">
                    <label className="swp-panel__label">Value</label>
                    {valueConfig.type === 'product_autocomplete' ? (
                        <ProductAutocomplete
                            value={config.value || ''}
                            onChange={(val) => update('value', val)}
                            searchUrl={productSearchUrl}
                        />
                    ) : valueConfig.type === 'select' ? (
                        <select
                            className="swp-panel__select"
                            value={config.value || ''}
                            onChange={(e) => update('value', e.target.value)}
                        >
                            <option value="">Select...</option>
                            {Object.entries(valueConfig.options).map(([val, label]) => (
                                <option key={val} value={val}>{label}</option>
                            ))}
                        </select>
                    ) : (
                        <input
                            className="swp-panel__input"
                            type={valueConfig.type}
                            value={config.value || ''}
                            onChange={(e) => update('value', e.target.value)}
                            placeholder={valueConfig.placeholder}
                        />
                    )}
                    {valueConfig.hint && (
                        <div className="swp-panel__preview" style={{ marginTop: '6px' }}>
                            {valueConfig.hint}
                        </div>
                    )}
                </div>
            )}

            {rule && (
                <div className="swp-panel__section">
                    <div className="swp-panel__preview">
                        ✅ If true → Then (continue workflow)
                    </div>
                    <div className="swp-panel__preview" style={{ marginTop: '4px' }}>
                        ❌ If false → Otherwise branch or stop
                    </div>
                </div>
            )}
        </>
    );
}
