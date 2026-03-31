/**
 * Converts backend graph JSON {nodes, edges} to React Flow format.
 */
export function deserializeGraph(graph) {
    const raw = typeof graph === 'string' ? JSON.parse(graph) : graph;
    const backendNodes = raw.nodes || [];
    const backendEdges = raw.edges || [];

    const nodes = backendNodes.map((node, index) => ({
        id: node.id,
        type: nodeTypeToComponent(node.type),
        position: node.position || { x: 300, y: 40 + index * 160 },
        data: {
            nodeType: node.type,
            config: node.config || {},
            label: '',
        },
    }));

    const edges = backendEdges.map((edge) => ({
        id: edge.id || `edge-${edge.source}-${edge.target}`,
        source: edge.source,
        target: edge.target,
        sourceHandle: edge.sourceHandle || null,
        targetHandle: edge.targetHandle || null,
        type: 'insertButton',
        animated: false,
        style: { stroke: '#94A3B8', strokeWidth: 1.5 },
        markerEnd: { type: 'arrowclosed', width: 16, height: 16, color: '#94A3B8' },
    }));

    return { nodes, edges };
}

/**
 * Converts React Flow nodes/edges back to backend graph JSON.
 */
export function serializeGraph(nodes, edges) {
    const backendNodes = nodes.map((node) => ({
        id: node.id,
        type: node.data.nodeType,
        config: node.data.config || {},
        position: node.position,
    }));

    const backendEdges = edges.map((edge) => ({
        id: edge.id,
        source: edge.source,
        target: edge.target,
        sourceHandle: edge.sourceHandle || null,
        targetHandle: edge.targetHandle || null,
    }));

    return { nodes: backendNodes, edges: backendEdges };
}

function nodeTypeToComponent(type) {
    switch (type) {
        case 'trigger': return 'triggerNode';
        case 'condition': return 'conditionNode';
        case 'action': return 'actionNode';
        case 'delay': return 'delayNode';
        default: return 'actionNode';
    }
}

let idCounter = 0;

export function generateNodeId() {
    idCounter++;
    return `node-${Date.now()}-${idCounter}`;
}

export function generateEdgeId(source, target) {
    return `edge-${source}-${target}`;
}
