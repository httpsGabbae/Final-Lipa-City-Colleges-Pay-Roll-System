<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['attendance_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Present');
    $remarks = trim($_POST['remarks'] ?? '');
    if ($id > 0 && in_array($status, ['Present', 'Late', 'Absent', 'On Leave', 'Half Day'], true)) {
        $stmt = $conn->prepare('UPDATE attendance SET status=?,remarks=? WHERE attendance_id=?');
        $stmt->bind_param('ssi', $status, $remarks, $id);
        if ($stmt->execute()) $message = 'Attendance record updated.';
        else $error = 'Unable to update the attendance record.';
    }
}

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
$department = trim($_GET['department'] ?? '');
$position = trim($_GET['position'] ?? '');
$employeeId = (int)($_GET['employee_id'] ?? 0);

$departments = [];
$dres = $conn->query("SELECT department_name FROM departments ORDER BY department_name ASC");
if ($dres) while ($d = $dres->fetch_assoc()) $departments[] = $d['department_name'];

$positions = [];
$pres = $conn->query("SELECT DISTINCT position FROM employees WHERE position IS NOT NULL AND position <> '' ORDER BY position ASC");
if ($pres) while ($p = $pres->fetch_assoc()) $positions[] = $p['position'];

$employees = [];
$eres = $conn->query("SELECT employee_id,employee_no,first_name,last_name FROM employees ORDER BY last_name,first_name");
if ($eres) while ($e = $eres->fetch_assoc()) $employees[] = $e;

$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));
$where = ['a.attendance_date BETWEEN ? AND ?'];
$params = [$start, $end];
$types = 'ss';

if ($department !== '') { $where[] = 'e.department = ?'; $params[] = $department; $types .= 's'; }
if ($position !== '') { $where[] = 'e.position = ?'; $params[] = $position; $types .= 's'; }
if ($employeeId > 0) { $where[] = 'e.employee_id = ?'; $params[] = $employeeId; $types .= 'i'; }

$sql = 'SELECT a.*,e.employee_no,e.first_name,e.last_name,e.department,e.position FROM attendance a JOIN employees e ON e.employee_id=a.employee_id WHERE ' . implode(' AND ', $where) . ' ORDER BY a.attendance_date DESC,e.last_name,e.first_name';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result();

// Today's summary follows the same department/position/person filters.
$todayWhere = ['a.attendance_date = ?'];
$todayParams = [date('Y-m-d')];
$todayTypes = 's';
if ($department !== '') { $todayWhere[] = 'e.department = ?'; $todayParams[] = $department; $todayTypes .= 's'; }
if ($position !== '') { $todayWhere[] = 'e.position = ?'; $todayParams[] = $position; $todayTypes .= 's'; }
if ($employeeId > 0) { $todayWhere[] = 'e.employee_id = ?'; $todayParams[] = $employeeId; $todayTypes .= 'i'; }
$summarySql = 'SELECT COUNT(CASE WHEN a.time_in IS NOT NULL THEN 1 END) timed_in, COUNT(CASE WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL THEN 1 END) completed, COUNT(CASE WHEN a.status="Late" THEN 1 END) late_count FROM attendance a JOIN employees e ON e.employee_id=a.employee_id WHERE ' . implode(' AND ', $todayWhere);
$summary = $conn->prepare($summarySql);
$summary->bind_param($todayTypes, ...$todayParams);
$summary->execute();
$summaryRow = $summary->get_result()->fetch_assoc();
$today = (int)($summaryRow['timed_in'] ?? 0);
$complete = (int)($summaryRow['completed'] ?? 0);
$late = (int)($summaryRow['late_count'] ?? 0);

