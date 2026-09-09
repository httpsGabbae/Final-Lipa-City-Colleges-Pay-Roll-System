<?php
require_once __DIR__.'/auth.php';
require_once __DIR__.'/layout.php';

$employee = current_employee($conn);
if (!$employee) { header('Location: logout.php'); exit; }

$attStmt = $conn->prepare("SELECT time_in,time_out,status FROM attendance WHERE employee_id=? AND attendance_date=CURDATE() LIMIT 1");
$attStmt->bind_param('i', $employee['employee_id']);
$attStmt->execute();
$todayAttendance = $attStmt->get_result()->fetch_assoc();

$payStmt = $conn->prepare("SELECT COUNT(*) AS total, MAX(period_end) AS latest_period FROM payroll_records WHERE employee_id=? AND status IN ('Approved','Paid')");
$payStmt->bind_param('i', $employee['employee_id']);
$payStmt->execute();
$pay = $payStmt->get_result()->fetch_assoc();

$latest = $conn->prepare("SELECT period_start,period_end,status FROM payroll_records WHERE employee_id=? ORDER BY period_end DESC LIMIT 3");
$latest->bind_param('i', $employee['employee_id']);
$latest->execute();
$rows = $latest->get_result();

$monthStmt = $conn->prepare("SELECT COUNT(*) AS total, SUM(status='Present') AS present, SUM(status='Late') AS late, SUM(status='Absent') AS absent FROM attendance WHERE employee_id=? AND attendance_date>=DATE_FORMAT(CURDATE(),'%Y-%m-01') AND attendance_date<=LAST_DAY(CURDATE())");
$monthStmt->bind_param('i', $employee['employee_id']);
$monthStmt->execute();
$monthAttendance = $monthStmt->get_result()->fetch_assoc();

$profileFields = ['first_name','last_name','department','position','employment_status'];
$complete = 0;
foreach ($profileFields as $field) { if (!empty(trim((string)($employee[$field] ?? '')))) $complete++; }
$profilePercent = (int)round(($complete / count($profileFields)) * 100);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<link rel="icon" type="image/png" href="../assets/favicon.png"><title>LCC Payroll System</title>
<link rel="stylesheet" href="../assets/css/app.css?v=20260909-rail4"><link rel="stylesheet" href="../assets/css/employee.css"><script src="../assets/js/app.js?v=20260909-rail4" defer></script>
</head>
<body><div class="app">
<?php employee_sidebar('dashboard'); ?>
<main class="main">
<?php employee_topbar('Employee Dashboard'); ?>
<div class="content">
<div class="page-heading"><div><div class="eyebrow">EMPLOYEE PORTAL</div><h1>Welcome, <?php echo e($employee['first_name']); ?></h1><p>Your attendance, payroll status, and employee information in one place.</p></div></div>

<section class="card dashboard-attendance"><div><div class="eyebrow">TODAY'S ATTENDANCE</div><h2><?php echo $todayAttendance && $todayAttendance['time_in'] ? 'You are checked in.' : 'Ready for today?'; ?></h2><p><?php echo $todayAttendance && $todayAttendance['time_in'] ? ($todayAttendance['time_out'] ? 'Your attendance for today is complete.' : 'Remember to time out before you leave.') : 'Record your attendance with one click.'; ?></p></div><div class="dashboard-attendance-action"><?php if(!$todayAttendance || !$todayAttendance['time_in']): ?><form method="post" action="attendance.php"><input type="hidden" name="action" value="time_in"><button class="btn btn-primary" type="submit">Time In</button></form><?php elseif(!$todayAttendance['time_out']): ?><div class="attendance-mini"><span>✓</span> Timed in <?php echo e(date('h:i A',strtotime($todayAttendance['time_in']))); ?></div><form method="post" action="attendance.php"><input type="hidden" name="action" value="time_out"><button class="btn btn-secondary" type="submit">Time Out</button></form><?php else: ?><div class="attendance-mini"><span>✓</span> Complete for today</div><?php endif; ?><a class="attendance-link" href="attendance.php">View attendance</a></div></section>

