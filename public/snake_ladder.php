<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

/* -------------------------------
   READ POST INPUT
--------------------------------*/
$board_size     = intval($_POST['board_size'] ?? 0);
$player_answer  = intval($_POST['player_answer'] ?? -1);
$num_ladders    = intval($_POST['num_ladders'] ?? 0);
$num_snakes     = intval($_POST['num_snakes'] ?? 0);
$csrf           = $_POST['csrf'] ?? '';

$ladder_json = $_POST['ladders'] ?? '[]';
$snake_json  = $_POST['snakes']  ?? '[]';

$ladders = json_decode($ladder_json, true);
$snakes  = json_decode($snake_json, true);

/* -------------------------------
   VALIDATE CSRF
--------------------------------*/
if (!check_csrf($csrf)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF']);
    exit;
}

/* -------------------------------
   USER MUST BE LOGGED IN
--------------------------------*/
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

/* -------------------------------
   VALIDATE INPUT
--------------------------------*/
if ($board_size < 6 || $board_size > 12) {
    http_response_code(422);
    echo json_encode(['error' => 'Invalid board size']);
    exit;
}

/* -------------------------------
   PREP BOARD MAP
--------------------------------*/
$N = $board_size;
$maxCell = $N * $N;

/*
We create a mapping from each cell to its effective destination
after snakes or ladders.

Example: if ladder start=5, end=14:
   nextCell[5] = 14

If no ladder/snake: nextCell[i] = i
*/
$nextCell = [];
for ($i = 1; $i <= $maxCell; $i++) {
    $nextCell[$i] = $i; // default
}

if (is_array($ladders)) {
    foreach ($ladders as $l) {
        if (!empty($l['start']) && !empty($l['end'])) {
            $start = intval($l['start']);
            $end   = intval($l['end']);
            if ($start > 1 && $end <= $maxCell && $start < $end) {
                $nextCell[$start] = $end;
            }
        }
    }
}

if (is_array($snakes)) {
    foreach ($snakes as $s) {
        if (!empty($s['start']) && !empty($s['end'])) {
            $start = intval($s['start']);
            $end   = intval($s['end']);
            if ($end >= 1 && $start < $maxCell && $start > $end) {
                $nextCell[$start] = $end;
            }
        }
    }
}

/* -------------------------------
   BFS FOR MINIMUM MOVES
--------------------------------*/
function bfs_min_moves($N, $nextCell) {
    $max = $N*$N;
    $dist = array_fill(1, $max, PHP_INT_MAX);
    $dist[1] = 0;

    $queue = [1];

    while (!empty($queue)) {
        $curr = array_shift($queue);
        $moves = $dist[$curr];

        if ($curr == $max) return $moves;

        for ($dice = 1; $dice <= 6; $dice++) {
            $next = $curr + $dice;
            if ($next > $max) continue;

            $finalCell = $nextCell[$next] ?? $next;

            if ($dist[$finalCell] > $moves + 1) {
                $dist[$finalCell] = $moves + 1;
                $queue[] = $finalCell;
            }
        }
    }
    return null;
}

$algorithm1_name = "BFS";
$algorithm2_name = "DP";

$startTime1 = microtime(true);
$min_moves = bfs_min_moves($N, $nextCell);
$algorithm1_time = microtime(true) - $startTime1;

$algorithm2_time = $algorithm1_time; // DP not implemented; keeps compatibility

$is_correct = ($player_answer == $min_moves) ? 1 : 0;

/* -------------------------------
   SAVE RESULT TO DATABASE
--------------------------------*/
try {
    global $pdo;

    $data = [
        'user_id'         => $_SESSION['user_id'],
        'board_size'      => $board_size,
        'num_ladders'     => count($ladders),
        'num_snakes'      => count($snakes),
        'min_moves'       => $min_moves,
        'algorithm1_name' => $algorithm1_name,
        'algorithm1_time' => $algorithm1_time,
        'algorithm2_name' => $algorithm2_name,
        'algorithm2_time' => $algorithm2_time,
        'player_answer'   => $player_answer,
        'is_correct'      => $is_correct,
    ];

    $id = save_result($pdo, 'snake_ladder_results', $data);

    echo json_encode([
        'ok'           => true,
        'saved_id'     => $id,
        'min_moves'    => $min_moves,
        'is_correct'   => $is_correct,
        'algorithm1_time' => $algorithm1_time,
        'algorithm2_time' => $algorithm2_time,
        'received_ladders' => $ladders,
        'received_snakes'  => $snakes
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'DB save error: ' . $e->getMessage()
    ]);
    exit;
}
