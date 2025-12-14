<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

$data = $_POST;
if (!check_csrf($data['csrf'] ?? '')) { http_response_code(400); echo json_encode(['error'=>'Invalid CSRF token']); exit; }
if (!is_logged_in()) { http_response_code(401); echo json_encode(['error'=>'Login required']); exit; }

$player_answer = isset($data['player_answer']) ? (int)$data['player_answer'] : null;
$nodeKeys = ['A','B','C','D','E'];
$possible = [['A','B'],['A','C'],['A','D'],['B','C'],['B','D'],['B','E'],['C','D'],['C','E'],['D','E']];
shuffle($possible);
$edges = [];
for ($i=0;$i<rand(4,7);$i++){ $p=$possible[$i]; $edges[]=['u'=>$p[0],'v'=>$p[1],'cap'=>rand(5,20)]; }

function build_adj($edges,$nodes){
    $idx=[];$n=0; foreach($nodes as $node){ if(!isset($idx[$node])) $idx[$node]=$n++; }
    $cap = array_fill(0,$n,array_fill(0,$n,0));
    foreach($edges as $e){ $u=$idx[$e['u']]; $v=$idx[$e['v']]; $cap[$u][$v] += (int)$e['cap']; }
    return ['cap'=>$cap,'idx'=>$idx,'nodes'=>$nodes];
}
$built = build_adj($edges,$nodeKeys);
$cap = $built['cap']; $idx = $built['idx']; $n = count($cap);
$s = 0; $t = $n - 1;

function edmonds_karp($cap,$s,$t){
    $n=count($cap);
    $res_cap = array_map(function($r){return array_values($r);}, $cap);
    $maxFlow=0;
    while(true){
        $parent = array_fill(0,$n,-1);
        $q = new SplQueue(); $q->enqueue($s); $parent[$s] = -2;
        $flow = array_fill(0,$n,0); $flow[$s]=PHP_INT_MAX;
        while(!$q->isEmpty()){
            $u=$q->dequeue();
            for($v=0;$v<$n;$v++){
                if($parent[$v]==-1 && $res_cap[$u][$v]>0){
                    $parent[$v]=$u;
                    $flow[$v]=min($flow[$u], $res_cap[$u][$v]);
                    if($v==$t) break 2;
                    $q->enqueue($v);
                }
            }
        }
        if($parent[$t]==-1) break;
        $pushed = $flow[$t];
        $v = $t;
        while($v!=$s){
            $u = $parent[$v];
            $res_cap[$u][$v] -= $pushed;
            $res_cap[$v][$u] += $pushed;
            $v = $u;
        }
        $maxFlow += $pushed;
    }
    return $maxFlow;
}

function ford_fulkerson($cap,$s,$t){
    $n=count($cap);
    $res = array_map(function($r){return array_values($r);}, $cap);
    $maxFlow=0;
    while(true){
        $visited = array_fill(0,$n,false);
        $flowFound = 0;
        $dfs = function($u,$flow) use (&$dfs,&$res,&$t,&$visited,&$n,&$flowFound){
            if($u==$t){ $flowFound = $flow; return $flow; }
            $visited[$u]=true;
            for($v=0;$v<$n;$v++){
                if(!$visited[$v] && $res[$u][$v]>0){
                    $pushed = $dfs($v, min($flow, $res[$u][$v]));
                    if($pushed>0){ $res[$u][$v]-=$pushed; $res[$v][$u]+=$pushed; return $pushed; }
                }
            }
            return 0;
        };
        $p = $dfs($s, PHP_INT_MAX);
        if($p==0) break;
        $maxFlow += $p;
    }
    return $maxFlow;
}

$start1 = timer_start(); $ff = ford_fulkerson($cap,$s,$t); $t1 = timer_diff_ms($start1);
$start2 = timer_start(); $ek = edmonds_karp($cap,$s,$t); $t2 = timer_diff_ms($start2);
$max_flow = max($ff,$ek);
$is_correct = ($player_answer !== null && $player_answer == $max_flow) ? 1 : 0;

$payload = [
    'user_id'=>$_SESSION['user_id'],
    'network_config'=>json_encode(['nodes'=>$nodeKeys,'edges'=>$edges]),
    'max_flow'=>$max_flow,
    'algorithm1_name'=>'Ford-Fulkerson',
    'algorithm1_time'=>round($t1,6),
    'algorithm2_name'=>'Edmonds-Karp',
    'algorithm2_time'=>round($t2,6),
    'player_answer'=>($player_answer===null?0:$player_answer),
    'is_correct'=>$is_correct,
    'played_at'=>date('Y-m-d H:i:s')
];

try {
    $id = save_result($pdo,'traffic_results',$payload);
    echo json_encode(['ok'=>true,'max_flow'=>$max_flow,'times'=>['ff'=>round($t1,6),'ek'=>round($t2,6)],'is_correct'=>$is_correct,'saved_id'=>$id,'edges'=>$edges]);
} catch (Exception $e) { http_response_code(500); echo json_encode(['error'=>'Save failed']); }
