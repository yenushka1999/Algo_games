<?php
require_once __DIR__ . '/../functions.php';
header('Content-Type: application/json; charset=utf-8');

$data = $_POST;

if (!check_csrf($data['csrf'] ?? '')) {
    http_response_code(400);
    echo json_encode(['error'=>'Invalid CSRF token']);
    exit;
}

$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

$errors = [];
if (!validate_username($username)) $errors[] = 'Invalid username (3-20)';
if (!validate_email($email)) $errors[] = 'Invalid email';
if (!validate_password($password)) $errors[] = 'Password too short';
if ($errors) { http_response_code(422); echo json_encode(['errors'=>$errors]); exit; }

$stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = :u OR email = :e');
$stmt->execute([':u'=>$username, ':e'=>$email]);
if ($stmt->fetch()) { http_response_code(409); echo json_encode(['error'=>'Username or email exists']); exit; }

$hash = password_hash($password, PASSWORD_BCRYPT);
$ins = $pdo->prepare('INSERT INTO users (username,password,email) VALUES (:u,:p,:e)');
$ins->execute([':u'=>$username, ':p'=>$hash, ':e'=>$email]);
echo json_encode(['ok'=>true,'user_id'=>$pdo->lastInsertId()]);
