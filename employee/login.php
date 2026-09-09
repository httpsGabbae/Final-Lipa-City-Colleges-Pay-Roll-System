<?php
require_once __DIR__ . '/../config/database.php';
if (isset($_SESSION['employee_id'])) { header('Location: dashboard.php'); exit; }
$error = '';

// Admins can open an employee portal directly from the employee list.
// This keeps the portal connected to the admin side without exposing a password.
$adminEmployeeNo = trim($_GET['employee'] ?? '');
if (isset($_SESSION['admin_id']) && $adminEmployeeNo !== '') {
    $quick = $conn->prepare('SELECT employee_id,employee_no,first_name,last_name FROM employees WHERE employee_no=? LIMIT 1');
    $quick->bind_param('s', $adminEmployeeNo); $quick->execute(); $quickRow = $quick->get_result()->fetch_assoc();
    if ($quickRow) {
        $_SESSION['employee_id']=(int)$quickRow['employee_id'];
        $_SESSION['employee_no']=$quickRow['employee_no'];
        $_SESSION['employee_name']=trim($quickRow['first_name'].' '.$quickRow['last_name']);
        header('Location: dashboard.php'); exit;
    }
}

$employeeNo = trim($_GET['employee'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeNo = trim($_POST['employee_no'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare('SELECT employee_id,employee_no,portal_password,first_name,last_name,employment_status FROM employees WHERE employee_no=? LIMIT 1');
    $stmt->bind_param('s', $employeeNo); $stmt->execute(); $row = $stmt->get_result()->fetch_assoc();
    $valid = false;
    if ($row && $row['employment_status'] !== '') {
        $stored=(string)($row['portal_password']??'');
        $valid=$stored!=='' && password_verify($password,$stored);
        if(!$valid && $stored!=='' && hash_equals($stored,$password)){$valid=true;$hash=password_hash($password,PASSWORD_DEFAULT);$up=$conn->prepare('UPDATE employees SET portal_password=? WHERE employee_id=?');$up->bind_param('si',$hash,$row['employee_id']);$up->execute();}
    }
    if($valid){session_regenerate_id(true);$_SESSION['employee_id']=(int)$row['employee_id'];$_SESSION['employee_no']=$row['employee_no'];$_SESSION['employee_name']=trim($row['first_name'].' '.$row['last_name']);header('Location: dashboard.php');exit;}
    $error='Incorrect employee ID or password.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<!-- FAVICON: Put your favicon file at /assets/favicon.png (replace the included LCC placeholder if desired). -->
<link rel="icon" type="image/png" href="../assets/favicon.png"><title>LCC Payroll System</title><link rel="stylesheet" href="../assets/css/login.css"><link rel="stylesheet" href="../assets/css/employee.css"></head><body class="employee-login">
<section class="brand-side"><div class="brand-content"><img class="login-logo" src="../uploads/logo1.jpg" alt="LCC Logo"><div class="eyebrow" style="color:#b8f1ee">LCC PAYROLL SYSTEM</div><h1>Your employee space, all in one place.</h1><p>Access your profile, payroll records, and daily attendance through a simple employee portal.</p></div></section>
<section class="login-side"><div class="login-card"><div class="eyebrow">EMPLOYEE PORTAL</div><h2>Welcome back</h2><p class="hint">Sign in with the Employee ID and password provided by your administrator.</p><?php if($error): ?><div class="error" role="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?><form method="post" autocomplete="on"><label for="employee_no">Employee ID</label><input id="employee_no" type="text" name="employee_no" value="<?php echo htmlspecialchars($employeeNo); ?>" placeholder="25-0001" autocomplete="username" required><label for="password">Password</label><input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" required><button type="submit">Sign In</button></form><a class="portal-link" href="../pages/login.php">← Administrator Login</a><div class="employee-login-note">Need access? Contact your payroll administrator.</div></div></section></body></html>
