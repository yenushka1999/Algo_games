<?php
require_once __DIR__ . '/../functions.php';
$csrf = csrf_token();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Tower of Hanoi Game</title>

<style>
body{
    background:#000;
    color:#e0e0e0;
    font-family:Consolas, monospace;
    margin:0;
    padding:20px;
}
.container{max-width:1100px;margin:auto}
.box{
    border:1px solid #9b59b6;
    padding:15px;
    margin-bottom:15px;
    background:#4b0082;
}
h1,h2,h3{text-align:center;margin:5px 0}
button{
    background:#777;
    color:#000;
    border:none;
    padding:8px 14px;
    margin:4px;
    cursor:pointer;
    font-weight:bold
}
button:hover{background:#999}
input{
    background:#111;
    color:#fff;
    border:1px solid #ccc;
    padding:4px
}
#board{
    display:flex;
    justify-content:space-around;
    margin-top:20px
}
.peg{
    width:180px;
    height:300px;
    border:2px solid #ddd;
    display:flex;
    flex-direction:column-reverse;
    align-items:center;
    padding-bottom:10px
}
.peg-label{text-align:center;margin-top:5px}
.disk{
    height:22px;
    background:#aaa;
    margin:3px 0;
    cursor:grab;
    border-radius:3px
}
.hidden{display:none}
.win{background:#065f46}
.lose{background:#7f1d1d}
</style>
</head>

<body>
<div class="container">

<!-- HEADER -->
<div class="box">
<h1>TOWER OF HANOI GAME</h1>
</div>

<!-- SETTINGS -->
<div class="box">
<h3>GAME SETTINGS</h3>
<p id="roundInfo"></p>
<label><input type="radio" name="pegs" value="3" checked> 3 Pegs</label>
<label><input type="radio" name="pegs" value="4"> 4 Pegs</label>
<button onclick="newRound()">New Round</button>
</div>

<!-- BOARD -->
<div class="box">
<div id="board">
  <div>
    <div class="peg" data-peg="A"></div>
    <div class="peg-label">Peg A (Source)</div>
  </div>
  <div>
    <div class="peg" data-peg="B"></div>
    <div class="peg-label">Peg B</div>
  </div>
  <div>
    <div class="peg" data-peg="C"></div>
    <div class="peg-label">Peg C</div>
  </div>
  <div>
    <div class="peg" data-peg="D"></div>
    <div class="peg-label">Peg D (Destination)</div>
  </div>
</div>
</div>

<!-- CONTROLS -->
<div class="box">
<p>Moves Recorded: <span id="moveCount">0</span></p>
<input type="number" id="player_moves" readonly>

<button onclick="submitGame()">Submit Answer</button>
<button onclick="resetRound()">Reset</button>

<br><br>

<a href="../index.php">
    <button>⬅ Back to Main Menu</button>
</a>
</div>

<!-- RESULT -->
<div class="box hidden" id="resultBox"></div>

<input type="hidden" id="csrf" value="<?= htmlspecialchars($csrf) ?>">

</div>

<script>
const base="<?= BASE_URL ?>";

let numDisks=0;
let pegs={A:[],B:[],C:[],D:[]};
let moveLog=[];
let gameWon=false;
let pegMode="3";

/* ---------- NEW ROUND ---------- */
function newRound(){
    pegMode=document.querySelector('input[name="pegs"]:checked').value;
    numDisks=Math.floor(Math.random()*6)+5;
    document.getElementById("roundInfo").textContent=
        `Disks: ${numDisks} | Pegs: ${pegMode}`;
    resetRound();
}

/* ---------- RESET ---------- */
function resetRound(){
    pegs={A:[],B:[],C:[],D:[]};
    moveLog=[];
    gameWon=false;
    document.getElementById("resultBox").classList.add("hidden");

    for(let i=numDisks;i>=1;i--) pegs.A.push(i);
    render();
}

/* ---------- RENDER ---------- */
function render(){
    document.querySelectorAll('.peg').forEach(p=>p.innerHTML='');

    for(let p in pegs){
        const el=document.querySelector(`[data-peg="${p}"]`);
        pegs[p].forEach(size=>{
            const d=document.createElement('div');
            d.className='disk';
            d.style.width=(size*20)+'px';
            d.draggable=true;
            d.dataset.size=size;
            el.appendChild(d);
        });
    }

    document.getElementById("moveCount").textContent=moveLog.length;
    document.getElementById("player_moves").value=moveLog.length;
}

/* ---------- DRAG ---------- */
document.addEventListener('dragstart',e=>{
    if(e.target.classList.contains('disk')){
        const from=e.target.closest('.peg').dataset.peg;
        e.dataTransfer.setData('from',from);
        e.dataTransfer.setData('size',e.target.dataset.size);
    }
});

/* ---------- DROP ---------- */
document.querySelectorAll('.peg').forEach(peg=>{
    peg.addEventListener('dragover',e=>e.preventDefault());
    peg.addEventListener('drop',e=>{
        e.preventDefault();
        const to=e.currentTarget.dataset.peg;
        const from=e.dataTransfer.getData('from');
        const size=parseInt(e.dataTransfer.getData('size'));
        attemptMove(from,to,size);
    });
});

/* ---------- MOVE ---------- */
function attemptMove(from,to,size){
    if(gameWon) return;

    if(pegMode==="3" && to==="D"){
        alert("Peg D is disabled in 3-peg mode");
        return;
    }

    const src=pegs[from];
    const dst=pegs[to];

    if(!src || src[src.length-1]!==size) return;
    if(dst.length && dst[dst.length-1]<size) return;

    src.pop();
    dst.push(size);
    moveLog.push(`${from}-${to}`);
    render();

    if(pegs.D.length===numDisks){
        gameWon=true;
        alert("🎉 Puzzle Solved! Submit your answer.");
    }
}

/* ---------- SUBMIT ---------- */
async function submitGame(){
    const fd=new FormData();
    fd.append("num_disks",numDisks);
    fd.append("num_pegs",pegMode);
    fd.append("player_moves",moveLog.length);
    fd.append("player_sequence",JSON.stringify(moveLog));
    fd.append("csrf",document.getElementById("csrf").value);

    const r=await fetch(base+"/public/hanoi.php",{
        method:"POST",
        body:fd,
        credentials:"include"
    });
    const j=await r.json();

    const box=document.getElementById("resultBox");
    box.classList.remove("hidden");

    if(j.is_correct==1){
        box.className="box win";
        box.innerHTML=`
            🎉 <b>YOU WIN</b><br><br>
            Your Moves: ${moveLog.length}<br>
            Minimum Moves: ${j.min_moves}
        `;
    }else{
        box.className="box lose";
        box.innerHTML=`
            ❌ <b>YOU LOSE</b><br><br>
            Your Moves: ${moveLog.length}<br>
            Minimum Moves: ${j.min_moves}
        `;
    }
}

newRound();
</script>
</body>
</html>
