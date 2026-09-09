<?php
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<!-- FAVICON: Put your favicon file at /assets/favicon.png (replace the included LCC placeholder if desired). -->
<link rel="icon" type="image/png" href="../assets/favicon.png">
    <title>LCC Payroll System</title>
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>
    <section class="brand-side">
        <div class="brand-content"><img class="login-logo" src="../uploads/logo1.jpg" alt="Company Logo">
            <div class="eyebrow" style="color:#b8f1ee">LCC Payroll System</div>
            <h1>Employee & Payroll Management</h1>
            <p>Lipa City Colleges payroll system.</p>
        </div>
    </section>
    <section class="login-side">
        <div class="login-card">
            <div class="eyebrow">Administrator</div>
            <h2>Welcome back</h2>
            <p class="hint">Sign in to open the payroll dashboard.</p><?php if (isset($_GET["error"])): ?><div class="error" role="alert">Incorrect username or password.</div><?php endif; ?><form method="post" action="../auth/login.php"><label>Username</label><input type="text" name="username" autocomplete="username" required><label>Password</label><input type="password" name="password" autocomplete="current-password" required><button type="submit">Sign In</button></form><a class="portal-link" href="../employee/login.php">Employee Portal <span>→</span></a>
        </div>
    </section>
</body>

</html>