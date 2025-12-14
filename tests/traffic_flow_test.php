<?php
/**
 * UNIT TEST: Traffic Simulation – Maximum Flow
 * Algorithm: Edmonds–Karp
 */

/* ---------- BUILD GRAPH ---------- */
function buildGraph(array $edges): array
{
    $graph = [];

    foreach ($edges as [$u, $v, $cap]) {
        if (!isset($graph[$u])) $graph[$u] = [];
        if (!isset($graph[$v])) $graph[$v] = [];

        $graph[$u][$v] = $cap;
        $graph[$v][$u] = 0;
    }

    return $graph;
}

/* ---------- BFS ---------- */
function bfs(array $graph, string $source, string $sink, array &$parent): bool
{
    $queue = [$source];
    $visited = [$source => true];
    $parent = [];

    while ($queue) {
        $u = array_shift($queue);
        foreach ($graph[$u] as $v => $cap) {
            if ($cap > 0 && !isset($visited[$v])) {
                $parent[$v] = $u;
                $visited[$v] = true;
                if ($v === $sink) return true;
                $queue[] = $v;
            }
        }
    }
    return false;
}

/* ---------- EDMONDS–KARP ---------- */
function maxFlow(array $graph, string $source, string $sink): int
{
    $flow = 0;
    $parent = [];

    while (bfs($graph, $source, $sink, $parent)) {
        $pathFlow = PHP_INT_MAX;

        for ($v = $sink; $v !== $source; $v = $parent[$v]) {
            $u = $parent[$v];
            $pathFlow = min($pathFlow, $graph[$u][$v]);
        }

        for ($v = $sink; $v !== $source; $v = $parent[$v]) {
            $u = $parent[$v];
            $graph[$u][$v] -= $pathFlow;
            $graph[$v][$u] += $pathFlow;
        }

        $flow += $pathFlow;
    }

    return $flow;
}

/* ---------- TEST NETWORK ---------- */
/*
A → B (10)
A → C (10)
B → D (4)
C → D (8)
D → T (10)

Bottleneck: D → T (10)
Expected Max Flow = 10
*/

$edges = [
    ['A','B',10],
    ['A','C',10],
    ['B','D',4],
    ['C','D',8],
    ['D','T',10],
];

/* ---------- RUN TEST ---------- */

echo "Running Traffic Simulation Unit Test...\n\n";

$graph = buildGraph($edges);
$result = maxFlow($graph, 'A', 'T');

echo "Computed Max Flow → $result\n";

if ($result === 10) {
    echo "\nPASS ✅ Maximum Flow is correct\n";
} else {
    echo "\nFAIL ❌ Expected 10, got $result\n";
}

echo "\nAll tests completed.\n";