<section class="employee-hero card"><div class="employee-hero-person"><?php if($employee['photo_path']): ?><img src="<?php echo e(employee_photo_url($employee['photo_path'])); ?>" class="portal-photo" alt=""><?php else: ?><div class="portal-avatar"><?php echo e(strtoupper(substr($employee['first_name'],0,1).substr($employee['last_name'],0,1))); ?></div><?php endif; ?><div><span class="eyebrow">EMPLOYEE ID</span><h2><?php echo e($employee['employee_no']); ?></h2><p><?php echo e(trim($employee['first_name'].' '.($employee['middle_name']??'').' '.$employee['last_name'])); ?></p></div></div><div class="hero-meta"><span><?php echo e($employee['department']?:'Department not assigned'); ?></span><strong><?php echo e($employee['position']?:'Position not assigned'); ?></strong></div></section>

<div class="dashboard-kpis employee-kpis">
<div class="kpi card"><span class="kpi-label">Payroll Records</span><strong><?php echo (int)($pay['total']??0); ?></strong><small>Approved or paid</small></div>
<div class="kpi card"><span class="kpi-label">This Month Attendance</span><strong><?php echo (int)($monthAttendance['total']??0); ?></strong><small><?php echo (int)($monthAttendance['present']??0); ?> present · <?php echo (int)($monthAttendance['late']??0); ?> late</small></div>
<div class="kpi card"><span class="kpi-label">Profile Status</span><strong class="status-value"><?php echo $profilePercent; ?>%</strong><small><?php echo $profilePercent >= 100 ? 'Profile complete' : 'Keep your information updated'; ?></small></div>
</div>

<div class="employee-dashboard-grid">
<section class="card employee-recent-payroll"><div class="card-head"><div><h2>Recent Payroll</h2><p>Amounts are kept on the My Payroll page for privacy.</p></div><a class="btn btn-secondary" href="payroll.php">View Payroll</a></div><div class="table-wrap"><table class="table"><thead><tr><th>Pay Period</th><th>Status</th><th>Record</th></tr></thead><tbody><?php if($rows->num_rows===0): ?><tr><td colspan="3" class="empty">No payroll records available yet.</td></tr><?php else: while($r=$rows->fetch_assoc()): ?><tr><td><?php echo e(date('M d, Y',strtotime($r['period_start'])).' – '.date('M d, Y',strtotime($r['period_end']))); ?></td><td><span class="badge"><?php echo e($r['status']); ?></span></td><td><a class="table-link" href="payroll.php">View details</a></td></tr><?php endwhile; endif; ?></tbody></table></div></section>

<section class="card employee-quick-card"><div class="card-head"><div><h2>Quick Access</h2><p>Common employee actions.</p></div></div><div class="employee-quick-actions"><a href="payroll.php"><span>₱</span><div><strong>My Payroll</strong><small>View payslips and payroll history</small></div></a><a href="attendance.php"><span>◷</span><div><strong>My Attendance</strong><small>Review attendance records</small></div></a><a href="profile.php"><span>◎</span><div><strong>My Profile</strong><small>Check your employee information</small></div></a></div></section>
</div>

<section class="card employee-month-card"><div class="card-head"><div><h2>Monthly Attendance Snapshot</h2><p><?php echo e(date('F Y')); ?> attendance overview.</p></div><a class="btn btn-secondary" href="attendance.php">Open Attendance</a></div><div class="employee-attendance-snapshot"><div><strong><?php echo (int)($monthAttendance['present']??0); ?></strong><span>Present</span></div><div><strong><?php echo (int)($monthAttendance['late']??0); ?></strong><span>Late</span></div><div><strong><?php echo (int)($monthAttendance['absent']??0); ?></strong><span>Absent</span></div></div></section>

</div></main></div></body></html>
