<?php
// config.php - FIXED for MAMP macOS (correct DSN)
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
session_start();

define('BASE_URL', 'http://localhost:8888/pdsa_game');

// Correct MAMP settings
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '8889');
define('DB_NAME', 'pdsa_games');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHAR', 'utf8mb4');

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // Correct DSN format
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}
