import { useState, useCallback, useRef, useMemo } from 'react';
import {
    ReactFlow,
    MiniMap,
    Background,
    useNodesState,
    useEdgesState,
    addEdge,
    BaseEdge,
    EdgeLabelRenderer,
    getSmoothStepPath,
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

function InsertButtonEdge({ id, sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition, style, data }) {
    const [path, labelX, labelY] = getSmoothStepPath({ sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition });
    const showMenu = data?.insertEdgeId === id;
    const isHovered = data?.hoveredEdgeId === id;

    return (
        <>
            <BaseEdge path={path} style={style} interactionWidth={30} />
            <EdgeLabelRenderer>
                <div
                    className="swp-edge-label"
                    style={{
                        transform: `translate(-50%, -50%) translate(${labelX}px,${labelY}px)`,
                    }}
                >
                    <button
                        className={`swp-edge-add-btn ${isHovered || showMenu ? 'swp-edge-add-btn--visible' : ''} ${showMenu ? 'swp-edge-add-btn--active' : ''}`}
                        onClick={(e) => { e.stopPropagation(); data?.onInsertClick(id); }}
                    >
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round">
                            <line x1="12" y1="5" x2="12" y2="19" />
                            <line x1="5" y1="12" x2="19" y2="12" />
                        </svg>
                    </button>
                    {showMenu && (
                        <div className="swp-edge-insert-menu">
                            <button className="swp-edge-insert-item" onClick={() => data?.onInsert(id, 'condition')}>
                                <span className="swp-edge-insert-icon" style={{ background: 'var(--swp-condition)' }}>
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5" strokeLinecap="round"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
                                </span>
                                Condition
                            </button>
                            <button className="swp-edge-insert-item" onClick={() => data?.onInsert(id, 'action')}>
                                <span className="swp-edge-insert-icon" style={{ background: 'var(--swp-action)' }}>
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                </span>
                                Action
                            </button>
                            <button className="swp-edge-insert-item" onClick={() => data?.onInsert(id, 'delay')}>
                                <span className="swp-edge-insert-icon" style={{ background: 'var(--swp-delay)' }}>
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#fff" strokeWidth="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                </span>
                                Delay
                            </button>
                        </div>
                    )}
                </div>
            </EdgeLabelRenderer>
        </>
    );
}

const edgeTypes = { insertButton: InsertButtonEdge };

const NODE_TYPE_TO_COMPONENT = {
    trigger: 'triggerNode',
    condition: 'conditionNode',
    action: 'actionNode',
    delay: 'delayNode',
};

export default function App({ initialGraph, workflowId, apiUrl, initialName, initialStatus, runsUrl, backUrl, productSearchUrl }) {
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
    const [insertEdgeId, setInsertEdgeId] = useState(null);
    const [hoveredEdgeId, setHoveredEdgeId] = useState(null);
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
                type: 'insertButton',
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
        setInsertEdgeId(null);
    }, []);

    // ── Insert node on edge ─────────────────────────────────

    const insertNodeOnEdge = useCallback(
        (edgeId, nodeType) => {
            const edge = edges.find((e) => e.id === edgeId);
            if (!edge) return;

            const sourceNode = nodes.find((n) => n.id === edge.source);
            const targetNode = nodes.find((n) => n.id === edge.target);
            if (!sourceNode || !targetNode) return;

            const id = generateNodeId();
            const midX = (sourceNode.position.x + targetNode.position.x) / 2;
            const midY = (sourceNode.position.y + targetNode.position.y) / 2;

            setNodes((nds) => [...nds, {
                id,
                type: NODE_TYPE_TO_COMPONENT[nodeType],
                position: { x: midX, y: midY },
                data: { nodeType, config: {}, label: '' },
            }]);

            setEdges((eds) => {
                const filtered = eds.filter((e) => e.id !== edgeId);
                return [
                    ...filtered,
                    { id: generateEdgeId(edge.source, id), source: edge.source, target: id, type: 'insertButton', animated: false, style: { stroke: '#94A3B8', strokeWidth: 1.5 } },
                    { id: generateEdgeId(id, edge.target), source: id, target: edge.target, type: 'insertButton', animated: false, style: { stroke: '#94A3B8', strokeWidth: 1.5 } },
                ];
            });

            setSelectedNodeId(id);
            setInsertEdgeId(null);
        },
        [edges, nodes, setNodes, setEdges]
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
            if (nodeType === 'trigger' && nodes.some((n) => n.data.nodeType === 'trigger')) {
                showToast('error', 'A workflow can only have one trigger node.');
                setAddMenuOpen(false);
                return;
            }

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
        setNodes((nds) => {
            const nodeMap = {};
            nds.forEach((n) => { nodeMap[n.id] = n; });

            // Build adjacency per handle
            const adj = {}; // id -> [{ target, handle }]
            const incoming = {};
            nds.forEach((n) => { adj[n.id] = []; incoming[n.id] = 0; });
            edges.forEach((e) => {
                if (adj[e.source]) adj[e.source].push({ target: e.target, handle: e.sourceHandle || null });
                if (incoming[e.target] !== undefined) incoming[e.target]++;
            });

            // Tree layout via DFS from root
            const roots = Object.keys(incoming).filter((id) => incoming[id] === 0);
            const posMap = {};
            const COL_W = 300;
            const ROW_H = 160;
            const visited = new Set();

            function layout(nodeId, row, col) {
                if (visited.has(nodeId)) return col;
                visited.add(nodeId);
                posMap[nodeId] = { x: col * COL_W, y: row * ROW_H + 40 };

                const children = adj[nodeId] || [];
                const node = nodeMap[nodeId];
                const isCondition = node && node.data.nodeType === 'condition';

                if (isCondition && children.length >= 2) {
                    // Find true/false branches
                    const trueBranch = children.find((c) => c.handle === 'exit-true');
                    const falseBranch = children.find((c) => c.handle === 'exit-false');

                    let nextCol = col;
                    if (trueBranch && !visited.has(trueBranch.target)) {
                        nextCol = layout(trueBranch.target, row + 1, col - 1);
                    }
                    if (falseBranch && !visited.has(falseBranch.target)) {
                        nextCol = layout(falseBranch.target, row + 1, Math.max(nextCol + 1, col + 1));
                    }
                    return nextCol;
                } else {
                    // Linear: lay out children sequentially
                    let nextCol = col;
                    for (const child of children) {
                        if (!visited.has(child.target)) {
                            nextCol = layout(child.target, row + 1, col);
                        }
                    }
                    return nextCol;
                }
            }

            let startCol = 1;
            for (const root of roots) {
                startCol = layout(root, 0, startCol);
            }

            // Place any unvisited nodes at the end
            let extraRow = Object.keys(posMap).length;
            nds.forEach((n) => {
                if (!posMap[n.id]) {
                    posMap[n.id] = { x: COL_W, y: extraRow * ROW_H + 40 };
                    extraRow++;
                }
            });

            return nds.map((node) => ({
                ...node,
                position: posMap[node.id] || node.position,
            }));
        });
    }, [setNodes, edges]);

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

    // ── Enrich edges with insert callbacks ──────────────────

    const edgesWithData = useMemo(() =>
        edges.map((e) => ({
            ...e,
            data: {
                ...e.data,
                insertEdgeId,
                hoveredEdgeId,
                onInsertClick: (edgeId) => setInsertEdgeId((prev) => prev === edgeId ? null : edgeId),
                onInsert: insertNodeOnEdge,
            },
        })),
        [edges, insertEdgeId, hoveredEdgeId, insertNodeOnEdge]
    );

    return (
        <div className="swp-editor" onKeyDown={onKeyDown} tabIndex={0}>
            {/* ── Toolbar ──────────────────────────────────── */}
            <div className="swp-toolbar">
                <div className="swp-toolbar__left">
                    {backUrl && (
                        <a href={backUrl} className="swp-btn swp-btn--back" title="Back">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <polyline points="15 18 9 12 15 6" />
                            </svg>
                        </a>
                    )}
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
                        edges={edgesWithData}
                        nodeTypes={nodeTypes}
                        edgeTypes={edgeTypes}
                        onNodesChange={onNodesChange}
                        onEdgesChange={onEdgesChange}
                        onConnect={onConnect}
                        onNodeClick={onNodeClick}
                        onPaneClick={onPaneClick}
                        onEdgeMouseEnter={useCallback((_e, edge) => setHoveredEdgeId(edge.id), [])}
                        onEdgeMouseLeave={useCallback(() => setHoveredEdgeId(null), [])}
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
                        productSearchUrl={productSearchUrl}
                    />
                )}
            </div>

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
