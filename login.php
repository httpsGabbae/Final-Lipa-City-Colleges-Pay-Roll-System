<?php
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<!-- FAVICON: Put your favicon file at /assets/favicon.png -->
<link rel="icon" type="image/png" href="assets/favicon.png">
    <title>Paywise</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/apple-system.css?v=20260916-apple9">
    <script src="assets/js/apple-motion.js?v=20260916-apple1" defer></script>
</head>

<body>
<script>try{if(localStorage.getItem("paywiseTheme")==="dark")document.body.classList.add("dark-mode");}catch(_e){}</script>
    <section class="brand-side">
        <div class="brand-content"><img class="login-logo" src="uploads/logo1.jpg" alt="Company Logo">
            <div class="eyebrow" style="color:#b8f1ee">Paywise</div>
            <h1>Payroll that feels effortless.</h1>
            <p>One calm workspace for people, attendance, and pay — accurate to the peso, ready to print, kind to every screen.</p>
        </div>
    </section>
    <section class="login-side">
        <div class="login-card">
            <div class="login-mobile-brand"><img src="uploads/logo1.jpg" alt="Paywise logo"><div><strong>PAYWISE</strong><span>Administrator Sign In</span></div></div>
            <div class="eyebrow">Administrator</div>
            <h2>Welcome back</h2>
            <p class="hint">Sign in to open the payroll dashboard.</p><?php if (isset($_GET["error"])): ?><div class="error" role="alert">Incorrect username or password.</div><?php endif; ?><form method="post" action="auth/login.php"><label>Username</label><input type="text" name="username" autocomplete="username" required><label>Password</label><input type="password" name="password" autocomplete="current-password" required><button type="submit">Sign In</button></form><a class="portal-link" href="employee/login.php">Employee Portal <span>→</span></a>
        </div>
    </section>
</body>

</html>
