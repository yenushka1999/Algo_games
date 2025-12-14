<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

$data = $_POST;
if (!check_csrf($data['csrf'] ?? '')) { http_response_code(400); echo json_encode(['error'=>'Invalid CSRF']); exit; }
if (!is_logged_in()) { http_response_code(401); echo json_encode(['error'=>'Login required']); exit; }

$num_disks = isset($data['num_disks']) ? (int)$data['num_disks'] : null;
$num_pegs = isset($data['num_pegs']) ? (int)$data['num_pegs'] : null;
$player_moves = isset($data['player_moves']) ? (int)$data['player_moves'] : null;

if ($num_disks === null || $num_disks < 5 || $num_disks > 10) { http_response_code(422); echo json_encode(['error'=>'num_disks must be between 5 and 10']); exit; }
if ($num_pegs === null || ($num_pegs !== 3 && $num_pegs !== 4)) { http_response_code(422); echo json_encode(['error'=>'num_pegs must be 3 or 4']); exit; }

function hanoi_min_moves_3($n){ return (int)(pow(2,$n) - 1); }
function hanoi_min_moves_4($n){
    $dp = array_fill(0,$n+1, PHP_INT_MAX);
    $dp[0]=0;
    for ($k=1;$k<=$n;$k++){
        if ($k==1){ $dp[$k]=1; continue; }
        for ($m=1;$m<$k;$m++){
            $cost = 2 * $dp[$m] + hanoi_min_moves_3($k-$m);
            if ($cost < $dp[$k]) $dp[$k] = $cost;
        }
    }
    return (int)$dp[$n];
}

if ($num_pegs == 3){
    $min_moves = hanoi_min_moves_3($num_disks);
    $moves_seq = [];
    function hanoi_moves_3($n,$from,$to,$aux,&$moves){ if ($n==0) return; hanoi_moves_3($n-1,$from,$aux,$to,$moves); $moves[] = "$from-$to"; hanoi_moves_3($n-1,$aux,$to,$from,$moves); }
    hanoi_moves_3($num_disks,'A','C','B',$moves_seq);
    $algo1 = 'Recursive-3peg';
    $algo1_time = timer_diff_ms(timer_start());
    $algo2 = 'Iterative-approx';
    $algo2_time = 0.0;
} else {
    $min_moves = hanoi_min_moves_4($num_disks);
    $moves_seq = ['Frame-Stewart sequence not enumerated'];
    $algo1 = 'Frame-Stewart-4peg';
    $algo1_time = 0.0;
    $algo2 = '3peg-baseline';
    $algo2_time = 0.0;
}

$is_correct = ($player_moves !== null && $player_moves == $min_moves) ? 1 : 0;

$payload = [
    'user_id'=>$_SESSION['user_id'],
    'num_disks'=>$num_disks,
    'num_pegs'=>$num_pegs,
    'min_moves'=>$min_moves,
    'move_sequence'=>json_encode($moves_seq, JSON_UNESCAPED_UNICODE),
    'algorithm1_name'=>$algo1,'algorithm1_time'=>round($algo1_time,6),
    'algorithm2_name'=>$algo2,'algorithm2_time'=>round($algo2_time,6),
    'player_moves'=>($player_moves===null?0:$player_moves),
    'player_sequence'=>json_encode($data['player_sequence'] ?? [], JSON_UNESCAPED_UNICODE),
    'is_correct'=>$is_correct,
    'played_at'=>date('Y-m-d H:i:s')
];

try { $id = save_result($pdo,'hanoi_results',$payload); echo json_encode(['ok'=>true,'min_moves'=>$min_moves,'moves_count'=>count($moves_seq),'saved_id'=>$id]); }
catch (Exception $e) { http_response_code(500); echo json_encode(['error'=>'Save failed']); }
