<?php
require_once __DIR__ . '/functions.php';
$csrf = csrf_token();
$logged = is_logged_in();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>PDSA Games</title>

<style>
body{
    font-family:Arial,Helvetica,sans-serif;
    background:#f4f6f8;
    margin:0;
    padding:20px
}
.container{
    max-width:900px;
    margin:0 auto;
    background:#fff;
    padding:20px;
    border-radius:8px
}
.row{display:flex;gap:20px}
.card{
    flex:1;
    padding:12px;
    border-radius:8px;
    border:1px solid #eee;
    background:#fafafa
}
button{
    padding:10px;
    border-radius:6px;
    border:none;
    background:#2d7bff;
    color:#fff;
    cursor:pointer
}
small{color:#666}
.form-control{
    width:100%;
    padding:8px;
    margin:6px 0
}
.alert{
    padding:8px;
    border-radius:6px;
    margin:8px 0
}
.success{background:#e6ffed;color:#0a8b3a}
.error{background:#ffecec;color:#b82a2a}
.games{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:10px;
    margin-top:12px
}
.game-btn{
    background:#2d7bff;
    padding:14px;
    border-radius:8px;
    color:#fff;
    text-align:center
}
</style>
</head>

<body>
<div class="container">
<h1>PDSA Games</h1>

<?php if (!$logged): ?>

<div class="row">
<div class="card">
<h3>Login</h3>
<div id="login-msg"></div>
<input id="login-username" class="form-control" placeholder="username or email">
<input id="login-password" class="form-control" type="password" placeholder="password">
<input type="hidden" id="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
<button id="btn-login">Login</button>
</div>

<div class="card">
<h3>Register</h3>
<div id="reg-msg"></div>
<input id="reg-username" class="form-control" placeholder="username (3-20)">
<input id="reg-email" class="form-control" placeholder="email">
<input id="reg-password" class="form-control" type="password" placeholder="password (min 6)">
<button id="btn-register">Register</button>
</div>
</div>

<?php else: ?>

<div class="card">
<h3>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'player'); ?></h3>
<p><small>Choose a game to play</small></p>

<div class="games">
<a href="public/snake_ladder_ui.php"><div class="game-btn">Snake &amp; Ladder</div></a>
<a href="public/traffic_ui.php"><div class="game-btn">Traffic (Max Flow)</div></a>
<a href="public/tsp_ui.php"><div class="game-btn">TSP</div></a>
<a href="public/hanoi_ui.php"><div class="game-btn">Tower of Hanoi</div></a>
<a href="public/eight_queens_ui.php"><div class="game-btn">Eight Queens</div></a>
</div>

<form id="logout-form" method="post" action="auth/logout.php" style="margin-top:12px">
<button style="background:#ff4d4d">Logout</button>
</form>
</div>

<?php endif; ?>
</div>

<script>
const base = "<?php echo BASE_URL; ?>";

async function postJSON(url, payload) {
    const res = await fetch(url, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        credentials:'include',
        body: new URLSearchParams(payload)
    });
    return res.json();
}

document.getElementById('btn-login')?.addEventListener('click', async ()=>{
    const u = document.getElementById('login-username').value.trim();
    const p = document.getElementById('login-password').value;
    const csrf = document.getElementById('csrf').value;
    const msg = document.getElementById('login-msg');
    msg.innerHTML = '';

    if (!u || !p) {
        msg.innerHTML = '<div class="alert error">Enter username and password</div>';
        return;
    }

    const j = await postJSON(base + '/auth/login.php', {
        username:u,
        password:p,
        csrf:csrf
    });

    if (j.ok) {
        location.reload();
    } else {
        msg.innerHTML = '<div class="alert error">'+(j.error || 'Login failed')+'</div>';
    }
});

document.getElementById('btn-register')?.addEventListener('click', async ()=>{
    const u = document.getElementById('reg-username').value.trim();
    const e = document.getElementById('reg-email').value.trim();
    const p = document.getElementById('reg-password').value;
    const csrf = document.getElementById('csrf').value;
    const msg = document.getElementById('reg-msg');
    msg.innerHTML='';

    const j = await postJSON(base + '/auth/register.php', {
        username:u,
        email:e,
        password:p,
        csrf:csrf
    });

    if (j.ok) {
        msg.innerHTML = '<div class="alert success">Registered. Please login.</div>';
    } else {
        msg.innerHTML = '<div class="alert error">'+
            (j.error || (j.errors ? j.errors.join('\n') : 'Registration failed'))+
            '</div>';
    }
});
</script>

</body>
</html>
