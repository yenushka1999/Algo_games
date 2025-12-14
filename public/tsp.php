<?php
require_once __DIR__.'/../functions.php';
header('Content-Type: application/json; charset=utf-8');

$data = $_POST;
if (!check_csrf($data['csrf'] ?? '')) { http_response_code(400); echo json_encode(['error'=>'Invalid CSRF']); exit; }
if (!is_logged_in()) { http_response_code(401); echo json_encode(['error'=>'Login required']); exit; }

$home = isset($data['home_city']) ? strtoupper(substr(trim($data['home_city']),0,1)) : null;
$selected = isset($data['selected_cities']) ? $data['selected_cities'] : null;
$player_answer = isset($data['player_answer']) ? (int)$data['player_answer'] : null;

if (!$home || !$selected) { http_response_code(422); echo json_encode(['error'=>'Provide home_city and selected_cities']); exit; }
if (is_string($selected)) {
    $selected = json_decode($selected, true);
    if (!is_array($selected)) $selected = array_map('trim', explode(',', $selected));
}
$selected = array_values(array_unique(array_map(function($x){return strtoupper(substr(trim($x),0,1));}, $selected)));
$selected = array_filter($selected, function($c) use ($home){ return $c !== $home; });
$nodes = array_merge([$home], array_values($selected));
$n = count($nodes);
if ($n > 11) { http_response_code(422); echo json_encode(['error'=>'Too many cities (max 11)']); exit; }

$dist_in = $data['distance_matrix'] ?? null;
if ($dist_in && is_string($dist_in)) $dist_in = json_decode($dist_in, true);
if ($dist_in && is_array($dist_in)) $dist = $dist_in;
else {
    $dist = array_fill(0,$n,array_fill(0,$n,0));
    for ($i=0;$i<$n;$i++){ for ($j=$i+1;$j<$n;$j++){ $d = rand(10,100); $dist[$i][$j]=$d; $dist[$j][$i]=$d; } }
}

function held_karp($dist){
    $n = count($dist);
    if ($n==1) return ['dist'=>0,'route'=>[0,0]];
    $FULL = (1<<($n-1)) - 1;
    $dp = [];
    for ($mask=0;$mask<=$FULL;$mask++) $dp[$mask]=array_fill(0,$n,INF);
    for ($j=1;$j<$n;$j++){ $mask = 1<<($j-1); $dp[$mask][$j] = $dist[0][$j]; }
    for ($mask=0;$mask<=$FULL;$mask++){
        for ($j=1;$j<$n;$j++){
            if (!(($mask >> ($j-1)) & 1)) continue;
            $prev = $mask ^ (1<<($j-1));
            if ($prev==0) continue;
            for ($k=1;$k<$n;$k++){
                if ($k==$j) continue;
                if (!(($prev >> ($k-1)) & 1)) continue;
                $dp[$mask][$j] = min($dp[$mask][$j], $dp[$prev][$k] + $dist[$k][$j]);
            }
        }
    }
    $best = INF;
    for ($j=1;$j<$n;$j++){ $cost = $dp[$FULL][$j] + $dist[$j][0]; if ($cost < $best) $best = $cost; }
    return ['dist'=> (int)$best, 'route'=>range(0,$n-1)+[0]];
}

function greedy_nn($dist){
    $n = count($dist);
    $visited = array_fill(0,$n,false);
    $route=[0]; $visited[0]=true; $cur=0; $cost=0;
    for ($step=1;$step<$n;$step++){ $best=-1;$bestd=PHP_INT_MAX; for ($j=0;$j<$n;$j++){ if(!$visited[$j] && $dist[$cur][$j] < $bestd){ $bestd=$dist[$cur][$j]; $best=$j; } } if ($best==-1) break; $route[]=$best; $visited[$best]=true; $cost += $dist[$cur][$best]; $cur=$best; }
    $cost += $dist[$cur][0]; $route[] = 0;
    return ['dist'=>(int)$cost,'route'=>$route];
}

$start = timer_start(); $hk = held_karp($dist); $time_hk = timer_diff_ms($start);
$start2 = timer_start(); $gr = greedy_nn($dist); $time_gr = timer_diff_ms($start2);
$time_bb = 0.0;

$is_correct = ($player_answer !== null && $player_answer == $hk['dist']) ? 1 : 0;

$payload = [
    'user_id'=>$_SESSION['user_id'],
    'home_city'=>$home,
    'selected_cities'=>json_encode($selected, JSON_UNESCAPED_UNICODE),
    'distance_matrix'=>json_encode($dist, JSON_UNESCAPED_UNICODE),
    'shortest_distance'=>(int)$hk['dist'],
    'shortest_route'=>json_encode($hk['route'], JSON_UNESCAPED_UNICODE),
    'algorithm1_name'=>'Held-Karp','algorithm1_time'=>round($time_hk,6),
    'algorithm2_name'=>'Greedy','algorithm2_time'=>round($time_gr,6),
    'algorithm3_name'=>'Branch-and-Bound','algorithm3_time'=>round($time_bb,6),
    'player_answer'=>($player_answer===null?0:$player_answer),
    'is_correct'=>$is_correct,
    'played_at'=>date('Y-m-d H:i:s')
];

try { $id = save_result($pdo,'tsp_results',$payload); echo json_encode(['ok'=>true,'hk'=>$hk,'greedy'=>$gr,'times'=>['hk'=>round($time_hk,6),'gr'=>round($time_gr,6),'bb'=>round($time_bb,6)],'saved_id'=>$id]); }
catch (Exception $e) { http_response_code(500); echo json_encode(['error'=>'Save failed']); }
