<?php require_once __DIR__ . '/includes/helpers.php'; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BPO SUITE — Hourly Timekeeping</title><link rel="stylesheet" href="assets/style.css"></head>
<body><div class="wrap">
<div class="hero"><div><div class="eyebrow" style="color:rgba(255,255,255,.75)">BPO SUITE · HOURLY OFFER</div>
<h1 style="color:#fff">Hourly timekeeping for BPO teams</h1>
<p>Google-gated Time In. Shift 8:00 AM – 5:00 PM. Pay runs per hour, with per-member overtime.</p></div>
<div><a class="btn" style="background:#fff;color:#063b46" href="admin/dashboard.php">Open dashboard</a></div></div>

<div class="quick-actions">
<a class="quick-action" href="admin/dashboard.php"><span><strong>Admin Dashboard</strong><br><span class="mini">KPIs, payroll + attention</span></span></a>
<a class="quick-action" href="employee/time_in.php"><span><strong>Agent Time In</strong><br><span class="mini">Google-gated clock in</span></span></a>
<a class="quick-action" href="admin/members.php"><span><strong>Members</strong><br><span class="mini">Rates + overtime switch</span></span></a>
<a class="quick-action" href="admin/attendance.php"><span><strong>Attendance</strong><br><span class="mini">Daily time logs</span></span></a>
</div>

<div class="stats">
<div class="card stat"><div class="eyebrow">SHIFT</div><div class="stat-number" style="font-size:22px">8:00 AM – 5:00 PM</div><div class="mini">Late flagged internally; cutoff never shown</div></div>
<div class="card stat"><div class="eyebrow">TIME IN</div><div class="stat-number" style="font-size:22px">Google-gated</div><div class="mini">Sign in with Google to unlock clock in</div></div>
<div class="card stat"><div class="eyebrow">PAY MODEL</div><div class="stat-number" style="font-size:22px">Hourly</div><div class="mini">Per-member overtime past 5:00 PM</div></div>
<div class="card stat"><div class="eyebrow">PAYROLL</div><div class="stat-number" style="font-size:22px">Daily totals</div><div class="mini"><a href="admin/payroll.php" style="text-decoration:underline">Open payroll</a></div></div>
</div>

<div class="grid2">
<div class="card"><div class="eyebrow">HOW TIME IN WORKS</div><h2>Google sign-in unlocks the clock</h2>
<p class="muted">Agents sign in with Google first, then Time In starts pay and Time Out stops it. Early arrivals are billed from 8:00 AM. Demo mode works with no keys; add OAuth keys later via <code>.env</code> + <code>config/google.php</code>.</p>
<p><a class="btn" href="employee/time_in.php">Agent Time In</a> <a class="btn secondary" href="admin/dashboard.php">Admin dashboard</a></p></div>
<div class="card"><div class="eyebrow">HOW PAY WORKS</div><h2>8:00 AM – 5:00 PM, hourly + overtime</h2>
<p class="muted">Regular hours run from Time In to Time Out capped at 5:00 PM. Overtime past 5:00 PM accrues only when an admin enables it per member (×<?= h(BPO_OVERTIME_MULTIPLIER) ?>). Late status is recorded without ever displaying the grace cutoff.</p>
<p><a class="btn secondary" href="admin/members.php">Members</a> <a class="btn secondary" href="admin/attendance.php">Attendance</a> <a class="btn secondary" href="admin/payroll.php">Payroll</a></p></div>
</div>
</div>
<script src="assets/app.js"></script>
</body></html>
