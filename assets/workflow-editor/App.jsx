import { useState, useCallback, useRef, useMemo } from 'react';
import {
    ReactFlow,
    MiniMap,
    Background,
    useNodesState,
    useEdgesState,
    addEdge,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';

import TriggerNode from './nodes/TriggerNode';
import ConditionNode from './nodes/ConditionNode';
import ActionNode from './nodes/ActionNode';
import DelayNode from './nodes/DelayNode';
import ConfigPanel from './panel/ConfigPanel';
import { deserializeGraph, serializeGraph, generateNodeId, generateEdgeId } from './utils/graphSerializer';
import { validateGraph } from './utils/graphValidator';
import './styles/nodes.css';

const nodeTypes = {
    triggerNode: TriggerNode,
    conditionNode: ConditionNode,
    actionNode: ActionNode,
    delayNode: DelayNode,
};

const NODE_TYPE_TO_COMPONENT = {
    trigger: 'triggerNode',
    condition: 'conditionNode',
    action: 'actionNode',
    delay: 'delayNode',
};

export default function App({ initialGraph, workflowId, apiUrl, initialName, initialStatus }) {
    const { nodes: initNodes, edges: initEdges } = deserializeGraph(initialGraph);

    const [nodes, setNodes, onNodesChange] = useNodesState(initNodes);
    const [edges, setEdges, onEdgesChange] = useEdgesState(initEdges);
    const [selectedNodeId, setSelectedNodeId] = useState(null);
    const [workflowName, setWorkflowName] = useState(initialName || 'Untitled Workflow');
    const [status, setStatus] = useState(initialStatus || 'draft');
    const [errors, setErrors] = useState([]);
    const [toast, setToast] = useState(null);
    const [addMenuOpen, setAddMenuOpen] = useState(false);
    const [saving, setSaving] = useState(false);
    const toastTimer = useRef(null);

    const selectedNode = useMemo(
        () => nodes.find((n) => n.id === selectedNodeId) || null,
        [nodes, selectedNodeId]
    );

    // ── Connections ─────────────────────────────────────────

    const onConnect = useCallback(
        (params) => {
            const edge = {
                ...params,
                id: generateEdgeId(params.source, params.target),
                type: 'smoothstep',
                animated: false,
                style: { stroke: '#378ADD', strokeWidth: 1.5, opacity: 0.7 },
            };
            setEdges((eds) => addEdge(edge, eds));
        },
        [setEdges]
    );

    // ── Selection ───────────────────────────────────────────

    const onNodeClick = useCallback((_event, node) => {
        setSelectedNodeId(node.id);
    }, []);

    const onPaneClick = useCallback(() => {
        setSelectedNodeId(null);
    }, []);

    // ── Delete node (keyboard) ──────────────────────────────

    const onKeyDown = useCallback(
        (event) => {
            if ((event.key === 'Delete' || event.key === 'Backspace') && selectedNodeId) {
                const node = nodes.find((n) => n.id === selectedNodeId);
                if (node && node.data.nodeType !== 'trigger') {
                    setNodes((nds) => nds.filter((n) => n.id !== selectedNodeId));
                    setEdges((eds) =>
                        eds.filter((e) => e.source !== selectedNodeId && e.target !== selectedNodeId)
                    );
                    setSelectedNodeId(null);
                }
            }
        },
        [selectedNodeId, nodes, setNodes, setEdges]
    );

    // ── Add node ────────────────────────────────────────────

    const addNode = useCallback(
        (nodeType) => {
            const id = generateNodeId();
            const yPositions = nodes.map((n) => n.position.y);
            const maxY = yPositions.length > 0 ? Math.max(...yPositions) : -120;

            const newNode = {
                id,
                type: NODE_TYPE_TO_COMPONENT[nodeType],
                position: { x: 300, y: maxY + 160 },
                data: {
                    nodeType,
                    config: {},
                    label: '',
                },
            };

            setNodes((nds) => [...nds, newNode]);
            setSelectedNodeId(id);
            setAddMenuOpen(false);
        },
        [nodes, setNodes]
    );

    // ── Update node data ────────────────────────────────────

    const updateNodeData = useCallback(
        (nodeId, newData) => {
            setNodes((nds) =>
                nds.map((n) => {
                    if (n.id !== nodeId) return n;

                    const newType = NODE_TYPE_TO_COMPONENT[newData.nodeType] || n.type;
                    return {
                        ...n,
                        type: newType,
                        data: { ...newData },
                    };
                })
            );
        },
        [setNodes]
    );

    // ── Delete node (from panel) ────────────────────────────

    const deleteNode = useCallback(
        (nodeId) => {
            const node = nodes.find((n) => n.id === nodeId);
            if (node && node.data.nodeType === 'trigger') return;

            setNodes((nds) => nds.filter((n) => n.id !== nodeId));
            setEdges((eds) => eds.filter((e) => e.source !== nodeId && e.target !== nodeId));
            setSelectedNodeId(null);
        },
        [nodes, setNodes, setEdges]
    );

    // ── Auto-arrange ────────────────────────────────────────

    const autoArrange = useCallback(() => {
        setNodes((nds) =>
            nds.map((node, index) => ({
                ...node,
                position: { x: 300, y: 40 + index * 160 },
            }))
        );
    }, [setNodes]);

    // ── Save ────────────────────────────────────────────────

    const showToast = useCallback((type, message) => {
        clearTimeout(toastTimer.current);
        setToast({ type, message });
        toastTimer.current = setTimeout(() => setToast(null), 3000);
    }, []);

    const save = useCallback(async () => {
        setErrors([]);

        const validationErrors = validateGraph(nodes, edges);
        if (validationErrors.length > 0) {
            setErrors(validationErrors);
            return;
        }

        const graph = serializeGraph(nodes, edges);

        setSaving(true);
        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ graph, name: workflowName }),
            });

            if (!response.ok) {
                const text = await response.text();
                throw new Error(text || `HTTP ${response.status}`);
            }

            showToast('success', 'Workflow saved successfully.');
        } catch (err) {
            showToast('error', 'Save failed: ' + err.message);
        } finally {
            setSaving(false);
        }
    }, [nodes, edges, apiUrl, workflowName, showToast]);

    // ── Toggle activate ─────────────────────────────────────

    const toggleActivate = useCallback(async () => {
        const newStatus = status === 'active' ? 'paused' : 'active';

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    graph: serializeGraph(nodes, edges),
                    name: workflowName,
                    status: newStatus,
                }),
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            setStatus(newStatus);
            showToast('success', `Workflow ${newStatus === 'active' ? 'activated' : 'paused'}.`);
        } catch (err) {
            showToast('error', 'Status change failed: ' + err.message);
        }
    }, [status, nodes, edges, apiUrl, workflowName, showToast]);

    // ── Status badge class ──────────────────────────────────

    const badgeClass = `swp-toolbar__badge ${
        status === 'active' ? 'swp-toolbar__badge--active' :
        status === 'paused' ? 'swp-toolbar__badge--paused' : ''
    }`;

    return (
        <div className="swp-editor" onKeyDown={onKeyDown} tabIndex={0}>
            {/* ── Toolbar ──────────────────────────────────── */}
            <div className="swp-toolbar">
                <div className="swp-toolbar__left">
                    <input
                        className="swp-toolbar__name-input"
                        value={workflowName}
                        onChange={(e) => setWorkflowName(e.target.value)}
                    />
                    <span className={badgeClass}>{status}</span>
                </div>

                <div className="swp-toolbar__right">
                    {/* Add node dropdown */}
                    <div className="swp-dropdown">
                        <button
                            className="swp-btn"
                            onClick={() => setAddMenuOpen(!addMenuOpen)}
                        >
                            + Add Node
                        </button>
                        {addMenuOpen && (
                            <div className="swp-dropdown__menu">
                                <button className="swp-dropdown__item" onClick={() => addNode('trigger')}>
                                    ⚡ Trigger
                                </button>
                                <button className="swp-dropdown__item" onClick={() => addNode('condition')}>
                                    🔀 Condition
                                </button>
                                <button className="swp-dropdown__item" onClick={() => addNode('action')}>
                                    ▶ Action
                                </button>
                                <button className="swp-dropdown__item" onClick={() => addNode('delay')}>
                                    ⏳ Delay
                                </button>
                            </div>
                        )}
                    </div>

                    <button className="swp-btn" onClick={autoArrange}>
                        ↕ Auto-arrange
                    </button>

                    <button className="swp-btn" disabled title="Coming in Phase 4">
                        🧪 Test Run
                    </button>

                    <button
                        className="swp-btn swp-btn--primary"
                        onClick={save}
                        disabled={saving}
                    >
                        {saving ? 'Saving...' : '💾 Save'}
                    </button>

                    <button
                        className={`swp-btn swp-btn--toggle ${status === 'active' ? 'swp-btn--toggle-active' : ''}`}
                        onClick={toggleActivate}
                    >
                        {status === 'active' ? 'Active' : 'Activate'}
                    </button>
                </div>
            </div>

            {/* ── Error Banner ─────────────────────────────── */}
            {errors.length > 0 && (
                <div className="swp-error-banner">
                    <div>
                        <strong>Validation errors:</strong>
                        <ul style={{ margin: '4px 0 0 16px', padding: 0 }}>
                            {errors.map((err, i) => (
                                <li key={i}>{err}</li>
                            ))}
                        </ul>
                    </div>
                    <button className="swp-error-banner__close" onClick={() => setErrors([])}>
                        ✕
                    </button>
                </div>
            )}

            {/* ── Canvas + Panel ───────────────────────────── */}
            <div className="swp-editor__main">
                <div className="swp-editor__canvas">
                    <ReactFlow
                        nodes={nodes}
                        edges={edges}
                        nodeTypes={nodeTypes}
                        onNodesChange={onNodesChange}
                        onEdgesChange={onEdgesChange}
                        onConnect={onConnect}
                        onNodeClick={onNodeClick}
                        onPaneClick={onPaneClick}
                        fitView
                        fitViewOptions={{ padding: 0.3 }}
                        defaultEdgeOptions={{
                            type: 'smoothstep',
                            style: { stroke: '#378ADD', strokeWidth: 1.5, opacity: 0.7 },
                        }}
                    >
                        <Background variant="dots" gap={16} size={1} color="#d0d0d0" />
                        <MiniMap
                            position="bottom-right"
                            style={{ border: '1px solid #e5e5e5', borderRadius: '6px' }}
                            maskColor="rgba(0,0,0,0.05)"
                        />
                    </ReactFlow>
                </div>

                {/* ── Config Panel ─────────────────────────── */}
                {selectedNode && (
                    <ConfigPanel
                        node={selectedNode}
                        onUpdate={updateNodeData}
                        onDelete={deleteNode}
                        onClose={() => setSelectedNodeId(null)}
                    />
                )}
            </div>

            {/* ── Toast ────────────────────────────────────── */}
            {toast && (
                <div className={`swp-toast swp-toast--${toast.type}`}>
                    {toast.message}
                </div>
            )}
        </div>
    );
}
