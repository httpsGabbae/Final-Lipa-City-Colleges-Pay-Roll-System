<?php
require_once __DIR__ . '/../includes/layout.php';

$db = bpo_db();
bpo_migrate($db);

$today = date('Y-m-d');

$membersCount = (int)$db->query('SELECT COUNT(*) FROM members')->fetchColumn();
$otCount = (int)$db->query('SELECT COUNT(*) FROM members WHERE overtime_enabled = 1')->fetchColumn();

$timedIn = (int)$db->query("SELECT COUNT(*) FROM attendance_logs WHERE work_date = " . $db->quote($today) . " AND time_in IS NOT NULL")->fetchColumn();
$completed = (int)$db->query("SELECT COUNT(*) FROM attendance_logs WHERE work_date = " . $db->quote($today) . " AND time_in IS NOT NULL AND time_out IS NOT NULL")->fetchColumn();
$lateCount = (int)$db->query("SELECT COUNT(*) FROM attendance_logs WHERE work_date = " . $db->quote($today) . " AND status = 'Late'")->fetchColumn();

$st = $db->prepare('SELECT a.*, m.full_name, m.hourly_rate, m.overtime_enabled, m.team FROM attendance_logs a JOIN members m ON m.member_id = a.member_id WHERE a.work_date = ? AND a.time_in IS NOT NULL');
$st->execute([$today]);
$payRows = $st->fetchAll(PDO::FETCH_ASSOC);
$payTotal = 0.0;
$regHours = 0.0;
$otHours = 0.0;
foreach ($payRows as $r) {
    [$rh, $oh] = billable_window($r['work_date'], $r['time_in'], $r['time_out'], (bool)$r['overtime_enabled']);
    $payTotal += pay_for((float)$rh, (float)$oh, (float)$r['hourly_rate']);
    $regHours += $rh;
    $otHours += $oh;
}

