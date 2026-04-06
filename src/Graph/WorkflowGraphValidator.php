<?php

declare(strict_types=1);

namespace Abderrahim\SyliusWorkflowPlugin\Graph;

use Abderrahim\SyliusWorkflowPlugin\Enum\NodeType;

final class WorkflowGraphValidator
{
    /**
     * @return string[] Array of validation error messages
     */
    public function validate(array $graph): array
    {
        $errors = [];

        // Limit total graph payload size to prevent excessive storage
        $graphSize = \strlen(json_encode($graph, JSON_THROW_ON_ERROR));
        if ($graphSize > 512_000) {
            $errors[] = sprintf('Graph payload exceeds maximum size (500KB). Size: %dKB.', (int) ($graphSize / 1024));
            return $errors;
        }

        $nodes = $graph['nodes'] ?? [];
        $edges = $graph['edges'] ?? [];

        if (empty($nodes)) {
            $errors[] = 'Graph must contain at least one node.';
            return $errors;
        }

        if (\count($nodes) > 50) {
            $errors[] = sprintf('Graph exceeds maximum node limit (50). Found %d nodes.', \count($nodes));
            return $errors;
        }

        if (\count($edges) > 100) {
            $errors[] = sprintf('Graph exceeds maximum edge limit (100). Found %d edges.', \count($edges));
            return $errors;
        }

        $nodeIds = array_column($nodes, 'id');
        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeMap[$node['id']] = $node;
        }

        // Exactly one trigger node
        $triggerNodes = array_filter($nodes, fn (array $node) => ($node['type'] ?? '') === NodeType::Trigger->value);
        if (\count($triggerNodes) === 0) {
            $errors[] = 'Graph must contain exactly one trigger node.';
        } elseif (\count($triggerNodes) > 1) {
            $errors[] = 'Graph must contain exactly one trigger node, found ' . \count($triggerNodes) . '.';
        }

        // All edges must connect to existing node IDs
        foreach ($edges as $index => $edge) {
            if (!isset($edge['source']) || !isset($edge['target'])) {
                $errors[] = sprintf('Edge at index %d is missing source or target.', $index);
                continue;
            }
            if (!\in_array($edge['source'], $nodeIds, true)) {
                $errors[] = sprintf('Edge source "%s" does not match any node ID.', $edge['source']);
            }
            if (!\in_array($edge['target'], $nodeIds, true)) {
                $errors[] = sprintf('Edge target "%s" does not match any node ID.', $edge['target']);
            }
        }

        // No dangling nodes (except the last action node which has no outgoing edges)
        $sourcesInEdges = array_column($edges, 'source');
        $targetsInEdges = array_column($edges, 'target');
        foreach ($nodes as $node) {
            $id = $node['id'];
            $type = $node['type'] ?? '';
            $isSource = \in_array($id, $sourcesInEdges, true);
            $isTarget = \in_array($id, $targetsInEdges, true);

            if ($type === NodeType::Trigger->value) {
                // Trigger nodes should not be targets (they are entry points)
                if (!$isSource && \count($nodes) > 1) {
                    $errors[] = sprintf('Trigger node "%s" has no outgoing edges.', $id);
                }
            } elseif (!$isTarget) {
                $errors[] = sprintf('Node "%s" has no incoming edges (dangling node).', $id);
            }
        }

        // Cycle detection via DFS
        if ($this->hasCycle($nodeIds, $edges)) {
            $errors[] = 'Graph contains a cycle.';
        }

        return $errors;
    }

    private function hasCycle(array $nodeIds, array $edges): bool
    {
        $adjacency = [];
        foreach ($nodeIds as $id) {
            $adjacency[$id] = [];
        }
        foreach ($edges as $edge) {
            if (isset($edge['source'], $edge['target'])) {
                $adjacency[$edge['source']][] = $edge['target'];
            }
        }

        $visited = [];
        $inStack = [];

        foreach ($nodeIds as $nodeId) {
            if (!isset($visited[$nodeId]) && $this->dfs($nodeId, $adjacency, $visited, $inStack)) {
                return true;
            }
        }

        return false;
    }

    private function dfs(string $nodeId, array &$adjacency, array &$visited, array &$inStack): bool
    {
        $visited[$nodeId] = true;
        $inStack[$nodeId] = true;

        foreach ($adjacency[$nodeId] ?? [] as $neighbor) {
            if (!isset($visited[$neighbor])) {
                if ($this->dfs($neighbor, $adjacency, $visited, $inStack)) {
                    return true;
                }
            } elseif (isset($inStack[$neighbor])) {
                return true;
            }
        }

        unset($inStack[$nodeId]);

        return false;
    }
}
