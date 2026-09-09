<?php
require_once __DIR__ . '/../config/database.php';
unset($_SESSION['employee_id'], $_SESSION['employee_no'], $_SESSION['employee_name']);
session_regenerate_id(true);
header('Location: login.php'); exit;
