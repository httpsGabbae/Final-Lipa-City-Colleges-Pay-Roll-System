<?php
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/admin_dashboard.php');
    exit;
}

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if ($current === '' || $new === '' || $confirm === '') {
    header('Location: ../pages/admin_dashboard.php?password_error=' . urlencode('Please complete all password fields.'));
    exit;
}

if (strlen($new) < 8) {
    header('Location: ../pages/admin_dashboard.php?password_error=' . urlencode('The new password must be at least 8 characters.'));
    exit;
}

if ($new !== $confirm) {
    header('Location: ../pages/admin_dashboard.php?password_error=' . urlencode('The new passwords do not match.'));
    exit;
}

$stmt = $conn->prepare('SELECT password_hash FROM admins WHERE admin_id=? LIMIT 1');
$adminId = (int)$_SESSION['admin_id'];
$stmt->bind_param('i', $adminId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || !password_verify($current, $row['password_hash'])) {
    header('Location: ../pages/admin_dashboard.php?password_error=' . urlencode('The current password is incorrect.'));
    exit;
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $conn->prepare('UPDATE admins SET password_hash=? WHERE admin_id=?');
$stmt->bind_param('si', $hash, $adminId);

if (!$stmt->execute()) {
    header('Location: ../pages/admin_dashboard.php?password_error=' . urlencode('The password could not be changed. Please try again.'));
    exit;
}

header('Location: ../pages/admin_dashboard.php?password_changed=1');
exit;