$missingTeam = $db->query("SELECT member_id, full_name, email FROM members WHERE team IS NULL OR TRIM(team) = '' ORDER BY full_name LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$missingRate = $db->query('SELECT member_id, full_name, email, hourly_rate FROM members WHERE hourly_rate IS NULL OR hourly_rate <= 0 ORDER BY full_name LIMIT 10')->fetchAll(PDO::FETCH_ASSOC);
$lateRows = $db->prepare('SELECT a.time_in, m.full_name FROM attendance_logs a JOIN members m ON m.member_id = a.member_id WHERE a.work_date = ? AND a.status = ? ORDER BY a.time_in LIMIT 10');
$lateRows->execute([$today, 'Late']);
$lateList = $lateRows->fetchAll(PDO::FETCH_ASSOC);
$attentionCount = count($missingTeam) + count($missingRate) + count($lateList);

$snap = $db->prepare('SELECT a.*, m.full_name, m.hourly_rate, m.overtime_enabled FROM attendance_logs a JOIN members m ON m.member_id = a.member_id WHERE a.work_date = ? ORDER BY a.time_in DESC LIMIT 8');
$snap->execute([$today]);
$snapshot = $snap->fetchAll(PDO::FETCH_ASSOC);

$hour = (int)date('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — BPO SUITE</title><link rel="stylesheet" href="../assets/style.css"></head>
<body><div class="app">
<?php bpo_sidebar('dashboard'); ?>
<div class="main">
<?php bpo_topbar('Dashboard', 'BPO SUITE · ' . date('M d, Y')); ?>
<div class="content">

<div class="hero"><div><div class="eyebrow" style="color:rgba(255,255,255,.75)">BPO SUITE · ADMIN OVERVIEW</div>
<h1 style="margin:6px 0;color:#fff"><?= h($greet) ?> — today at a glance</h1>
<p><?= (int)$timedIn ?> timed in · <?= (int)$completed ?> completed · <?= h(pesos($payTotal)) ?> payroll today. Shift 8:00 AM – 5:00 PM.</p></div>
<div><a class="btn" style="background:#fff;color:#063b46" href="payroll.php?date=<?= h($today) ?>">View payroll</a></div></div>

<div class="quick-actions">
<a class="quick-action" href="members.php"><?= bpo_icon('users') ?><span><strong>Add Member</strong><br><span class="mini">Rates + overtime switch</span></span></a>
<a class="quick-action" href="attendance.php?date=<?= h($today) ?>"><?= bpo_icon('clock') ?><span><strong>Review Attendance</strong><br><span class="mini">Today's time logs</span></span></a>
<a class="quick-action" href="../employee/time_in.php"><?= bpo_icon('login') ?><span><strong>Agent Time In</strong><br><span class="mini">Google-gated clock in</span></span></a>
<a class="quick-action" href="payroll.php?date=<?= h($today) ?>"><?= bpo_icon('wallet') ?><span><strong>View Payroll</strong><br><span class="mini">Hourly + overtime pay</span></span></a>
</div>

<div class="stats">
<div class="card stat"><div class="eyebrow">MEMBERS</div><div class="stat-number"><?= (int)$membersCount ?></div><div class="mini"><?= (int)$otCount ?> overtime enabled</div></div>
<div class="card stat"><div class="eyebrow">ATTENDANCE TODAY</div><div class="stat-number"><?= (int)$timedIn ?></div><div class="mini"><?= (int)$completed ?> completed · <?= (int)$lateCount ?> late</div></div>
<div class="card stat"><div class="eyebrow">PAYROLL TODAY</div><div class="stat-number"><?= h(pesos($payTotal)) ?></div><div class="mini"><?= h(number_format($regHours, 2)) ?> regular hrs · <?= h(number_format($otHours, 2)) ?> OT hrs</div></div>
<div class="card stat"><div class="eyebrow">OT ENABLED</div><div class="stat-number"><?= (int)$otCount ?></div><div class="mini">Per-member overtime switch</div></div>
</div>

<div class="grid2">
<div class="card"><div class="card-head"><div><div class="eyebrow">PAYROLL OVERVIEW</div><h2>Today · <?= h($today) ?></h2></div><a class="btn secondary" href="payroll.php?date=<?= h($today) ?>">Open payroll</a></div>
<div class="kpi-grid">
<div class="kpi"><span class="mini">PAYABLE LOGS</span><strong><?= (int)count($payRows) ?></strong></div>
<div class="kpi"><span class="mini">REGULAR HOURS</span><strong><?= h(number_format($regHours, 2)) ?></strong></div>
<div class="kpi"><span class="mini">OVERTIME HOURS</span><strong><?= h(number_format($otHours, 2)) ?></strong></div>
</div>
<p class="muted">Pay starts at Time In (early arrivals billed from 8:00 AM) and stops at Time Out or 5:00 PM. Overtime past 5:00 PM accrues only for members with the switch ON (×<?= h(BPO_OVERTIME_MULTIPLIER) ?>).</p>
<p><a class="btn secondary" href="members.php">Manage members</a> <a class="btn secondary" href="attendance.php?date=<?= h($today) ?>">Review attendance</a></p></div>

<div class="card"><div class="card-head"><div><div class="eyebrow">NEEDS ATTENTION</div><h2><?= (int)$attentionCount ?> open items</h2></div><a class="btn secondary" href="members.php">Fix now</a></div>
<?php if ($attentionCount === 0): ?><p class="muted">All clear — every member has a team and rate, and nobody is late today.</p><?php endif; ?>
<?php if ($missingTeam): ?><h3>Missing team (<?= count($missingTeam) ?>)</h3><?php foreach ($missingTeam as $m): ?><div class="mini">• <?= h($m['full_name']) ?> <span class="muted"><?= h($m['email']) ?></span></div><?php endforeach; ?><?php endif; ?>
<?php if ($missingRate): ?><h3 style="margin-top:10px">Missing rate (<?= count($missingRate) ?>)</h3><?php foreach ($missingRate as $m): ?><div class="mini">• <?= h($m['full_name']) ?> — <?= h(pesos($m['hourly_rate'])) ?>/hr</div><?php endforeach; ?><?php endif; ?>
<?php if ($lateList): ?><h3 style="margin-top:10px">Late today (<?= count($lateList) ?>)</h3><?php foreach ($lateList as $l): ?><div class="mini">• <?= h($l['full_name']) ?> — in at <?= h(date('h:i A', strtotime($l['time_in']))) ?> <span class="badge late">Late</span></div><?php endforeach; ?><?php endif; ?>
</div>
</div>

<div class="card"><div class="card-head"><div><div class="eyebrow">ATTENDANCE SNAPSHOT</div><h2>Latest logs · <?= h($today) ?></h2></div><a class="btn secondary" href="attendance.php?date=<?= h($today) ?>">Full log</a></div>
<div class="table-wrap"><table class="table"><thead><tr><th>Member</th><th>In</th><th>Out</th><th>Status</th><th>Hours</th></tr></thead><tbody>
<?php if (!$snapshot): ?><tr><td colspan="5" class="muted">No logs yet today.</td></tr><?php endif; ?>
<?php foreach ($snapshot as $r): [$rh, $oh] = billable_window($r['work_date'], $r['time_in'], $r['time_out'], (bool)$r['overtime_enabled']); ?>
<tr><td><strong><?= h($r['full_name']) ?></strong></td>
<td><?= $r['time_in'] ? h(date('h:i A', strtotime($r['time_in']))) : '—' ?></td>
<td><?= $r['time_out'] ? h(date('h:i A', strtotime($r['time_out']))) : 'running' ?></td>
<td><span class="badge <?= $r['status'] === 'Late' ? 'late' : 'ontime' ?>"><?= h($r['status']) ?></span></td>
<td><?= h(number_format($rh + $oh, 2)) ?>h<?= $oh > 0 ? ' (OT ' . h(number_format($oh, 2)) . 'h)' : '' ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>

</div>
</div>
</div>
<script src="../assets/app.js"></script>
</body></html>
