<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

$db = bpo_db();
bpo_migrate($db);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $logId = (int)($_POST['log_id'] ?? 0);
    $status = trim((string)($_POST['status'] ?? ''));
    $remarks = trim((string)($_POST['remarks'] ?? ''));
    $allowed = ['Present', 'Late', 'Absent', 'On Leave', 'Half Day'];
    if ($logId > 0 && in_array($status, $allowed, true)) {
        $st = $db->prepare('UPDATE attendance_logs SET status = ?, remarks = ? WHERE log_id = ?');
        if ($st->execute([$status, $remarks, $logId])) {
            $message = 'Attendance record updated.';
        } else {
            $error = 'Unable to update the attendance record.';
        }
    } else {
        $error = 'Invalid attendance update.';
    }
}

// --- Filters: date XOR month, plus search/team/status ---
$rawDate = $_GET['date'] ?? '';
$rawMonth = $_GET['month'] ?? '';
$search = trim((string)($_GET['search'] ?? ''));
$teamFilter = trim((string)($_GET['team'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));

$useMonth = false;
$month = '';
$date = date('Y-m-d');
if (is_string($rawMonth) && preg_match('/^\d{4}-\d{2}$/', $rawMonth)) {
    $useMonth = true;
    $month = $rawMonth;
    $start = $month . '-01';
    $end = date('Y-m-t', strtotime($start));
    if (!$start || !$end) { $useMonth = false; $date = date('Y-m-d'); }
} else {
    if (is_string($rawDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawDate)) {
        $date = $rawDate;
    }
    $month = substr($date, 0, 7);
    $start = $date;
    $end = $date;
}

$allowedStatuses = ['Present', 'Late', 'Absent', 'On Leave', 'Half Day'];
if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatuses, true)) $statusFilter = '';

// Team options
$teams = [];
try {
    $tq = $db->query("SELECT DISTINCT team FROM members WHERE team IS NOT NULL AND team <> '' ORDER BY team ASC");
    if ($tq) foreach ($tq->fetchAll(PDO::FETCH_ASSOC) as $t) $teams[] = $t['team'];
} catch (Throwable $e) { $teams = []; }

// Month navigation
$prevMonth = date('Y-m', strtotime($start . ' -1 month'));
$nextMonth = date('Y-m', strtotime($start . ' +1 month'));
$qs = function (array $over = []) use ($useMonth, $date, $month, $search, $teamFilter, $statusFilter) {
    $p = [];
    if ($useMonth) $p['month'] = $over['month'] ?? $month;
    else $p['date'] = $over['date'] ?? $date;
    if (array_key_exists('search', $over)) $p['search'] = $over['search'];
    elseif ($search !== '') $p['search'] = $search;
    if (array_key_exists('team', $over)) $p['team'] = $over['team'];
    elseif ($teamFilter !== '') $p['team'] = $teamFilter;
    if (array_key_exists('status', $over)) $p['status'] = $over['status'];
    elseif ($statusFilter !== '') $p['status'] = $statusFilter;
    foreach (['search', 'team', 'status', 'month', 'date'] as $k) if (isset($p[$k]) && $p[$k] === '') unset($p[$k]);
    return 'attendance.php' . ($p ? '?' . http_build_query($p) : '');
};

// Query
$where = ['a.work_date BETWEEN ? AND ?'];
$params = [$start, $end];
if ($search !== '') { $where[] = 'm.full_name LIKE ?'; $params[] = '%' . $search . '%'; }
if ($teamFilter !== '') { $where[] = 'm.team = ?'; $params[] = $teamFilter; }
if ($statusFilter !== '') {
    if ($statusFilter === 'Late') {
        $where[] = "(a.status = 'Late')";
    } else {
        $where[] = 'a.status = ?'; $params[] = $statusFilter;
    }
}
$sql = 'SELECT a.*, m.full_name, m.team, m.hourly_rate, m.overtime_enabled FROM attendance_logs a JOIN members m ON m.member_id = a.member_id WHERE ' . implode(' AND ', $where) . ' ORDER BY a.work_date DESC, a.time_in ASC, m.full_name ASC';
$st = $db->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// KPIs
$timedIn = 0; $completed = 0; $late = 0;
foreach ($rows as $r) {
    if (!empty($r['time_in'])) $timedIn++;
    if (!empty($r['time_in']) && !empty($r['time_out'])) $completed++;
    if (($r['status'] ?? '') === 'Late') $late++;
}

$periodLabel = $useMonth ? date('F Y', strtotime($start)) : date('M d, Y', strtotime($date));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Attendance — BPO SUITE</title>
<link rel="stylesheet" href="../assets/style.css">
<script src="../assets/app.js" defer></script>
</head>
<body>
<div class="app">
<?php bpo_sidebar('attendance'); ?>
<main class="main">
<?php bpo_topbar('Attendance', 'BPO SUITE · Shift 8:00 AM-5:00 PM'); ?>
<div class="content">
<div class="page-heading"><div><div class="eyebrow">BPO SUITE · ADMIN</div><h1>Attendance · <?= h($periodLabel) ?></h1><p class="muted">Shift 8:00 AM-5:00 PM. Monitor time in / time out records.</p></div>
<div class="no-print" style="display:flex;gap:8px;flex-wrap:wrap">
<a class="btn secondary" href="<?= h($qs(['month' => $prevMonth])) ?>">← <?= h(date('M Y', strtotime($prevMonth . '-01'))) ?></a>
<a class="btn secondary" href="<?= h($qs(['month' => $nextMonth])) ?>"><?= h(date('M Y', strtotime($nextMonth . '-01'))) ?> →</a>
<button type="button" class="btn secondary" onclick="window.print()">Print</button>
</div></div>

