/**
 * Frontend graph validation before saving.
 * Returns an array of error strings. Empty = valid.
 */
export function validateGraph(nodes, edges) {
    const errors = [];

    if (nodes.length === 0) {
        errors.push('Graph must contain at least one node.');
        return errors;
    }

    // Exactly one trigger node
    const triggerNodes = nodes.filter((n) => n.data.nodeType === 'trigger');
    if (triggerNodes.length === 0) {
        errors.push('Workflow must have exactly one trigger node.');
    } else if (triggerNodes.length > 1) {
        errors.push(`Workflow must have exactly one trigger node, found ${triggerNodes.length}.`);
    }

    // All edges reference existing nodes
    const nodeIds = new Set(nodes.map((n) => n.id));
    for (const edge of edges) {
        if (!nodeIds.has(edge.source)) {
            errors.push(`Edge source "${edge.source}" references a missing node.`);
        }
        if (!nodeIds.has(edge.target)) {
            errors.push(`Edge target "${edge.target}" references a missing node.`);
        }
    }

    // Cycle detection via DFS
    if (hasCycle(nodes, edges)) {
        errors.push('Workflow contains a cycle. Nodes cannot loop back to earlier nodes.');
    }

    return errors;
}

function hasCycle(nodes, edges) {
    const adjacency = {};
    for (const node of nodes) {
        adjacency[node.id] = [];
    }
    for (const edge of edges) {
        if (adjacency[edge.source]) {
            adjacency[edge.source].push(edge.target);
        }
    }

    const visited = new Set();
    const inStack = new Set();

    function dfs(nodeId) {
        visited.add(nodeId);
        inStack.add(nodeId);

        for (const neighbor of adjacency[nodeId] || []) {
            if (!visited.has(neighbor)) {
                if (dfs(neighbor)) return true;
            } else if (inStack.has(neighbor)) {
                return true;
            }
        }

        inStack.delete(nodeId);
        return false;
    }

    for (const node of nodes) {
        if (!visited.has(node.id)) {
            if (dfs(node.id)) return true;
        }
    }

    return false;
}
