<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit;
}

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function money($value): string { return '₱ ' . number_format((float)$value, 2); }
function employee_photo_url(?string $path): string {
    $path = ltrim(trim((string)$path), '/\\');
    return $path === '' ? '' : '../' . $path;
}
function current_employee(mysqli $conn): ?array {
    $id = (int)($_SESSION['employee_id'] ?? 0);
    if ($id <= 0) return null;
    $stmt = $conn->prepare('SELECT * FROM employees WHERE employee_id=? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}