<?php if ($message): ?><div class="notice ok no-print"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice err no-print"><?= h($error) ?></div><?php endif; ?>

<div class="kpi-grid no-print">
<div class="kpi"><span class="mini">Timed In</span><strong><?= (int)$timedIn ?></strong><small class="muted">Logs with a time in</small></div>
<div class="kpi"><span class="mini">Completed</span><strong><?= (int)$completed ?></strong><small class="muted">Time in and time out recorded</small></div>
<div class="kpi"><span class="mini">Late</span><strong><?= (int)$late ?></strong><small class="muted">Marked late</small></div>
</div>

<section class="card">
<div class="card-head"><div><div class="eyebrow">ATTENDANCE REPORT</div><h2><?= h($periodLabel) ?></h2><p class="muted"><?= count($rows) ?> record<?= count($rows) === 1 ? '' : 's' ?></p></div></div>
<form method="get" class="toolbar no-print">
<?php if ($useMonth): ?>
<div><label>Month<input type="month" name="month" value="<?= h($month) ?>"></label></div>
<?php else: ?>
<div><label>Date<input type="date" name="date" value="<?= h($date) ?>"></label></div>
<?php endif; ?>
<div><label>Search<input type="search" name="search" value="<?= h($search) ?>" placeholder="Member name"></label></div>
<div><label>Team<select name="team"><option value="">All teams</option><?php foreach ($teams as $t): ?><option value="<?= h($t) ?>"<?= $teamFilter === $t ? ' selected' : '' ?>><?= h($t) ?></option><?php endforeach; ?></select></label></div>
<div><label>Status<select name="status"><option value="">All statuses</option><?php foreach ($allowedStatuses as $s): ?><option value="<?= h($s) ?>"<?= $statusFilter === $s ? ' selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?></select></label></div>
<div><label>&nbsp;<span><button class="btn" type="submit">Apply</button> <a class="btn secondary" href="attendance.php">Reset</a></span></label></div>
</form>
<p class="muted no-print">Month view: <a href="attendance.php?month=<?= h($month) ?>">This month</a> · Day view: <a href="attendance.php?date=<?= h($useMonth ? date('Y-m-d') : $date) ?>">Today</a></p>
<div class="table-wrap"><table class="table">
<thead><tr><th>Date</th><th>Member</th><th>Team</th><th>In</th><th>Out</th><th>Status</th><th>Hours</th><th>Remarks</th><th class="no-print"></th></tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="9" class="muted">No attendance records match the selected filters.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?>
<?php
$rh = 0.0; $oh = 0.0;
if (!empty($r['time_in'])) { [$rh, $oh] = billable_window($r['work_date'], $r['time_in'], $r['time_out'] ?? null, (bool)$r['overtime_enabled']); }
$cls = in_array($r['status'], ['Late', 'Absent'], true) ? 'late' : 'ontime';
?>
<tr>
<td><?= h(date('M d, Y', strtotime($r['work_date']))) ?></td>
<td><strong><?= h($r['full_name']) ?></strong></td>
<td><?= h($r['team'] !== '' ? $r['team'] : '—') ?></td>
<td><?= $r['time_in'] ? h(date('h:i A', strtotime($r['time_in']))) : '—' ?></td>
<td><?= $r['time_out'] ? h(date('h:i A', strtotime($r['time_out']))) : '—' ?></td>
<td><span class="badge <?= h($cls) ?>"><?= h($r['status']) ?></span></td>
<td><?= number_format($rh + $oh, 2) ?>h<?= $oh > 0 ? ' (OT ' . number_format($oh, 2) . 'h)' : '' ?></td>
<td><?= $r['remarks'] !== '' ? h($r['remarks']) : '<span class="muted">—</span>' ?></td>
<td class="no-print"><button type="button" class="btn secondary" onclick='editLog(<?= json_encode(['log_id' => (int)$r['log_id'], 'status' => $r['status'], 'remarks' => $r['remarks']], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>Edit</button></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<p class="muted">Shift shown as 8:00 AM-5:00 PM.</p>
</section>
</div>
</main>
</div>

<div class="modal-overlay" id="logModal" aria-hidden="true"><div class="modal" role="dialog" aria-modal="true">
<div class="card-head"><div><div class="eyebrow">ATTENDANCE</div><h2>Edit record</h2></div><button type="button" class="btn secondary" onclick="closeBpoModal('logModal')">×</button></div>
<form method="post"><?= csrf_field() ?><input type="hidden" name="log_id" id="logId">
<label>Status<select name="status" id="logStatus"><?php foreach ($allowedStatuses as $s): ?><option value="<?= h($s) ?>"><?= h($s) ?></option><?php endforeach; ?></select></label>
<p></p><label>Remarks<textarea name="remarks" id="logRemarks" rows="4"></textarea></label>
<p><button type="button" class="btn secondary" onclick="closeBpoModal('logModal')">Cancel</button> <button class="btn" type="submit">Save changes</button></p>
</form>
</div></div>
<script>
function editLog(r){document.getElementById('logId').value=r.log_id;document.getElementById('logStatus').value=r.status;document.getElementById('logRemarks').value=r.remarks||'';openBpoModal('logModal');}
</script>
</body>
</html>
