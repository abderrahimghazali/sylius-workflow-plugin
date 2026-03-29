import { DELAY_UNIT_LABELS } from '../utils/nodeLabels';

export default function DelayConfig({ config, onChange }) {
    const update = (field, value) => onChange({ ...config, [field]: value });

    return (
        <>
            <div className="swp-panel__section">
                <label className="swp-panel__label">Amount</label>
                <input
                    className="swp-panel__input"
                    type="number"
                    min="1"
                    value={config.amount || ''}
                    onChange={(e) => update('amount', e.target.value)}
                    placeholder="1"
                />
            </div>

            <div className="swp-panel__section">
                <label className="swp-panel__label">Unit</label>
                <select
                    className="swp-panel__select"
                    value={config.unit || 'minutes'}
                    onChange={(e) => update('unit', e.target.value)}
                >
                    {Object.entries(DELAY_UNIT_LABELS).map(([value, label]) => (
                        <option key={value} value={value}>{label}</option>
                    ))}
                </select>
            </div>
        </>
    );
}
