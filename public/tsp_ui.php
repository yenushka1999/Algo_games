<?php require_once __DIR__ . '/../functions.php'; $csrf = csrf_token(); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Traveling Salesman Problem Game</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
*{box-sizing:border-box}
body{
  margin:0;
  padding:24px;
  background:#000;
  font-family:system-ui,-apple-system,Segoe UI,sans-serif;
  color:#e5e7eb;
}
.container{
  max-width:1200px;
  margin:auto;
}
.card{
  background:#020617;
  padding:20px;
  border-radius:14px;
  margin-bottom:18px;
}
h1,h2{margin:0 0 12px 0;text-align:center}
button{
  padding:12px 22px;
  border-radius:10px;
  border:0;
  font-weight:800;
  cursor:pointer;
  background:#9ca3af;
  color:#000;
}
button:hover{background:#d1d5db}
input{
  padding:10px;
  border-radius:8px;
  border:0;
  font-size:16px;
}
.grid{
  display:grid;
  grid-template-columns:repeat(10,1fr);
  gap:6px;
  margin-top:12px;
}
.city-box{
  background:#111827;
  padding:10px;
  border-radius:8px;
  text-align:center;
}
table{
  width:100%;
  border-collapse:collapse;
  margin-top:12px;
  font-size:14px;
}
th,td{
  border:1px solid #374151;
  padding:6px;
  text-align:center;
}
th{background:#111827}
.home{background:#065f46;font-weight:800}
.actions{
  display:flex;
  gap:14px;
  justify-content:center;
  margin-top:16px;
}
.result{
  margin-top:16px;
  padding:14px;
  border-radius:10px;
  display:none;
  font-weight:800;
}
.win{background:#064e3b;color:#d1fae5}
.lose{background:#78350f;color:#ffedd5}
</style>
</head>

<body>
<div class="container">

<div class="card">
  <h1>🧭 Traveling Salesman Problem Game</h1>
</div>

<div class="card">
  <strong>Home City:</strong> 🏠 <span id="homeCity"></span>
</div>

<div class="card">
  <h2>Select Cities to Visit</h2>
  <div class="grid" id="cityGrid"></div>
  <p><strong>Selected Cities:</strong> <span id="selectedList">None</span></p>
</div>

<div class="card">
  <h2>Distance Matrix (km)</h2>
  <div style="overflow-x:auto">
    <table id="matrix"></table>
  </div>
  <p style="font-size:13px;color:#9ca3af">
    Distances are symmetric and randomly generated between <strong>50–100 km</strong>.
  </p>
</div>

<div class="card">
  <h2>Your Answer</h2>
  <input type="number" id="player_answer" placeholder="Enter shortest distance">
  <div class="actions">
    <button id="submit">Submit Answer</button>
    <button id="restart">Restart Game</button>
  </div>
  <div id="result" class="result"></div>
</div>

<p style="text-align:center">
  <a href="../index.php" style="color:#9ca3af">⬅ Back to Main Menu</a>
</p>

<input type="hidden" id="csrf" value="<?= htmlspecialchars($csrf) ?>">

</div>

<script>
const BASE_URL = "<?= BASE_URL ?>";
const cities = ["A","B","C","D","E","F","G","H","I","J"];
let home = cities[Math.floor(Math.random()*cities.length)];
let selected = new Set();
let matrix = [];

function randDist(){ return Math.floor(Math.random()*51)+50; }

function generateMatrix(){
  matrix = Array(10).fill(0).map(()=>Array(10).fill(0));
  for(let i=0;i<10;i++){
    for(let j=i+1;j<10;j++){
      const d = randDist();
      matrix[i][j]=d;
      matrix[j][i]=d;
    }
  }
}

function drawMatrix(){
  const tbl = document.getElementById("matrix");
  tbl.innerHTML="";
  let h="<tr><th></th>";
  cities.forEach(c=>h+=`<th>${c}</th>`);
  h+="</tr>";
  for(let i=0;i<10;i++){
    h+=`<tr><th class="${cities[i]===home?'home':''}">${cities[i]}</th>`;
    for(let j=0;j<10;j++){
      h+=`<td>${i===j?"—":matrix[i][j]}</td>`;
    }
    h+="</tr>";
  }
  tbl.innerHTML=h;
}

function drawCities(){
  const grid=document.getElementById("cityGrid");
  grid.innerHTML="";
  cities.forEach(c=>{
    if(c===home) return;
    const div=document.createElement("div");
    div.className="city-box";
    div.innerHTML=`<input type="checkbox"> City ${c}`;
    div.onclick=()=>{
      if(selected.has(c)) selected.delete(c);
      else selected.add(c);
      updateSelected();
    };
    grid.appendChild(div);
  });
}

function updateSelected(){
  document.getElementById("selectedList").textContent =
    selected.size ? Array.from(selected).join(", ") : "None";
}

async function submit(){
  const fd=new FormData();
  fd.append("home_city",home);
  fd.append("selected_cities",JSON.stringify(Array.from(selected)));
  fd.append("distance_matrix",JSON.stringify(matrix));
  fd.append("player_answer",document.getElementById("player_answer").value);
  fd.append("csrf",document.getElementById("csrf").value);

  const r=await fetch(BASE_URL+"/public/tsp.php",{method:"POST",body:fd,credentials:"include"});
  const j=await r.json();
  const res=document.getElementById("result");

  if(j.ok && j.hk){
    if(parseInt(document.getElementById("player_answer").value)===j.hk.dist){
      res.className="result win";
      res.textContent="✅ Correct! Optimal distance: "+j.hk.dist+" km";
    }else{
      res.className="result lose";
      res.textContent="❌ Incorrect. Optimal distance: "+j.hk.dist+" km";
    }
    res.style.display="block";
  }
}

function restart(){
  selected.clear();
  home = cities[Math.floor(Math.random()*cities.length)];
  document.getElementById("homeCity").textContent=home;
  document.getElementById("player_answer").value="";
  document.getElementById("result").style.display="none";
  generateMatrix();
  drawMatrix();
  drawCities();
  updateSelected();
}

document.getElementById("submit").onclick=submit;
document.getElementById("restart").onclick=restart;

document.getElementById("homeCity").textContent=home;
generateMatrix();
drawMatrix();
drawCities();
</script>

</body>
</html>
