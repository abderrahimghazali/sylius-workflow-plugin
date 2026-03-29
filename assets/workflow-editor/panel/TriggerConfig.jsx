import { TRIGGER_EVENT_LABELS } from '../utils/nodeLabels';

export default function TriggerConfig({ config, onChange }) {
    return (
        <div className="swp-panel__section">
            <label className="swp-panel__label">Event</label>
            <select
                className="swp-panel__select"
                value={config.event || ''}
                onChange={(e) => onChange({ ...config, event: e.target.value })}
            >
                <option value="">Select an event...</option>
                {Object.entries(TRIGGER_EVENT_LABELS).map(([value, label]) => (
                    <option key={value} value={value}>{label}</option>
                ))}
            </select>
        </div>
    );
}
