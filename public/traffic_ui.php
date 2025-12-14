<?php
require_once __DIR__ . '/../functions.php';
$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Traffic Simulation Problem Game</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
*{box-sizing:border-box}
body{
  margin:0;
  padding:24px;
  font-family:system-ui,-apple-system,Segoe UI,sans-serif;
  background:#f8f9fa;
}
.container{
  max-width:1100px;
  margin:auto;
  background:#fff;
  padding:24px;
  border-radius:12px;
  box-shadow:0 10px 25px rgba(0,0,0,.1);
}
.topbar{
  display:flex;
  justify-content:space-between;
  align-items:center;
  font-weight:800;
}
.timer{font-weight:900}
h2{text-align:center;margin:16px 0}
svg{background:#f1f5f9;border-radius:10px}

.controls{
  margin-top:20px;
  display:flex;
  gap:12px;
  align-items:center;
}
input{
  padding:10px;
  width:220px;
  font-size:16px;
}
button{
  padding:12px 20px;
  border:0;
  border-radius:6px;
  font-weight:800;
  cursor:pointer;
}
.primary{background:#007bff;color:#fff}
.secondary{background:#6c757d;color:#fff}

#result{
  margin-top:20px;
  padding:16px;
  border-radius:8px;
  display:none;
  font-weight:800;
}
.win{background:#dcfce7;color:#166534}
.lose{background:#fff3cd;color:#854d0e}

.legend{
  margin-top:10px;
  font-size:14px;
}
</style>
</head>

<body>
<div class="container">

<div class="topbar">
  <div>Player: <?= htmlspecialchars($_SESSION['user_name'] ?? 'Player') ?></div>
  <div class="timer">⏱ <span id="time">0</span>s</div>
</div>

<h2>🚦 Traffic Network – Maximum Flow</h2>

<svg id="network" width="100%" height="520" viewBox="0 0 800 520">
  <defs>
    <marker id="arrow" markerWidth="10" markerHeight="10" refX="8" refY="3"
            orient="auto" markerUnits="strokeWidth">
      <path d="M0,0 L0,6 L9,3 z" fill="#333" />
    </marker>
  </defs>
</svg>

<div class="legend">
  <strong>Legend:</strong> 🟢 Source (A), 🔵 Intermediate nodes, 🔴 Sink (T),
  Numbers = road capacity (vehicles/minute)
</div>

<div class="controls">
  <label><strong>Enter Maximum Flow:</strong></label>
  <input id="player_answer" type="number" min="0" value="0">
  <button class="primary" id="submit">SUBMIT</button>
  <button class="secondary" id="newGame">NEW GAME</button>
</div>

<div id="result"></div>

<p style="margin-top:20px"><a href="../index.php">⬅ Back</a></p>

<input type="hidden" id="csrf" value="<?= htmlspecialchars($csrf) ?>">

</div>

<script>
const BASE_URL = "<?= BASE_URL ?>";
const svg = document.getElementById("network");
let timer = 0, interval = null;
let edges = [];

function randCap(){
  return Math.floor(Math.random()*11) + 5; // 5–15
}

/* Fixed node positions (VERTICAL NETWORK) */
const nodes = {
  A:[400,40,'#16a34a'],
  B:[250,130,'#2563eb'],
  C:[400,130,'#2563eb'],
  D:[550,130,'#2563eb'],
  E:[320,240,'#2563eb'],
  F:[480,240,'#2563eb'],
  G:[360,350,'#2563eb'],
  H:[440,350,'#2563eb'],
  T:[400,460,'#dc2626']
};

/* Fixed topology */
const topology = [
  ['A','B'],['A','C'],['A','D'],
  ['B','E'],['C','E'],['C','F'],['D','F'],
  ['E','G'],['E','H'],['F','H'],
  ['G','T'],['H','T']
];

function generateNetwork(){
  edges = topology.map(e => ({
    u:e[0],
    v:e[1],
    cap: randCap()
  }));
}

function drawNetwork(){
  svg.innerHTML = svg.querySelector("defs").outerHTML;

  /* Draw edges */
  edges.forEach(e=>{
    const [x1,y1] = nodes[e.u];
    const [x2,y2] = nodes[e.v];
    const mx = (x1+x2)/2;
    const my = (y1+y2)/2;

    svg.innerHTML += `
      <line x1="${x1}" y1="${y1+22}" x2="${x2}" y2="${y2-22}"
        stroke="#444" stroke-width="2" marker-end="url(#arrow)" />
      <text x="${mx}" y="${my-6}"
        font-size="14" font-weight="800" fill="#2563eb">${e.cap}</text>
    `;
  });

  /* Draw nodes (HOUSE ICON 🏠) */
  Object.entries(nodes).forEach(([k,[x,y,color]])=>{
    svg.innerHTML += `
      <circle cx="${x}" cy="${y}" r="22" fill="${color}" />
      <text x="${x}" y="${y+5}" text-anchor="middle"
        font-size="14" font-weight="900" fill="#fff">🏠 ${k}</text>
    `;
  });
}

function startTimer(){
  clearInterval(interval);
  timer = 0;
  document.getElementById("time").textContent = 0;
  interval = setInterval(()=>{
    timer++;
    document.getElementById("time").textContent = timer;
  },1000);
}

async function submit(){
  const fd = new FormData();
  fd.append("player_answer", document.getElementById("player_answer").value);
  fd.append("csrf", document.getElementById("csrf").value);

  try{
    const r = await fetch(BASE_URL+"/public/traffic.php",{
      method:"POST",
      body:fd,
      credentials:"include"
    });
    const j = await r.json();
    const box = document.getElementById("result");

    if(j.is_correct){
      box.className = "win";
      box.innerHTML = `
        ✅ Correct!<br>
        Max Flow = ${j.max_flow}<br>
        Ford–Fulkerson: ${j.times.ff} ms<br>
        Edmonds–Karp: ${j.times.ek} ms
      `;
    }else{
      box.className = "lose";
      box.innerHTML = `
        ❌ Incorrect.<br>
        Correct Max Flow = ${j.max_flow}
      `;
    }
    box.style.display = "block";
  }catch(e){
    alert("Unexpected error occurred.");
  }
}

function newGame(){
  document.getElementById("player_answer").value = 0;
  document.getElementById("result").style.display = "none";
  generateNetwork();
  drawNetwork();
  startTimer();
}

/* Events */
document.getElementById("submit").onclick = submit;
document.getElementById("newGame").onclick = newGame;

/* Init */
generateNetwork();
drawNetwork();
startTimer();
</script>

</body>
</html>
