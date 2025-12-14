<?php
// SIMPLE UNIT TEST FOR EIGHT QUEENS VALIDATION
// No UI, no timers, no DOM, no changes to game files

function isValidQueens(array $queens): bool
{
    $N = count($queens);

    for ($r = 0; $r < $N; $r++) {
        if ($queens[$r] === null) return false;

        for ($r2 = 0; $r2 < $r; $r2++) {
            // Same column
            if ($queens[$r] === $queens[$r2]) return false;

            // Diagonal conflict
            if (abs($queens[$r] - $queens[$r2]) === abs($r - $r2)) return false;
        }
    }
    return true;
}

/* ---------- TEST CASES ---------- */

$tests = [
    [
        'name' => 'Correct Solution',
        'queens' => [0,4,7,5,2,6,1,3],
        'expected' => true
    ],
    [
        'name' => 'Same Column Conflict',
        'queens' => [0,0,7,5,2,6,1,3],
        'expected' => false
    ],
    [
        'name' => 'Diagonal Conflict',
        'queens' => [0,4,7,5,2,6,3,1],
        'expected' => false
    ],
    [
        'name' => 'Incomplete Board',
        'queens' => [0,4,null,5,2,6,1,3],
        'expected' => false
    ]
];

/* ---------- RUN TESTS ---------- */

echo "Running Eight Queens Unit Tests...\n\n";

foreach ($tests as $t) {
    $result = isValidQueens($t['queens']);
    echo "Test: {$t['name']} → ";

    if ($result === $t['expected']) {
        echo "PASS ✅\n";
    } else {
        echo "FAIL ❌ (Expected ";
        echo $t['expected'] ? 'true' : 'false';
        echo ")\n";
    }
}

echo "\nAll tests completed.\n";
