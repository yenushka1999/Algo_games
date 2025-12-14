<?php
require_once __DIR__ . '/../functions.php';
session_unset();
session_destroy();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>true]);
