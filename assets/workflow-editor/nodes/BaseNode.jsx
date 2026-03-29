import { Handle, Position } from '@xyflow/react';
import { getNodeLabel } from '../utils/nodeLabels';

const TYPE_CONFIG = {
    trigger: { icon: '⚡', badge: 'Trigger', className: 'swp-node--trigger' },
    condition: { icon: '🔀', badge: 'Condition', className: 'swp-node--condition' },
    action: { icon: '▶', badge: 'Action', className: 'swp-node--action' },
    delay: { icon: '⏳', badge: 'Delay', className: 'swp-node--delay' },
};

export default function BaseNode({ data, selected, children }) {
    const nodeType = data.nodeType || 'action';
    const config = TYPE_CONFIG[nodeType] || TYPE_CONFIG.action;
    const label = getNodeLabel(nodeType, data.config || {});
    const isTrigger = nodeType === 'trigger';

    return (
        <div className={`swp-node ${config.className} ${selected ? 'selected' : ''}`}>
            {/* Entry handle — hidden on trigger nodes */}
            {!isTrigger && (
                <Handle
                    type="target"
                    position={Position.Top}
                    id="entry"
                />
            )}

            <div className="swp-node__header">
                <div className="swp-node__icon">{config.icon}</div>
                <span className="swp-node__badge">{config.badge}</span>
                <span className="swp-node__title">{label}</span>
            </div>

            <div className="swp-node__body">
                {children || label}
            </div>

            {/* Exit handle */}
            <Handle
                type="source"
                position={Position.Bottom}
                id="exit"
            />
            <span className="swp-handle-label swp-handle-label--bottom">Then</span>
        </div>
    );
}
