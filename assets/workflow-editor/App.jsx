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

export default function App({ initialGraph, workflowId, apiUrl, initialName, initialStatus, runsUrl }) {
    const { nodes: initNodes, edges: initEdges } = deserializeGraph(initialGraph);

    const [nodes, setNodes, onNodesChange] = useNodesState(initNodes);
    const [edges, setEdges, onEdgesChange] = useEdgesState(initEdges);
    const [selectedNodeId, setSelectedNodeId] = useState(null);
    const [workflowName, setWorkflowName] = useState(initialName || 'Untitled Workflow');
    const [status, setStatus] = useState(initialStatus || 'draft');
    const [errors, setErrors] = useState([]);
    const [testRunModal, setTestRunModal] = useState(false);
    const [testSubjectType, setTestSubjectType] = useState('order');
    const [testSubjectId, setTestSubjectId] = useState('');
    const [testRunning, setTestRunning] = useState(false);
    const [testResults, setTestResults] = useState(null);
    const [toast, setToast] = useState(null);
    const [addMenuOpen, setAddMenuOpen] = useState(false);
    const [insertEdge, setInsertEdge] = useState(null); // edge to insert into
    const [insertMenuPos, setInsertMenuPos] = useState(null);
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
                style: { stroke: '#94A3B8', strokeWidth: 1.5 },
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
        setInsertEdge(null);
        setInsertMenuPos(null);
    }, []);

    // ── Insert node on edge click ───────────────────────────

    const onEdgeClick = useCallback((_event, edge) => {
        _event.stopPropagation();
        const sourceNode = nodes.find((n) => n.id === edge.source);
        const targetNode = nodes.find((n) => n.id === edge.target);
        if (!sourceNode || !targetNode) return;

        const midX = (sourceNode.position.x + targetNode.position.x) / 2;
        const midY = (sourceNode.position.y + targetNode.position.y) / 2;

        setInsertEdge({ edge, midX, midY });
        setInsertMenuPos({ x: _event.clientX, y: _event.clientY });
    }, [nodes]);

    const insertNodeOnEdge = useCallback(
        (nodeType) => {
            if (!insertEdge) return;
            const { edge, midX, midY } = insertEdge;
            const id = generateNodeId();

            const newNode = {
                id,
                type: NODE_TYPE_TO_COMPONENT[nodeType],
                position: { x: midX, y: midY },
                data: { nodeType, config: {}, label: '' },
            };

            setNodes((nds) => [...nds, newNode]);

            // Remove old edge, add two new edges
            setEdges((eds) => {
                const filtered = eds.filter((e) => e.id !== edge.id);
                return [
                    ...filtered,
                    {
                        id: generateEdgeId(edge.source, id),
                        source: edge.source,
                        target: id,
                        type: 'smoothstep',
                        animated: false,
                        style: { stroke: '#94A3B8', strokeWidth: 1.5 },
                    },
                    {
                        id: generateEdgeId(id, edge.target),
                        source: id,
                        target: edge.target,
                        type: 'smoothstep',
                        animated: false,
                        style: { stroke: '#94A3B8', strokeWidth: 1.5 },
                    },
                ];
            });

            setSelectedNodeId(id);
            setInsertEdge(null);
            setInsertMenuPos(null);
        },
        [insertEdge, setNodes, setEdges]
    );

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

    // ── Test run ─────────────────────────────────────────────

    const runTest = useCallback(async () => {
        if (!testSubjectId) return;
        setTestRunning(true);
        setTestResults(null);

        try {
            const testUrl = apiUrl.replace('/graph', '/test-run');
            const response = await fetch(testUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ subjectType: testSubjectType, subjectId: parseInt(testSubjectId, 10) }),
            });

            if (!response.ok) {
                const err = await response.json();
                showToast('error', err.error || 'Test run failed.');
                setTestRunning(false);
                return;
            }

            const data = await response.json();
            setTestResults(data);

            // Highlight nodes on canvas
            setNodes((nds) =>
                nds.map((n) => {
                    const result = data.results[n.id];
                    if (!result) return { ...n, className: '' };
                    const cls = result.status === 'passed' ? 'swp-test-passed'
                        : result.status === 'dry_run' ? 'swp-test-passed'
                        : result.status === 'skipped' ? 'swp-test-skipped'
                        : '';
                    return { ...n, className: cls };
                })
            );

            // Animate traversed edges
            const pathSet = new Set(data.path);
            setEdges((eds) =>
                eds.map((e) => ({
                    ...e,
                    animated: pathSet.has(e.source) && pathSet.has(e.target),
                }))
            );

            showToast('success', 'Test run completed.');
        } catch (err) {
            showToast('error', 'Test run failed: ' + err.message);
        } finally {
            setTestRunning(false);
        }
    }, [testSubjectType, testSubjectId, apiUrl, setNodes, setEdges, showToast]);

    return (
        <div className="swp-editor" onKeyDown={onKeyDown} tabIndex={0}>
            {/* ── Toolbar ──────────────────────────────────── */}
            <div className="swp-toolbar">
                <div className="swp-toolbar__left">
                    <span className="swp-toolbar__title">{workflowName}</span>
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

                    <button className="swp-btn" onClick={() => setTestRunModal(true)}>
                        🧪 Test Run
                    </button>

                    {runsUrl && (
                        <a href={runsUrl} className="swp-btn" style={{ textDecoration: 'none' }}>
                            📋 View Runs
                        </a>
                    )}

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
                        <span className="swp-toggle-dot" />
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
                        onEdgeClick={onEdgeClick}
                        fitView
                        fitViewOptions={{ padding: 0.5, maxZoom: 1 }}
                        defaultEdgeOptions={{
                            type: 'smoothstep',
                            style: { stroke: '#94A3B8', strokeWidth: 1.5 },
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

            {/* ── Insert Node on Edge Menu ──────────────────── */}
            {insertMenuPos && (
                <div style={{
                    position: 'fixed', inset: 0, zIndex: 998,
                }} onClick={() => { setInsertEdge(null); setInsertMenuPos(null); }}>
                    <div style={{
                        position: 'absolute',
                        left: insertMenuPos.x,
                        top: insertMenuPos.y,
                        background: '#fff',
                        border: '1px solid #E5E7EB',
                        borderRadius: '8px',
                        boxShadow: '0 8px 24px rgba(0,0,0,0.08)',
                        padding: '4px',
                        minWidth: '160px',
                    }} onClick={(e) => e.stopPropagation()}>
                        <div style={{ padding: '6px 10px', fontSize: '10px', fontWeight: 600, color: '#9CA3AF', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                            Insert node
                        </div>
                        <button className="swp-dropdown__item" onClick={() => insertNodeOnEdge('condition')}>
                            🔀 Condition
                        </button>
                        <button className="swp-dropdown__item" onClick={() => insertNodeOnEdge('action')}>
                            ▶ Action
                        </button>
                        <button className="swp-dropdown__item" onClick={() => insertNodeOnEdge('delay')}>
                            ⏳ Delay
                        </button>
                    </div>
                </div>
            )}

            {/* ── Test Run Modal ────────────────────────────── */}
            {testRunModal && (
                <div style={{
                    position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.3)',
                    display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 999,
                }} onClick={() => setTestRunModal(false)}>
                    <div style={{
                        background: '#fff', borderRadius: '10px', padding: '24px',
                        width: '360px', boxShadow: '0 8px 24px rgba(0,0,0,0.12)',
                    }} onClick={(e) => e.stopPropagation()}>
                        <h3 style={{ margin: '0 0 16px', fontSize: '15px', fontWeight: 600 }}>
                            Test Run
                        </h3>
                        <label className="swp-panel__label">Subject Type</label>
                        <select
                            className="swp-panel__select"
                            value={testSubjectType}
                            onChange={(e) => setTestSubjectType(e.target.value)}
                            style={{ marginBottom: '12px' }}
                        >
                            <option value="order">Order</option>
                            <option value="customer">Customer</option>
                        </select>
                        <label className="swp-panel__label">Subject ID</label>
                        <input
                            className="swp-panel__input"
                            type="number"
                            value={testSubjectId}
                            onChange={(e) => setTestSubjectId(e.target.value)}
                            placeholder="e.g. 12345"
                            style={{ marginBottom: '16px' }}
                        />
                        <div style={{ display: 'flex', gap: '8px' }}>
                            <button
                                className="swp-btn swp-btn--primary"
                                onClick={runTest}
                                disabled={testRunning || !testSubjectId}
                                style={{ flex: 1 }}
                            >
                                {testRunning ? 'Running...' : 'Run Test'}
                            </button>
                            <button
                                className="swp-btn"
                                onClick={() => setTestRunModal(false)}
                            >
                                Cancel
                            </button>
                        </div>
                        {testResults && (
                            <div style={{ marginTop: '16px', fontSize: '11px' }}>
                                <strong>Execution path:</strong>
                                <div style={{ marginTop: '6px' }}>
                                    {testResults.path.map((nodeId, i) => {
                                        const r = testResults.results[nodeId];
                                        const color = r?.status === 'passed' || r?.status === 'dry_run' ? '#0F6E56'
                                            : r?.status === 'skipped' ? '#854F0B' : '#666';
                                        return (
                                            <div key={nodeId} style={{ padding: '3px 0', color }}>
                                                {i + 1}. {nodeId} — {r?.message}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* ── Toast ────────────────────────────────────── */}
            {toast && (
                <div className={`swp-toast swp-toast--${toast.type}`}>
                    {toast.message}
                </div>
            )}
        </div>
    );
}
