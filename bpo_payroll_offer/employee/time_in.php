<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';
$google = require __DIR__ . '/../config/google.php';
$db = bpo_db();
bpo_migrate($db);

$today = date('Y-m-d');
$msg = '';
$err = '';

// Member picker: switch active member (demo convenience).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'pick_member') {
    require_csrf();
    $mid = (int)($_POST['member_id'] ?? 0);
    if ($mid > 0) {
        $chk = $db->prepare('SELECT member_id FROM members WHERE member_id = ? LIMIT 1');
        $chk->execute([$mid]);
        if ($chk->fetch(PDO::FETCH_ASSOC)) $_SESSION['member_id'] = $mid;
    }
    header('Location: time_in.php');
    exit;
}
if (!isset($_SESSION['member_id'])) {
    $first = $db->query('SELECT member_id FROM members ORDER BY member_id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    if ($first) $_SESSION['member_id'] = (int)$first['member_id'];
}
$member = current_member($db);
$allMembers = $db->query('SELECT member_id, full_name, email, hourly_rate, overtime_enabled FROM members ORDER BY full_name')->fetchAll(PDO::FETCH_ASSOC);

// Google gate: demo stub + real callback stub.
$action = $_GET['action'] ?? '';
if ($action === 'google_callback' && ($google['mode'] ?? 'demo') === 'real') {
    // Real wiring point: exchange code, verify ID token, match email to members.email.
    $err = 'Real Google callback not configured. Set GOOGLE_* in .env and implement token verify here.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'google_demo') {
    require_csrf();
    $_SESSION['google_user'] = ['name' => 'Demo Agent', 'email' => $member['email'] ?? 'demo.agent@example.com'];
    header('Location: time_in.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'google_logout') {
    require_csrf();
    unset($_SESSION['google_user']);
    header('Location: time_in.php');
    exit;
}

$gated = (bool)google_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['do'] ?? '', ['in', 'out'], true)) {
    require_csrf();
    if (!$gated || !$member) {
        $err = 'Sign in with Google first.';
    } else {
        $now = date('Y-m-d H:i:s');
        $row = $db->prepare('SELECT * FROM attendance_logs WHERE member_id = ? AND work_date = ? LIMIT 1');
        $row->execute([$member['member_id'], $today]);
        $log = $row->fetch(PDO::FETCH_ASSOC);
        if (($_POST['do'] ?? '') === 'in') {
            if ($log && $log['time_in']) {
                $err = 'Already timed in today.';
            } else {
                $status = attendance_status_for($today, $now);
                if ($log) {
                    $db->prepare('UPDATE attendance_logs SET time_in = ?, status = ?, source = ? WHERE log_id = ?')->execute([$now, $status, 'web', $log['log_id']]);
                } else {
                    $db->prepare("INSERT INTO attendance_logs (member_id, work_date, time_in, status, source) VALUES (?, ?, ?, ?, 'web')")->execute([$member['member_id'], $today, $now, $status]);
                }
                $msg = 'Timed in at ' . date('h:i A', strtotime($now)) . '. Pay started.';
            }
        } else {
            if (!$log || !$log['time_in']) {
                $err = 'Time in first.';
            } elseif ($log['time_out']) {
                $err = 'Already timed out.';
            } else {
                $db->prepare('UPDATE attendance_logs SET time_out = ? WHERE log_id = ?')->execute([$now, $log['log_id']]);
                $msg = 'Timed out at ' . date('h:i A', strtotime($now)) . '. Pay stopped.';
            }
        }
    }
}

