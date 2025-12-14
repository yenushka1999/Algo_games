<?php
// SIMPLE UNIT TEST FOR SNAKE & LADDER
// Tests BFS and DP minimum dice throw logic
// No UI, no randomness, no existing file changes

function bfsMinMoves(int $N, array $ladders, array $snakes): ?int
{
    $max = $N * $N;

    // next[cell] = destination after snake/ladder
    $next = [];
    for ($i = 1; $i <= $max; $i++) $next[$i] = $i;

    foreach ($ladders as $l) $next[$l['start']] = $l['end'];
    foreach ($snakes as $s)  $next[$s['start']] = $s['end'];

    $dist = array_fill(0, $max + 1, PHP_INT_MAX);
    $dist[1] = 0;

    $q = [1];
    while ($q) {
        $cur = array_shift($q);
        for ($d = 1; $d <= 6; $d++) {
            $n = $cur + $d;
            if ($n > $max) continue;

            $f = $next[$n];
            if ($dist[$f] > $dist[$cur] + 1) {
                $dist[$f] = $dist[$cur] + 1;
                $q[] = $f;
            }
        }
    }

    return $dist[$max] === PHP_INT_MAX ? null : $dist[$max];
}

function dpMinMoves(int $N, array $ladders, array $snakes): ?int
{
    $max = $N * $N;

    $next = [];
    for ($i = 1; $i <= $max; $i++) $next[$i] = $i;

    foreach ($ladders as $l) $next[$l['start']] = $l['end'];
    foreach ($snakes as $s)  $next[$s['start']] = $s['end'];

    $dp = array_fill(0, $max + 1, INF);
    $dp[1] = 0;

    $changed = true;
    while ($changed) {
        $changed = false;
        for ($i = 1; $i <= $max; $i++) {
            if ($dp[$i] === INF) continue;
            for ($d = 1; $d <= 6; $d++) {
                $n = $i + $d;
                if ($n > $max) continue;
                $n = $next[$n];
                if ($dp[$n] > $dp[$i] + 1) {
                    $dp[$n] = $dp[$i] + 1;
                    $changed = true;
                }
            }
        }
    }

    return is_infinite($dp[$max]) ? null : $dp[$max];
}

/* ---------- TEST CASES ---------- */

$tests = [
    [
        'name' => 'Simple 6x6 board – no snakes or ladders',
        'N' => 6,
        'ladders' => [],
        'snakes' => [],
        'expected' => bfsMinMoves(6, [], [])
    ],
    [
        'name' => '6x6 with one ladder',
        'N' => 6,
        'ladders' => [
            ['start' => 2, 'end' => 15]
        ],
        'snakes' => [],
        'expected' => bfsMinMoves(6, [['start'=>2,'end'=>15]], [])
    ],
    [
        'name' => '6x6 with ladder and snake',
        'N' => 6,
        'ladders' => [
            ['start' => 3, 'end' => 22]
        ],
        'snakes' => [
            ['start' => 20, 'end' => 5]
        ],
        'expected' => bfsMinMoves(
            6,
            [['start'=>3,'end'=>22]],
            [['start'=>20,'end'=>5]]
        )
    ]
];

/* ---------- RUN TESTS ---------- */

echo "Running Snake & Ladder Unit Tests...\n\n";

foreach ($tests as $t) {
    $bfs = bfsMinMoves($t['N'], $t['ladders'], $t['snakes']);
    $dp  = dpMinMoves($t['N'], $t['ladders'], $t['snakes']);

    echo "Test: {$t['name']} → ";

    if ($bfs === $dp) {
        echo "PASS ✅ (Min throws: $bfs)\n";
    } else {
        echo "FAIL ❌ (BFS=$bfs, DP=$dp)\n";
    }
}

echo "\nAll tests completed.\n";
