<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

$db = bpo_db();
bpo_migrate($db);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'add_adjustment') {
        $memberId = (int)($_POST['member_id'] ?? 0);
        $workDate = trim((string)($_POST['work_date'] ?? ''));
        $label = trim((string)($_POST['label'] ?? ''));
        $amount = round((float)($_POST['amount'] ?? 0), 2);
        if ($memberId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $workDate) || $label === '' || $amount == 0.0) {
            $error = 'Adjustment needs a member, date, label and non-zero amount.';
        } else {
            $db->prepare('INSERT INTO adjustments (member_id, work_date, label, amount) VALUES (?, ?, ?, ?)')->execute([$memberId, $workDate, $label, $amount]);
            $message = 'Adjustment added.';
        }
    } elseif ($action === 'delete_adjustment') {
        $id = (int)($_POST['adjustment_id'] ?? 0);
        if ($id > 0) {
            $db->prepare('DELETE FROM adjustments WHERE adjustment_id = ?')->execute([$id]);
            $message = 'Adjustment removed.';
        } else {
            $error = 'Invalid adjustment.';
        }
    }
}

// --- Period: ?date= or ?month= ---
$rawDate = $_GET['date'] ?? '';
$rawMonth = $_GET['month'] ?? '';
$useMonth = false;
$month = date('Y-m');
$date = date('Y-m-d');
if (is_string($rawMonth) && preg_match('/^\d{4}-\d{2}$/', $rawMonth)) {
    $useMonth = true; $month = $rawMonth;
    $start = $month . '-01';
    $end = date('Y-m-t', strtotime($start));
} else {
    if (is_string($rawDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) $date = $rawDate;
    $month = substr($date, 0, 7);
    $start = $date; $end = $date;
}
$periodLabel = $useMonth ? date('F Y', strtotime($start)) : date('M d, Y', strtotime($date));

$st = $db->prepare('SELECT a.*, m.full_name, m.team, m.hourly_rate, m.overtime_enabled FROM attendance_logs a JOIN members m ON m.member_id = a.member_id WHERE a.work_date BETWEEN ? AND ? AND a.time_in IS NOT NULL ORDER BY m.full_name ASC, a.work_date ASC');
$st->execute([$start, $end]);
$logs = $st->fetchAll(PDO::FETCH_ASSOC);

$ast = $db->prepare('SELECT adj.*, m.full_name, m.team FROM adjustments adj JOIN members m ON m.member_id = adj.member_id WHERE adj.work_date BETWEEN ? AND ? ORDER BY adj.work_date ASC');
$ast->execute([$start, $end]);
$adjustments = $ast->fetchAll(PDO::FETCH_ASSOC);

$members = $db->query('SELECT member_id, full_name FROM members ORDER BY full_name ASC')->fetchAll(PDO::FETCH_ASSOC);

// Aggregate per member
$agg = [];
foreach ($logs as $r) {
    $mid = (int)$r['member_id'];
    if (!isset($agg[$mid])) $agg[$mid] = ['full_name' => $r['full_name'], 'team' => $r['team'], 'rate' => (float)$r['hourly_rate'], 'ot_on' => (bool)$r['overtime_enabled'], 'reg' => 0.0, 'ot' => 0.0, 'pay' => 0.0, 'adj' => 0.0, 'status' => $r['status']];
    [$rh, $oh] = billable_window($r['work_date'], $r['time_in'], $r['time_out'] ?? null, (bool)$r['overtime_enabled']);
    $agg[$mid]['reg'] += $rh;
    $agg[$mid]['ot'] += $oh;
    $agg[$mid]['pay'] += pay_for($rh, $oh, (float)$r['hourly_rate']);
    $agg[$mid]['status'] = $r['status'];
}
foreach ($adjustments as $a) {
    $mid = (int)$a['member_id'];
    if (!isset($agg[$mid])) $agg[$mid] = ['full_name' => $a['full_name'], 'team' => $a['team'] ?? '', 'rate' => 0.0, 'ot_on' => false, 'reg' => 0.0, 'ot' => 0.0, 'pay' => 0.0, 'adj' => 0.0, 'status' => '—'];
    $agg[$mid]['adj'] += (float)$a['amount'];
    // fill rate if missing
    if ($agg[$mid]['rate'] == 0.0) {
        $q = $db->prepare('SELECT hourly_rate, overtime_enabled FROM members WHERE member_id = ?');
        $q->execute([$mid]);
        if ($m = $q->fetch(PDO::FETCH_ASSOC)) { $agg[$mid]['rate'] = (float)$m['hourly_rate']; $agg[$mid]['ot_on'] = (bool)$m['overtime_enabled']; }
    }
}

$totalReg = 0.0; $totalOt = 0.0; $totalBase = 0.0; $totalAdj = 0.0; $totalNet = 0.0;
foreach ($agg as $a) { $totalReg += $a['reg']; $totalOt += $a['ot']; $totalBase += $a['pay']; $totalAdj += $a['adj']; $totalNet += $a['pay'] + $a['adj']; }

// By-team breakdown
$byTeam = [];
foreach ($agg as $a) {
    $t = $a['team'] !== '' ? $a['team'] : '(No team)';
    if (!isset($byTeam[$t])) $byTeam[$t] = ['count' => 0, 'net' => 0.0];
    $byTeam[$t]['count']++;
    $byTeam[$t]['net'] += $a['pay'] + $a['adj'];
}
ksort($byTeam);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payroll — BPO SUITE</title>
<link rel="stylesheet" href="../assets/style.css">
<script src="../assets/app.js" defer></script>
</head>
<body>
<div class="app">
<?php bpo_sidebar('payroll'); ?>
<main class="main">
<?php bpo_topbar('Payroll', 'BPO SUITE · ' . $periodLabel); ?>
<div class="content">
<div class="page-heading"><div><div class="eyebrow">BPO SUITE · ADMIN</div><h1>Hourly payroll · <?= h($periodLabel) ?></h1><p class="muted">Regular capped at 5:00 PM. Overtime only when the member overtime switch is ON (×<?= h(BPO_OVERTIME_MULTIPLIER) ?>).</p></div>
<div class="no-print"><button type="button" class="btn secondary" onclick="window.print()">Print</button></div></div>

<?php if ($message): ?><div class="notice ok no-print"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice err no-print"><?= h($error) ?></div><?php endif; ?>

<div class="kpi-grid">
<div class="kpi"><span class="mini">Base pay</span><strong><?= h(pesos($totalBase)) ?></strong><small class="muted"><?= number_format($totalReg, 2) ?>h regular + <?= number_format($totalOt, 2) ?>h OT</small></div>
<div class="kpi"><span class="mini">Adjustments</span><strong><?= h(pesos($totalAdj)) ?></strong><small class="muted"><?= count($adjustments) ?> entr<?= count($adjustments) === 1 ? 'y' : 'ies' ?></small></div>
<div class="kpi"><span class="mini">Net total</span><strong><?= h(pesos($totalNet)) ?></strong><small class="muted">Base pay + adjustments</small></div>
</div>

<section class="card">
<div class="card-head"><div><div class="eyebrow">PAYROLL REPORT</div><h2><?= h($periodLabel) ?></h2><p class="muted"><?= count($agg) ?> member<?= count($agg) === 1 ? '' : 's' ?></p></div></div>
<form method="get" class="toolbar no-print">
<?php if ($useMonth || (!isset($_GET['date']))): ?>
<div><label>Month<input type="month" name="month" value="<?= h($month) ?>"></label></div>
<?php else: ?>
<div><label>Date<input type="date" name="date" value="<?= h($date) ?>"></label></div>
<?php endif; ?>
<div><label>&nbsp;<span><button class="btn" type="submit">View</button> <a class="btn secondary" href="payroll.php?date=<?= h($date) ?>">Day</a> <a class="btn secondary" href="payroll.php?month=<?= h($month) ?>">Month</a></span></label></div>
</form>
<div class="table-wrap"><table class="table">
<thead><tr><th>Member</th><th>Rate</th><th>Regular</th><th>OT</th><th>Base pay</th><th>Adjustments</th><th>Net</th><th>Status</th></tr></thead>
<tbody>
<?php if (!$agg): ?><tr><td colspan="8" class="muted">No payable logs for this period.</td></tr><?php endif; ?>
<?php foreach ($agg as $a): $net = $a['pay'] + $a['adj']; ?>
<tr>
<td><strong><?= h($a['full_name']) ?></strong><div class="mini"><?= h($a['team'] !== '' ? $a['team'] : '—') ?></div></td>
<td><?= h(pesos($a['rate'])) ?>/hr</td>
<td><?= number_format($a['reg'], 2) ?>h</td>
<td><?= number_format($a['ot'], 2) ?>h<?= $a['ot'] > 0 ? '' : ($a['ot_on'] ? '' : '<div class="mini">OT off</div>') ?></td>
<td><?= h(pesos($a['pay'])) ?></td>
<td><?= h(pesos($a['adj'])) ?></td>
<td><strong><?= h(pesos($net)) ?></strong></td>
<td><span class="badge <?= h(in_array($a['status'], ['Late', 'Absent'], true) ? 'late' : 'ontime') ?>"><?= h($a['status']) ?></span></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<h2>Total: <?= h(pesos($totalNet)) ?> <small class="muted">(base <?= h(pesos($totalBase)) ?> + adj <?= h(pesos($totalAdj)) ?>)</small></h2>
<p class="muted">Regular capped at 5:00 PM. Overtime only for members with the admin switch ON (×<?= h(BPO_OVERTIME_MULTIPLIER) ?>).</p>
</section>

<section class="card">
<div class="card-head"><div><div class="eyebrow">BY TEAM</div><h2>Breakdown</h2></div></div>
<div class="table-wrap"><table class="table"><thead><tr><th>Team</th><th>Members</th><th>Net</th></tr></thead><tbody>
<?php if (!$byTeam): ?><tr><td colspan="3" class="muted">No data.</td></tr><?php endif; ?>
<?php foreach ($byTeam as $t => $b): ?><tr><td><?= h($t) ?></td><td><?= (int)$b['count'] ?></td><td><strong><?= h(pesos($b['net'])) ?></strong></td></tr><?php endforeach; ?>
</tbody></table></div>
</section>

<section class="card no-print">
<div class="card-head"><div><div class="eyebrow">ADJUSTMENTS</div><h2>Adjustments (included in totals)</h2><p class="muted">Base pay + adjustments = net. Use negative amounts for deductions.</p></div></div>
<form method="post" class="toolbar">
<?= csrf_field() ?><input type="hidden" name="action" value="add_adjustment">
<div><label>Member<select name="member_id" required><option value="">Select member</option><?php foreach ($members as $m): ?><option value="<?= (int)$m['member_id'] ?>"><?= h($m['full_name']) ?></option><?php endforeach; ?></select></label></div>
<div><label>Date<input type="date" name="work_date" value="<?= h($useMonth ? date('Y-m-d') : $date) ?>" required></label></div>
<div><label>Label<input name="label" required placeholder="e.g. Bonus / Deduction"></label></div>
<div><label>Amount (+/-)<input name="amount" type="number" step="0.01" required placeholder="150.00 / -50.00"></label></div>
<div><label>&nbsp;<span><button class="btn" type="submit">Add</button></span></label></div>
</form>
<div class="table-wrap"><table class="table"><thead><tr><th>Date</th><th>Member</th><th>Label</th><th>Amount</th><th></th></tr></thead><tbody>
<?php if (!$adjustments): ?><tr><td colspan="5" class="muted">No adjustments for this period.</td></tr><?php endif; ?>
<?php foreach ($adjustments as $a): ?><tr>
<td><?= h($a['work_date']) ?></td><td><?= h($a['full_name']) ?></td><td><?= h($a['label']) ?></td><td><?= h(pesos($a['amount'])) ?></td>
<td><form method="post" onsubmit="return confirm('Remove this adjustment?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete_adjustment"><input type="hidden" name="adjustment_id" value="<?= (int)$a['adjustment_id'] ?>"><button class="btn secondary" type="submit">Delete</button></form></td>
</tr><?php endforeach; ?>
</tbody></table></div>
</section>

</div>
</main>
</div>
</body>
</html>