$log = null;
if ($member) {
    $st = $db->prepare('SELECT * FROM attendance_logs WHERE member_id = ? AND work_date = ? LIMIT 1');
    $st->execute([$member['member_id'], $today]);
    $log = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
$running = null;
if ($member && $log && $log['time_in'] && !$log['time_out']) {
    [$rh, $oh] = billable_window($today, $log['time_in'], null, (bool)$member['overtime_enabled']);
    $running = ['hours' => $rh + $oh, 'reg' => $rh, 'ot' => $oh, 'pay' => pay_for($rh, $oh, (float)$member['hourly_rate'])];
}

// This-week summary (last 7 days incl. today).
$weekRows = [];
$weekTotal = 0.0;
$weekHours = 0.0;
if ($member) {
    $wst = $db->prepare('SELECT * FROM attendance_logs WHERE member_id = ? AND work_date <= ? ORDER BY work_date DESC LIMIT 7');
    $wst->execute([$member['member_id'], $today]);
    foreach ($wst->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if (empty($r['time_in'])) {
            $weekRows[] = ['date' => $r['work_date'], 'in' => null, 'out' => null, 'hours' => 0, 'pay' => 0];
            continue;
        }
        [$rh, $oh] = billable_window($r['work_date'], $r['time_in'], $r['time_out'] ?? null, (bool)$member['overtime_enabled']);
        $pay = pay_for($rh, $oh, (float)$member['hourly_rate']);
        $weekRows[] = ['date' => $r['work_date'], 'in' => $r['time_in'], 'out' => $r['time_out'] ?? null, 'hours' => $rh + $oh, 'pay' => $pay];
        $weekTotal += $pay;
        $weekHours += $rh + $oh;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Agent Time In — BPO SUITE</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="app">
<?php bpo_sidebar('timein'); ?>
<main class="main">
<?php bpo_topbar('Agent Time In', 'BPO SUITE · shift 8:00 AM – 5:00 PM'); ?>
<div class="content">
<?php if ($msg): ?><div class="notice ok"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="notice err"><?= h($err) ?></div><?php endif; ?>

<section class="card">
<div class="eyebrow">BPO SUITE · AGENT</div>
<h2 style="margin:6px 0">Time In starts pay · Time Out stops pay</h2>
<p class="mini"><?= h($member['full_name'] ?? 'No member') ?> · <?= pesos($member['hourly_rate'] ?? 0) ?>/hour<?= $member && !empty($member['overtime_enabled']) ? ' · Overtime ON' : ' · Overtime OFF' ?></p>
<form class="toolbar" method="post">
<?= csrf_field() ?><input type="hidden" name="do" value="pick_member">
<div><label>Member</label><select name="member_id"><?php foreach ($allMembers as $am): ?><option value="<?= (int)$am['member_id'] ?>" <?= $member && (int)$member['member_id'] === (int)$am['member_id'] ? 'selected' : '' ?>><?= h($am['full_name']) ?> — <?= h($am['email']) ?></option><?php endforeach; ?></select></div>
<button class="btn secondary" type="submit">Switch</button>
</form>
<?php if (!$gated): ?>
<p class="mini">Step 1 — sign in with Google to unlock Time In.</p>
<form method="post"><?= csrf_field() ?><input type="hidden" name="do" value="google_demo"><button class="btn" type="submit">Continue with Google (Demo)</button></form>
<p class="mini">Production: set <code>GOOGLE_MODE=real</code> + OAuth keys; the callback is stubbed at <code>?action=google_callback</code>.</p>
<?php else: ?>
<p><span class="badge ontime">Google: <?= h(google_user()['email']) ?></span></p>
<form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="google_logout"><button class="btn secondary" type="submit">Switch Google account</button></form>
<?php endif; ?>
</section>

<div class="grid2">
<section class="card">
<div class="eyebrow">TODAY · <?= h(date('M d, Y')) ?></div>
<p class="mini">Shift 8:00 AM – 5:00 PM · Status:
<?php if (!$log || !$log['time_in']): ?>Not timed in<?php else: ?><span class="badge <?= h($log['status']) === 'Late' ? 'late' : 'ontime' ?>"><?= h($log['status'] === 'Late' ? 'Late' : 'OnTime') ?></span> in at <?= h(date('h:i A', strtotime($log['time_in']))) ?><?= $log['time_out'] ? ' · out ' . h(date('h:i A', strtotime($log['time_out']))) : ' · running' ?><?php endif; ?></p>
<?php if ($running): ?><div class="big timer"><?= pesos($running['pay']) ?></div><p class="mini"><?= number_format($running['hours'], 2) ?> hrs so far (reg <?= number_format($running['reg'], 2) ?>h<?= $running['ot'] > 0 ? ' · OT ' . number_format($running['ot'], 2) . 'h' : '' ?>)</p><?php endif; ?>
<p>
<form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="in"><button class="btn" type="submit" <?= (!$gated || ($log && $log['time_in'])) ? 'disabled' : '' ?>>Time In — start pay</button></form>
<form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="out"><button class="btn secondary" type="submit" <?= (!$gated || !$log || !$log['time_in'] || $log['time_out']) ? 'disabled' : '' ?>>Time Out — stop pay</button></form>
</p>
<p class="mini">Pay starts at Time In (early arrivals billed from 8:00 AM) and stops at Time Out or 5:00 PM. Overtime past 5:00 PM accrues only when enabled (×<?= h(BPO_OVERTIME_MULTIPLIER) ?>).</p>
</section>
<section class="card">
<div class="eyebrow">BPO SUITE · RULES</div>
<h2>How pay runs</h2>
<p class="mini">Time In opens a billable window; Time Out closes it. Early clock-ins start at 8:00 AM. Regular hours cap at 5:00 PM<?= $member && !empty($member['overtime_enabled']) ? ' · overtime enabled for this member' : ' · overtime off for this member' ?>.</p>
<p><a class="btn secondary" href="../index.php">Back</a></p>
</section>
</div>

<section class="card">
<div class="card-head"><div><div class="eyebrow">BPO SUITE · THIS WEEK</div><h2>Summary</h2><p class="mini"><?= number_format($weekHours, 2) ?> hrs · <?= pesos($weekTotal) ?> this week</p></div></div>
<div class="table-wrap">
<table class="table">
<thead><tr><th>Date</th><th>In</th><th>Out</th><th>Hours</th><th>Pay</th></tr></thead>
<tbody>
<?php if (!$weekRows): ?><tr><td colspan="5" class="mini">No logs this week.</td></tr><?php endif; ?>
<?php foreach ($weekRows as $w): ?>
<tr><td><?= h(date('M d, Y', strtotime($w['date']))) ?></td><td><?= $w['in'] ? h(date('h:i A', strtotime($w['in']))) : '—' ?></td><td><?= $w['out'] ? h(date('h:i A', strtotime($w['out']))) : ($w['in'] ? 'running' : '—') ?></td><td><?= number_format($w['hours'], 2) ?>h</td><td><strong><?= pesos($w['pay']) ?></strong></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>

</div>
</main>
</div>
<script>document.getElementById('themeToggle')?.addEventListener('click',()=>document.body.classList.toggle('light'));</script>
</body>
</html>
