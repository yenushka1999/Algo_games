<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

$data = $_POST;
if (!check_csrf($data['csrf'] ?? '')) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid CSRF']);
    exit;
}
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error'=>'Login required']);
    exit;
}

$find_one = isset($data['find_one']) ? (bool)$data['find_one'] : false;
$N = 8;
$solutions = [];

/* ---------------- EXISTING SOLVER (UNCHANGED) ---------------- */

function is_safe($row,$col,$board){
    for ($r=0;$r<$row;$r++){
        $c = $board[$r];
        if ($c == $col) return false;
        if (abs($c - $col) == ($row - $r)) return false;
    }
    return true;
}
function solve_nq($row,&$board,&$solutions,$N,$stop_after_one=false){
    if ($row == $N) {
        $solutions[] = $board;
        return $stop_after_one && count($solutions) >= 1;
    }
    for ($col=0;$col<$N;$col++){
        if (is_safe($row,$col,$board)){
            $board[$row] = $col;
            $should_stop = solve_nq($row+1,$board,$solutions,$N,$stop_after_one);
            if ($should_stop) return true;
        }
    }
    return false;
}

$start = timer_start();
$board = array_fill(0,$N,-1);
solve_nq(0,$board,$solutions,$N,$find_one);
$time_seq = timer_diff_ms($start);

/* ---------------- NEW PART (MINIMAL & SAFE) ---------------- */

/* Expect player solution from UI */
if (!isset($data['player_solution'])) {
    http_response_code(400);
    echo json_encode(['error'=>'Missing player solution']);
    exit;
}

$playerSolution = json_decode($data['player_solution'], true);

if (!is_array($playerSolution) || count($playerSolution) !== 8) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid solution format']);
    exit;
}

/* Validate player solution */
for ($r=0;$r<8;$r++){
    for ($r2=0;$r2<$r;$r2++){
        if ($playerSolution[$r] === $playerSolution[$r2]) {
            http_response_code(400);
            echo json_encode(['error'=>'Invalid solution (column conflict)']);
            exit;
        }
        if (abs($playerSolution[$r] - $playerSolution[$r2]) === ($r - $r2)) {
            http_response_code(400);
            echo json_encode(['error'=>'Invalid solution (diagonal conflict)']);
            exit;
        }
    }
}

/* ---------------- EXISTING SOLUTION TABLE LOGIC (UNCHANGED) ---------------- */

$inserted = [];
$json_sol = json_encode($playerSolution, JSON_UNESCAPED_UNICODE);
$hash = hash('sha256', $json_sol);

$stmt = $pdo->prepare(
    'SELECT solution_id FROM eight_queens_solutions WHERE solution_hash = :h LIMIT 1'
);
$stmt->execute([':h'=>$hash]);

if (!$stmt->fetch()) {
    $ins = $pdo->prepare(
        'INSERT INTO eight_queens_solutions
         (solution_hash, solution, is_found, found_by_user_id, found_at)
         VALUES (:h,:s,1,:u,:fa)'
    );
    $ins->execute([
        ':h'=>$hash,
        ':s'=>$json_sol,
        ':u'=>$_SESSION['user_id'],
        ':fa'=>date('Y-m-d H:i:s')
    ]);
    $inserted[] = $pdo->lastInsertId();
}

/* ---------------- PAYLOAD (ONLY ONE LINE CHANGED) ---------------- */

$payload = [
    'user_id'          => $_SESSION['user_id'],
    'solution'         => $json_sol,   // ✅ PLAYER SOLUTION SAVED
    'total_solutions'  => 92,
    'is_duplicate'     => 0,
    'sequential_time'  => round($time_seq,6),
    'threaded_time'    => null,
    'played_at'        => date('Y-m-d H:i:s')
];

try {
    $id = save_result($pdo,'eight_queens_results',$payload);
    echo json_encode([
        'ok'=>true,
        'saved_id'=>$id,
        'seq_time'=>round($time_seq,6),
        'inserted'=>$inserted
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'Save failed']);
}
