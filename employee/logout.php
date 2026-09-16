<?php
require_once __DIR__ . '/../config/database.php';
$hasAdmin = isset($_SESSION['admin_id']);
unset($_SESSION['employee_id'], $_SESSION['employee_no'], $_SESSION['employee_name']);
if ($hasAdmin) {
    session_regenerate_id(true);
    header('Location: login.php');
    exit;
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: login.php');
exit;
