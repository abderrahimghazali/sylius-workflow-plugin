import { Handle, Position } from '@xyflow/react';
import { getNodeLabel } from '../utils/nodeLabels';

const TYPE_CONFIG = {
    trigger: {
        icon: (
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
            </svg>
        ),
        badge: 'Trigger',
        className: 'swp-node--trigger',
    },
    condition: {
        icon: (
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/>
                <polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/>
                <line x1="4" y1="4" x2="9" y2="9"/>
            </svg>
        ),
        badge: 'Condition',
        className: 'swp-node--condition',
    },
    action: {
        icon: (
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <polygon points="5 3 19 12 5 21 5 3"/>
            </svg>
        ),
        badge: 'Action',
        className: 'swp-node--action',
    },
    delay: {
        icon: (
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
        ),
        badge: 'Delay',
        className: 'swp-node--delay',
    },
};

export default function BaseNode({ data, selected }) {
    const nodeType = data.nodeType || 'action';
    const config = TYPE_CONFIG[nodeType] || TYPE_CONFIG.action;
    const label = getNodeLabel(nodeType, data.config || {});
    const isTrigger = nodeType === 'trigger';
    const isCondition = nodeType === 'condition';

    return (
        <div className={`swp-node ${config.className} ${selected ? 'swp-node--selected' : ''}`}>
            {!isTrigger && (
                <Handle type="target" position={Position.Top} id="entry" />
            )}

            <div className="swp-node__header">
                <div className="swp-node__icon">{config.icon}</div>
                <span className="swp-node__badge">{config.badge}</span>
            </div>

            <div className="swp-node__body">
                <span className="swp-node__label">{label}</span>
            </div>

            {isCondition ? (
                <div className="swp-node__branches">
                    <div className="swp-node__branch swp-node__branch--true">
                        <span className="swp-node__branch-label">Then</span>
                        <Handle type="source" position={Position.Bottom} id="exit-true" style={{ left: '30%' }} />
                    </div>
                    <div className="swp-node__branch swp-node__branch--false">
                        <span className="swp-node__branch-label">Otherwise</span>
                        <Handle type="source" position={Position.Bottom} id="exit-false" style={{ left: '70%' }} />
                    </div>
                </div>
            ) : (
                <Handle type="source" position={Position.Bottom} id="exit" />
            )}
        </div>
    );
}
