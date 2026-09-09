<?php

require_once __DIR__ . '/../config/database.php';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if (isset($_SESSION['admin_id'])) {
    header('Location: ../pages/admin_dashboard.php');
    exit;
}

$error = '';
$loginFailed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $loginFailed = true;
        $error = 'Incorrect username or password.';
    } else {
        $stmt = $conn->prepare('SELECT admin_id,username,password_hash,full_name FROM admins WHERE username=? LIMIT 1');
        $row = null;
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
        }

        if ($row && password_verify($password, $row['password_hash'])) {
            $_SESSION['admin_id'] = (int)$row['admin_id'];
            $_SESSION['admin_username'] = $row['username'];
            $_SESSION['admin_name'] = $row['full_name'];
            header('Location: ../pages/admin_dashboard.php');
            exit;
        }
        $loginFailed = true;
        $error = 'Incorrect username or password.';
    }
}


header('Location: ../pages/login.php?error=1');
exit;
