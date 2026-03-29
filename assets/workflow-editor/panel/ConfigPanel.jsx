import TriggerConfig from './TriggerConfig';
import ConditionConfig from './ConditionConfig';
import ActionConfig from './ActionConfig';
import DelayConfig from './DelayConfig';
import { getNodeLabel } from '../utils/nodeLabels';

const NODE_TYPE_OPTIONS = [
    { value: 'trigger', label: 'Trigger' },
    { value: 'condition', label: 'Condition' },
    { value: 'action', label: 'Action' },
    { value: 'delay', label: 'Delay' },
];

export default function ConfigPanel({ node, onUpdate, onDelete, onClose }) {
    if (!node) return null;

    const { data } = node;
    const nodeType = data.nodeType;
    const config = data.config || {};
    const isTrigger = nodeType === 'trigger';

    const handleConfigChange = (newConfig) => {
        onUpdate(node.id, {
            ...data,
            config: newConfig,
        });
    };

    const handleTypeChange = (newType) => {
        onUpdate(node.id, {
            ...data,
            nodeType: newType,
            config: {},
        });
    };

    const label = getNodeLabel(nodeType, config);

    return (
        <div className="swp-panel">
            <div className="swp-panel__header">
                <span>Configure Node</span>
                <button className="swp-panel__close" onClick={onClose}>✕</button>
            </div>

            {/* Node type selector */}
            <div className="swp-panel__section">
                <label className="swp-panel__label">Node Type</label>
                <select
                    className="swp-panel__select"
                    value={nodeType}
                    onChange={(e) => handleTypeChange(e.target.value)}
                >
                    {NODE_TYPE_OPTIONS.map((opt) => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                </select>
            </div>

            {/* Type-specific fields */}
            {nodeType === 'trigger' && (
                <TriggerConfig config={config} onChange={handleConfigChange} />
            )}
            {nodeType === 'condition' && (
                <ConditionConfig config={config} onChange={handleConfigChange} />
            )}
            {nodeType === 'action' && (
                <ActionConfig config={config} onChange={handleConfigChange} />
            )}
            {nodeType === 'delay' && (
                <DelayConfig config={config} onChange={handleConfigChange} />
            )}

            {/* Auto-generated label preview */}
            <div className="swp-panel__section">
                <label className="swp-panel__label">Node Description</label>
                <div className="swp-panel__preview">{label}</div>
            </div>

            {/* Delete button */}
            <div className="swp-panel__footer">
                <button
                    className="swp-btn swp-btn--danger"
                    style={{ width: '100%', justifyContent: 'center' }}
                    onClick={() => onDelete(node.id)}
                    disabled={isTrigger}
                    title={isTrigger ? 'Cannot delete trigger node' : 'Delete this node'}
                >
                    🗑 Delete Node
                </button>
            </div>
        </div>
    );
}
