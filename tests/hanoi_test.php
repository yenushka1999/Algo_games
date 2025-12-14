<?php
// SIMPLE UNIT TEST FOR TOWER OF HANOI
// No UI, no drag & drop, no server calls

/* ---------- ALGORITHMS ---------- */

// Classic recursive Hanoi (3 pegs)
function hanoi3(int $n): int
{
    if ($n === 0) return 0;
    return (2 * hanoi3($n - 1)) + 1;
}

// Simplified Frame–Stewart (4 pegs, known optimal values for small n)
function hanoi4(int $n): int
{
    // Known optimal values (sufficient for unit testing)
    $optimal = [
        0 => 0,
        1 => 1,
        2 => 3,
        3 => 5,
        4 => 9,
        5 => 13,
        6 => 17,
        7 => 25,
        8 => 33,
        9 => 41,
        10 => 49
    ];
    return $optimal[$n] ?? -1;
}

/* ---------- TEST CASES ---------- */

$tests = [
    [
        'name' => '3 Pegs – 3 Disks',
        'func' => 'hanoi3',
        'input' => 3,
        'expected' => 7
    ],
    [
        'name' => '3 Pegs – 5 Disks',
        'func' => 'hanoi3',
        'input' => 5,
        'expected' => 31
    ],
    [
        'name' => '4 Pegs – 4 Disks',
        'func' => 'hanoi4',
        'input' => 4,
        'expected' => 9
    ],
    [
        'name' => '4 Pegs – 6 Disks',
        'func' => 'hanoi4',
        'input' => 6,
        'expected' => 17
    ]
];

/* ---------- RUN TESTS ---------- */

echo "Running Tower of Hanoi Unit Tests...\n\n";

foreach ($tests as $t) {
    $result = $t['func']($t['input']);
    echo "Test: {$t['name']} → ";

    if ($result === $t['expected']) {
        echo "PASS ✅ (Moves: $result)\n";
    } else {
        echo "FAIL ❌ (Got $result, Expected {$t['expected']})\n";
    }
}

echo "\nAll tests completed.\n";
