<?php
date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'lcc_payroll';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_errno) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
// Keep database dates/times aligned with the application's Philippines timezone.
@$conn->query("SET time_zone = '+08:00'");
