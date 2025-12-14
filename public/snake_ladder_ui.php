<?php
require_once __DIR__ . '/../functions.php';
$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Snake & Ladder – PDSA</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
*{box-sizing:border-box}
body{
  margin:0;
  padding:24px;
  font-family:system-ui,-apple-system,Segoe UI,sans-serif;
  background:#020617;
  color:#e5e7eb;
}
.container{max-width:1200px;margin:auto}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.controls{display:flex;gap:10px;align-items:center}
.controls input{width:70px;padding:8px;border-radius:8px;border:0}
button{padding:10px 16px;border-radius:10px;border:0;font-weight:700;cursor:pointer}
.primary{background:#2563eb;color:#fff}
.secondary{background:#334155;color:#fff}

.game{display:grid;grid-template-columns:760px 1fr;gap:24px}

.board-box{position:relative;padding:14px;background:#020617;border-radius:16px}
.board{
  position:relative;
  width:100%;
  aspect-ratio:1/1;
  display:grid;
  background:linear-gradient(135deg,#1e3a8a,#60a5fa);
  border-radius:14px;
}
.cell{
  border:1px solid rgba(255,255,255,.15);
  font-size:11px;
  padding:4px;
  display:flex;
  align-items:flex-end;
  justify-content:flex-end;
}
.svg-layer{position:absolute;inset:0;pointer-events:none;z-index:2}

.token{
  position:absolute;
  width:24px;height:24px;
  background:#22c55e;
  border-radius:50%;
  border:3px solid #fff;
  box-shadow:0 0 10px rgba(34,197,94,.8);
  transition:.3s;
  z-index:5;
}

.dice-box{text-align:center;margin-top:14px}
.dice{
  width:70px;height:70px;
  background:#fff;border-radius:14px;
  font-size:40px;font-weight:900;
  color:#020617;
  display:flex;align-items:center;justify-content:center;
  margin:auto;
}
.roll-btn{margin-top:10px;width:100%;background:#2563eb;color:#fff}

.panel{background:#f8fafc;color:#020617;padding:20px;border-radius:16px}
.answers button{width:100%;margin-bottom:14px;font-size:18px}
.result{display:none;padding:12px;border-radius:10px;font-weight:800}
.win{background:#dcfce7;color:#166534}
.lose{background:#fee2e2;color:#7f1d1d}
.draw{background:#e0e7ff;color:#1e3a8a}

@media(max-width:1024px){.game{grid-template-columns:1fr}}
</style>
</head>

<body>
<div class="container">

<header>
  <h1>🐍 Snake & Ladder</h1>
  <div class="controls">
    <label>N</label>
    <input id="N" type="number" min="6" max="12" value="10">
    <button class="primary" id="start">START</button>
    <button class="secondary" id="restart">RESTART</button>
  </div>
</header>

<div class="game">

<div class="board-box">
  <div class="board" id="board"></div>
  <svg class="svg-layer" id="svg"></svg>
  <div class="token" id="token"></div>

  <div class="dice-box">
    <div class="dice" id="dice">1</div>
    <button class="roll-btn" id="rollDice">ROLL</button>
  </div>
</div>

<div class="panel">
  <h2>Minimum Dice Throws</h2>
  <div class="answers" id="choices"></div>
  <div class="result" id="result"></div>
  <p><a href="../index.php">⬅ Back</a></p>
</div>

</div>
</div>

<script>
const BASE_URL="<?= BASE_URL ?>";
const CSRF="<?= htmlspecialchars($csrf,ENT_QUOTES) ?>";

const board=document.getElementById("board");
const svg=document.getElementById("svg");
const token=document.getElementById("token");
const diceEl=document.getElementById("dice");
const choicesEl=document.getElementById("choices");
const resultEl=document.getElementById("result");

let N=10,max=100;
let snakes=[],ladders=[];
let usedCells=new Set();

/* ---------------- BOARD ---------------- */
function buildBoard(){
  board.innerHTML="";
  board.style.gridTemplateColumns=`repeat(${N},1fr)`;
  max=N*N;
  for(let i=max;i>=1;i--){
    const d=document.createElement("div");
    d.className="cell";
    d.textContent=i;
    board.appendChild(d);
  }
}

/* ---------------- CELL POSITION ---------------- */
function cellXY(cell){
  const size=board.clientWidth;
  const cs=size/N;
  const r=Math.floor((cell-1)/N);
  const c=(r%2===0)?(cell-1)%N:(N-1-(cell-1)%N);
  return {x:c*cs+cs/2,y:size-(r+1)*cs+cs/2};
}

/* ---------------- RANDOM GENERATION (NO OVERLAP) ---------------- */
function generate(type,count){
  const arr=[];
  while(arr.length<count){
    let start=Math.floor(Math.random()*(max-2))+2;
    let end=Math.floor(Math.random()*(max-2))+2;

    if(type==="ladder" && end<=start) continue;
    if(type==="snake" && end>=start) continue;
    if(start===max||end===max) continue;
    if(usedCells.has(start)||usedCells.has(end)) continue;

    if(type==="ladder"){
      const rs=Math.floor((start-1)/N);
      const re=Math.floor((end-1)/N);
      if(rs===re) continue;
    }

    usedCells.add(start);
    usedCells.add(end);
    arr.push({start,end});
  }
  return arr;
}

/* ---------------- DRAW (UNCHANGED FROM ORIGINAL) ---------------- */
function draw(){
  svg.innerHTML="";
  const size=board.clientWidth;
  svg.setAttribute("viewBox",`0 0 ${size} ${size}`);

  ladders.forEach(l=>{
    const a=cellXY(l.start),b=cellXY(l.end);
    const dx=(b.x-a.x)/10,dy=(b.y-a.y)/10;

    svg.innerHTML+=`
      <line x1="${a.x-6}" y1="${a.y}" x2="${b.x-6}" y2="${b.y}"
        stroke="#7c3aed" stroke-width="6" stroke-linecap="round"/>
      <line x1="${a.x+6}" y1="${a.y}" x2="${b.x+6}" y2="${b.y}"
        stroke="#7c3aed" stroke-width="6" stroke-linecap="round"/>
    `;
    for(let i=1;i<10;i++){
      svg.innerHTML+=`
        <line x1="${a.x-6+dx*i}" y1="${a.y+dy*i}"
              x2="${a.x+6+dx*i}" y2="${a.y+dy*i}"
              stroke="#facc15" stroke-width="4" stroke-linecap="round"/>
      `;
    }
  });

  snakes.forEach(s=>{
    const a=cellXY(s.start),b=cellXY(s.end);
    svg.innerHTML+=`
      <path d="M${a.x},${a.y}
      C${a.x-40},${(a.y+b.y)/2}
       ${b.x+40},${(a.y+b.y)/2}
       ${b.x},${b.y}"
      stroke="#a855f7" stroke-width="30"
      fill="none" stroke-linecap="round"/>
      <path d="M${a.x},${a.y}
      C${a.x-40},${(a.y+b.y)/2}
       ${b.x+40},${(a.y+b.y)/2}
       ${b.x},${b.y}"
      stroke="#14b8a6" stroke-width="14"
      fill="none" stroke-dasharray="30 24"/>
      <circle cx="${a.x-6}" cy="${a.y-6}" r="3" fill="#000"/>
      <circle cx="${a.x+6}" cy="${a.y-6}" r="3" fill="#000"/>
    `;
  });
}

/* ---------------- ALGORITHMS ---------------- */
function bfs(){
  const next={};
  for(let i=1;i<=max;i++) next[i]=i;
  ladders.forEach(l=>next[l.start]=l.end);
  snakes.forEach(s=>next[s.start]=s.end);

  const d=Array(max+1).fill(1e9);
  d[1]=0; const q=[1];
  while(q.length){
    const c=q.shift();
    for(let i=1;i<=6;i++){
      const n=c+i;if(n>max)continue;
      const f=next[n];
      if(d[f]>d[c]+1){d[f]=d[c]+1;q.push(f);}
    }
  }
  return d[max]===1e9?null:d[max];
}

function dpMinMoves(){
  const next={};
  for(let i=1;i<=max;i++) next[i]=i;
  ladders.forEach(l=>next[l.start]=l.end);
  snakes.forEach(s=>next[s.start]=s.end);

  const dp=Array(max+1).fill(Infinity);
  dp[1]=0; let changed=true;
  while(changed){
    changed=false;
    for(let i=1;i<=max;i++){
      if(dp[i]===Infinity) continue;
      for(let d=1;d<=6;d++){
        let n=i+d;if(n>max)continue;
        n=next[n];
        if(dp[n]>dp[i]+1){
          dp[n]=dp[i]+1;
          changed=true;
        }
      }
    }
  }
  return dp[max]===Infinity?null:dp[max];
}

/* ---------------- ANSWERS ---------------- */
function showChoices(){
  const min=bfs();
  if(min===null){
    resultEl.className="result draw";
    resultEl.textContent="Draw – No valid path!";
    resultEl.style.display="block";
    return;
  }
  const opts=[min,min+1,min+2].sort(()=>Math.random()-0.5);
  choicesEl.innerHTML="";
  opts.forEach(v=>{
    const b=document.createElement("button");
    b.textContent=v;
    b.onclick=()=>submit(v,min);
    choicesEl.appendChild(b);
  });
}

/* ---------------- SUBMIT (UNCHANGED) ---------------- */
async function submit(ans,min){
  resultEl.className="result "+(ans===min?"win":"lose");
  resultEl.textContent=ans===min?"Correct!":"Incorrect";
  resultEl.style.display="block";

  const fd=new FormData();
  fd.append("board_size",N);
  fd.append("player_answer",ans);
  fd.append("num_ladders",ladders.length);
  fd.append("num_snakes",snakes.length);
  fd.append("algorithm1_name","BFS");
  fd.append("algorithm2_name","DP");
  fd.append("algorithm1_time",0);
  fd.append("algorithm2_time",0);
  fd.append("ladders",JSON.stringify(ladders));
  fd.append("snakes",JSON.stringify(snakes));
  fd.append("csrf",CSRF);

  await fetch(BASE_URL+"/public/snake_ladder.php",{method:"POST",body:fd,credentials:"include"});
}

/* ---------------- INIT ---------------- */
function init(){
  N=+document.getElementById("N").value;
  const count=N-2;
  max=N*N;

  usedCells=new Set([1,max]);

  buildBoard();
  ladders=generate("ladder",count);
  snakes=generate("snake",count);
  draw();
  showChoices();
  resultEl.style.display="none";
}

start.onclick=init;
restart.onclick=init;
rollDice.onclick=()=>diceEl.textContent=Math.floor(Math.random()*6)+1;
init();
</script>
</body>
</html>
