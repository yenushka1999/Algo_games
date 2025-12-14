<?php
//  UNIT TEST FOR TSP ALGORITHM


function tsp_bruteforce(array $matrix, int $start): int
{
    $n = count($matrix);
    $cities = range(0, $n - 1);
    unset($cities[$start]);

    return permute(array_values($cities), $start, $matrix, $start, 0, PHP_INT_MAX);
}

function permute(array $cities, int $current, array $matrix, int $start, int $dist, int $best): int
{
    if ($dist >= $best) return $best;

    if (empty($cities)) {
        return min($best, $dist + $matrix[$current][$start]);
    }

    foreach ($cities as $i => $city) {
        $remaining = $cities;
        unset($remaining[$i]);
        $best = permute(
            array_values($remaining),
            $city,
            $matrix,
            $start,
            $dist + $matrix[$current][$city],
            $best
        );
    }

    return $best;
}

/* ---------- TEST CASES ---------- */

$tests = [
    [
        'name' => '4 City Matrix',
        'matrix' => [
            [0,10,15,20],
            [10,0,35,25],
            [15,35,0,30],
            [20,25,30,0]
        ],
        'start' => 0,
        'expected' => 80
    ],
    [
        'name' => '3 Equal Distance Cities',
        'matrix' => [
            [0,5,5],
            [5,0,5],
            [5,5,0]
        ],
        'start' => 0,
        'expected' => 15
    ]
];

/* ---------- RUN TESTS ---------- */

echo "Running TSP Unit Tests...\n\n";

foreach ($tests as $t) {
    $result = tsp_bruteforce($t['matrix'], $t['start']);
    echo "Test: {$t['name']} → ";

    if ($result === $t['expected']) {
        echo "PASS ✅ (Distance: $result)\n";
    } else {
        echo "FAIL ❌ (Got $result, Expected {$t['expected']})\n";
    }
}

echo "\nAll tests completed.\n";
