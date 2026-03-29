import { RULE_LABELS, OPERATOR_LABELS } from '../utils/nodeLabels';

export default function ConditionConfig({ config, onChange }) {
    const update = (field, value) => onChange({ ...config, [field]: value });

    return (
        <>
            <div className="swp-panel__section">
                <label className="swp-panel__label">Rule</label>
                <select
                    className="swp-panel__select"
                    value={config.rule || ''}
                    onChange={(e) => update('rule', e.target.value)}
                >
                    <option value="">Select a rule...</option>
                    {Object.entries(RULE_LABELS).map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
                    ))}
                </select>
            </div>

            <div className="swp-panel__section">
                <label className="swp-panel__label">Operator</label>
                <select
                    className="swp-panel__select"
                    value={config.operator || ''}
                    onChange={(e) => update('operator', e.target.value)}
                >
                    <option value="">Select operator...</option>
                    {Object.entries(OPERATOR_LABELS).map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
                    ))}
                </select>
            </div>

            <div className="swp-panel__section">
                <label className="swp-panel__label">Value</label>
                <input
                    className="swp-panel__input"
                    type={isNumericRule(config.rule) ? 'number' : 'text'}
                    value={config.value || ''}
                    onChange={(e) => update('value', e.target.value)}
                    placeholder="Enter value..."
                />
            </div>

            <div className="swp-panel__section">
                <div className="swp-panel__preview">
                    ✅ If true → Then (continue workflow)
                </div>
                <div className="swp-panel__preview" style={{ marginTop: '4px' }}>
                    ❌ If false → stop workflow
                </div>
            </div>
        </>
    );
}

function isNumericRule(rule) {
    return ['order_total', 'workflow_run_count'].includes(rule);
}