$filterLabel = 'All employees';
if ($employeeId > 0) {
    foreach ($employees as $emp) if ((int)$emp['employee_id'] === $employeeId) $filterLabel = $emp['last_name'] . ', ' . $emp['first_name'];
} elseif ($department !== '' && $position !== '') $filterLabel = $department . ' · ' . $position;
elseif ($department !== '') $filterLabel = $department;
elseif ($position !== '') $filterLabel = $position;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<!-- FAVICON: Put your favicon file at /assets/favicon.png (replace the included LCC placeholder if desired). -->
<link rel="icon" type="image/png" href="../assets/favicon.png">
<title>LCC Payroll System</title>
<link rel="stylesheet" href="../assets/css/app.css?v=20260909-rail4"><script src="../assets/js/app.js?v=20260909-rail4" defer></script>
</head>
<body>
<div class="app">
<?php sidebar('attendance'); ?>
<main class="main">
<?php topbar('Attendance'); ?>
<div class="content attendance-page">
<div class="page-heading"><div><div class="eyebrow">WORKFORCE</div><h1>Attendance</h1><p>Monitor employee time in and time out records.</p></div></div>
<?php if ($message): ?><div class="notice ok no-print"><?php echo e($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice err no-print"><?php echo e($error); ?></div><?php endif; ?>

<div class="dashboard-kpis attendance-kpis no-print">
    <div class="kpi card"><span class="kpi-label">Timed In Today</span><strong><?php echo $today; ?></strong><small>Employees with a time in</small></div>
    <div class="kpi card"><span class="kpi-label">Completed Today</span><strong><?php echo $complete; ?></strong><small>Time in and time out recorded</small></div>
    <div class="kpi card"><span class="kpi-label">Late Today</span><strong><?php echo $late; ?></strong><small>Marked late</small></div>
</div>

<section class="card attendance-list-card">
    <div class="card-head attendance-print-head">
        <div><div class="eyebrow">ATTENDANCE REPORT</div><h2><?php echo e(date('F Y', strtotime($start))); ?></h2><p>Showing <?php echo e($filterLabel); ?> · <?php echo (int)$rows->num_rows; ?> record<?php echo $rows->num_rows === 1 ? '' : 's'; ?></p></div>
        <div class="attendance-print-actions no-print">
            <button type="button" class="btn btn-secondary" onclick="window.print()">Print Attendance</button>
        </div>
    </div>
    <form method="get" class="attendance-filters no-print">
        <div><label>Month</label><input type="month" name="month" value="<?php echo e($month); ?>"></div>
        <div><label>Department</label><select name="department"><option value="">All Departments</option><?php foreach ($departments as $d): ?><option value="<?php echo e($d); ?>" <?php echo $department === $d ? 'selected' : ''; ?>><?php echo e($d); ?></option><?php endforeach; ?></select></div>
        <div><label>Position</label><select name="position"><option value="">All Positions</option><?php foreach ($positions as $p): ?><option value="<?php echo e($p); ?>" <?php echo $position === $p ? 'selected' : ''; ?>><?php echo e($p); ?></option><?php endforeach; ?></select></div>
        <div><label>Employee</label><select name="employee_id"><option value="0">All Employees</option><?php foreach ($employees as $emp): ?><option value="<?php echo (int)$emp['employee_id']; ?>" <?php echo $employeeId === (int)$emp['employee_id'] ? 'selected' : ''; ?>><?php echo e($emp['last_name'] . ', ' . $emp['first_name'] . ' · ' . $emp['employee_no']); ?></option><?php endforeach; ?></select></div>
        <button class="btn btn-primary" type="submit">Apply Filters</button>
        <a class="btn btn-secondary" href="attendance.php">Reset</a>
    </form>
    <div class="attendance-report-meta print-only"><strong>LCC PAYROLL SYSTEM</strong><span>Attendance Report · <?php echo e(date('F Y', strtotime($start))); ?></span><span><?php echo e($filterLabel); ?></span></div>
    <div class="table-wrap">
    <table class="table attendance-table">
        <thead><tr><th>Date</th><th>Employee</th><th>Department</th><th>Position</th><th>Time In</th><th>Time Out</th><th>Status</th><th class="no-print"></th></tr></thead>
        <tbody>
        <?php if (!$rows->num_rows): ?><tr><td colspan="8" class="empty">No attendance records match the selected filters.</td></tr>
        <?php else: while ($r = $rows->fetch_assoc()): ?>
        <tr>
            <td><?php echo e(date('M d, Y', strtotime($r['attendance_date']))); ?></td>
            <td><strong><?php echo e($r['last_name'] . ', ' . $r['first_name']); ?></strong><small class="table-sub"><?php echo e($r['employee_no']); ?></small></td>
            <td><?php echo e($r['department'] ?: '—'); ?></td>
            <td><?php echo e($r['position'] ?: '—'); ?></td>
            <td><?php echo $r['time_in'] ? e(date('h:i A', strtotime($r['time_in']))) : '—'; ?></td>
            <td><?php echo $r['time_out'] ? e(date('h:i A', strtotime($r['time_out']))) : '—'; ?></td>
            <td><span class="badge attendance-badge-<?php echo strtolower(str_replace(' ', '-', $r['status'])); ?>"><?php echo e($r['status']); ?></span></td>
            <td class="no-print"><button type="button" class="icon-action edit" title="Edit attendance" onclick='editAttendance(<?php echo json_encode($r, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>✎</button></td>
        </tr>
        <?php endwhile; endif; ?>
        </tbody>
    </table>
    </div>
    <div class="attendance-print-footer print-only">Generated <?php echo e(date('M d, Y h:i A')); ?> · LCC Payroll System</div>
</section>
</div></main></div>

<div class="detail-modal-overlay" id="attendanceModal" aria-hidden="true"><div class="detail-modal" role="dialog" aria-modal="true"><div class="detail-modal-head"><div><div class="eyebrow">ATTENDANCE</div><h2>Edit Record</h2></div><button type="button" class="modal-icon-close" onclick="closeAttendance()">×</button></div><div class="detail-modal-body"><form method="post"><input type="hidden" name="attendance_id" id="attId"><label>Status<select name="status" id="attStatus"><option>Present</option><option>Late</option><option>Absent</option><option>On Leave</option><option>Half Day</option></select></label><label>Remarks<textarea name="remarks" id="attRemarks" rows="4"></textarea></label><div class="settings-actions"><button type="button" class="btn btn-secondary" onclick="closeAttendance()">Cancel</button><button class="btn btn-primary">Save Changes</button></div></form></div></div></div>
<script>
function editAttendance(r){document.getElementById('attId').value=r.attendance_id;document.getElementById('attStatus').value=r.status;document.getElementById('attRemarks').value=r.remarks||'';document.getElementById('attendanceModal').classList.add('show');document.getElementById('attendanceModal').setAttribute('aria-hidden','false')}
function closeAttendance(){document.getElementById('attendanceModal').classList.remove('show');document.getElementById('attendanceModal').setAttribute('aria-hidden','true')}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeAttendance()});
</script>
</body></html>
