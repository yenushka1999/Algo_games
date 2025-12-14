<?php
require_once __DIR__ . '/../functions.php';
$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Eight Queens – PDSA</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
*{box-sizing:border-box}
body{margin:0;padding:24px;background:#000;font-family:system-ui}
.container{max-width:1100px;margin:auto;background:#020617;padding:24px;border-radius:16px}
header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
h1{margin:0;color:#e5e7eb}
.controls{display:flex;gap:14px}
button{padding:14px 30px;border-radius:14px;border:0;cursor:pointer;font-size:18px;font-weight:800}
.play{background:#7c3aed;color:#fff}
.restart{background:#4c1d95;color:#fff}
.game{display:grid;grid-template-columns:520px 1fr;gap:28px}

.board-box{position:relative;background:#000;padding:14px;border-radius:14px}
.board{
  position:relative;
  width:100%;
  aspect-ratio:1/1;
  background:url("<?= BASE_URL ?>/assets/chessboard.jpg") center/contain no-repeat;
}
.grid{
  position:absolute;
  inset:0;
  display:grid;
  grid-template-columns:repeat(8,1fr);
  grid-template-rows:repeat(8,1fr);
}
.cell{position:relative;cursor:pointer}

.queen{
  position:absolute;
  width:0;height:0;
  border-left:14px solid transparent;
  border-right:14px solid transparent;
  border-bottom:26px solid #a855f7;
  transform:translate(-50%,-50%);
  pointer-events:none;
}

.panel{background:#f8fafc;padding:22px;border-radius:16px}
.timer{font-size:22px;font-weight:900}
.result{margin-top:18px;padding:16px;border-radius:12px;font-weight:800;display:none}
.win{background:#dcfce7;color:#166534}
.lose{background:#fee2e2;color:#7f1d1d}
.warn{background:#fef3c7;color:#92400e}
</style>
</head>

<body>
<div class="container">

<header>
  <h1>♛ Eight Queens Puzzle</h1>
  <div class="controls">
    <button id="play" class="play">PLAY</button>
    <button id="restart" class="restart">RESTART</button>
  </div>
</header>

<div class="game">
  <div class="board-box">
    <div class="board">
      <div class="grid" id="grid"></div>
    </div>
  </div>

  <div class="panel">
    <div class="timer">Time left: <span id="time">20</span>s</div>
    <div id="status" class="result"></div>
    <p>Place <strong>8 queens</strong>, one per row, without conflicts.</p>
    <p><a href="../index.php">⬅ Back</a></p>
  </div>
</div>

</div>

<script>
const CSRF = "<?= htmlspecialchars($csrf) ?>";

const grid = document.getElementById("grid");
const statusEl = document.getElementById("status");
const timeEl = document.getElementById("time");
const playBtn = document.getElementById("play");
const restartBtn = document.getElementById("restart");

const N = 8;
let queens = Array(N).fill(null);
let timer = null;
let timeLeft = 20;
let gameActive = false;

/* Hard-coded correct solution (for lose case) */
const CORRECT_SOLUTION = [0,4,7,5,2,6,1,3];

/* Build grid */
function buildGrid(){
  grid.innerHTML="";
  for(let r=0;r<N;r++){
    for(let c=0;c<N;c++){
      const cell=document.createElement("div");
      cell.className="cell";
      cell.addEventListener("click", ()=>placeQueen(r,c,cell));
      grid.appendChild(cell);
    }
  }
}

/* Place queen */
function placeQueen(row,col,cell){
  if(!gameActive) return;

  if(queens[row] !== null){
    const old=document.querySelector(`.queen[data-row="${row}"]`);
    if(old) old.remove();
  }

  queens[row] = col;

  const q=document.createElement("div");
  q.className="queen";
  q.dataset.row=row;
  q.style.left="50%";
  q.style.top="50%";
  cell.appendChild(q);
}

/* Validate board */
function isValid(){
  for(let r=0;r<N;r++){
    if(queens[r] === null) return false;
    for(let r2=0;r2<r;r2++){
      if(queens[r] === queens[r2]) return false;
      if(Math.abs(queens[r]-queens[r2]) === Math.abs(r-r2)) return false;
    }
  }
  return true;
}

function isBoardFull(){
  return queens.every(q => q !== null);
}

/* Show correct solution */
function showCorrectSolution(){
  document.querySelectorAll(".queen").forEach(q=>q.remove());
  CORRECT_SOLUTION.forEach((col,row)=>{
    const cell = grid.children[row*8 + col];
    const q=document.createElement("div");
    q.className="queen";
    q.style.left="50%";
    q.style.top="50%";
    cell.appendChild(q);
  });
}

/* Timer */
function startTimer(){
  clearInterval(timer);
  timeLeft=20;
  timeEl.textContent=timeLeft;
  timer=setInterval(()=>{
    timeLeft--;
    timeEl.textContent=timeLeft;
    if(timeLeft<=0){
      clearInterval(timer);
      loseGame();
    }
  },1000);
}

/* ✅ WIN → SAVE PLAYER SOLUTION */
function winGame(){
  clearInterval(timer);
  gameActive=false;

  const fd = new FormData();
  fd.append("csrf", CSRF);
  fd.append("player_solution", JSON.stringify(queens));

  fetch("eight_queens.php", {
    method: "POST",
    body: fd,
    credentials: "same-origin"
  })
  .then(r=>r.json())
  .then(resp=>{
    if(resp.ok){
      statusEl.className="result win";
      statusEl.textContent="🎉 Correct! Your solution was saved.";
    }else{
      statusEl.className="result warn";
      statusEl.textContent="⚠️ " + (resp.error || "Save failed");
    }
    statusEl.style.display="block";
  })
  .catch(()=>{
    statusEl.className="result warn";
    statusEl.textContent="⚠️ Network error. Result not saved.";
    statusEl.style.display="block";
  });
}

/* LOSE */
function loseGame(){
  gameActive=false;
  showCorrectSolution();
  statusEl.className="result lose";
  statusEl.textContent="❌ Incorrect placement. Correct solution shown.";
  statusEl.style.display="block";
}

/* PLAY */
playBtn.onclick=()=>{
  statusEl.style.display="none";
  queens.fill(null);
  document.querySelectorAll(".queen").forEach(q=>q.remove());
  gameActive=true;
  startTimer();
};

/* RESTART */
restartBtn.onclick=()=>{
  clearInterval(timer);
  queens.fill(null);
  document.querySelectorAll(".queen").forEach(q=>q.remove());
  statusEl.style.display="none";
  timeLeft=20;
  timeEl.textContent="20";
  gameActive=false;
};

/* Auto-check */
grid.addEventListener("click", ()=>{
  if(!gameActive) return;

  if(isBoardFull()){
    if(isValid()){
      winGame();
    }else{
      clearInterval(timer);
      loseGame();
    }
  }
});

/* Init */
buildGrid();
</script>
</body>
</html>
