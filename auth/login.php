<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

// Make sure POST was received
$data = $_SERVER['REQUEST_METHOD'] === 'POST' 
    ? $_POST 
    : json_decode(file_get_contents('php://input'), true);

// Extract fields
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
$csrf     = $data['csrf'] ?? '';

// ------------------------------
// CSRF VALIDATION
// ------------------------------
if (!check_csrf($csrf)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// ------------------------------
// FIELD VALIDATION
// ------------------------------
if ($username === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['error' => 'Username and password are required']);
    exit;
}

// ------------------------------
// FETCH USER
// ------------------------------
try {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT user_id, username, email, password 
        FROM users 
        WHERE username = :u OR email = :u 
        LIMIT 1
    ");
    $stmt->execute([':u' => $username]);

    $user = $stmt->fetch();

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// ------------------------------
// CHECK USER EXISTS
// ------------------------------
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// ------------------------------
// PASSWORD VERIFY
// ------------------------------
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// ------------------------------
// LOGIN SUCCESS
// ------------------------------
$_SESSION['user_id']  = $user['user_id'];
$_SESSION['username'] = $user['username'];

// Generate new CSRF token after login
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo json_encode([
    'ok'        => true,
    'user_id'   => $user['user_id'],
    'username'  => $user['username']
]);

exit;
