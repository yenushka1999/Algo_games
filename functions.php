<?php
require_once __DIR__ . '/config.php';

function validate_username(string $u): bool {
    return preg_match('/^[A-Za-z0-9_]{3,20}$/', $u) === 1;
}
function validate_email(string $e): bool {
    return filter_var($e, FILTER_VALIDATE_EMAIL) !== false;
}
function validate_password(string $p): bool {
    return strlen($p) >= 6;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function check_csrf($token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}
function safe_json_encode($arr) {
    return json_encode($arr, JSON_UNESCAPED_UNICODE);
}
function save_result(PDO $pdo, string $table, array $data) {
    $allowed = [
        'snake_ladder_results',
        'traffic_results',
        'tsp_results',
        'hanoi_results',
        'eight_queens_results'
    ];
    if (!in_array($table, $allowed)) {
        throw new InvalidArgumentException('Invalid table');
    }
    $cols = array_keys($data);
    $placeholders = array_map(function($c){ return ':' . $c; }, $cols);
    $sql = "INSERT INTO $table (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $pdo->prepare($sql);
    foreach ($data as $k => $v) {
        if (is_array($v) || is_object($v)) $v = json_encode($v, JSON_UNESCAPED_UNICODE);
        $stmt->bindValue(':' . $k, $v);
    }
    $stmt->execute();
    return $pdo->lastInsertId();
}
function timer_start(): float { return microtime(true); }
function timer_diff_ms(float $start): float { return (microtime(true) - $start); }
