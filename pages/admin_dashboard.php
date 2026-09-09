<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$totalEmployees = (int)$conn->query("SELECT COUNT(*) total FROM employees")->fetch_assoc()['total'];
$activeEmployees = (int)$conn->query("SELECT COUNT(*) total FROM employees WHERE COALESCE(employment_status,'') NOT IN ('Separated','Inactive')")->fetch_assoc()['total'];
$regularEmployees = (int)$conn->query("SELECT COUNT(*) total FROM employees WHERE employment_status='Regular'")->fetch_assoc()['total'];
$totalDepartments = (int)$conn->query("SELECT COUNT(*) total FROM departments WHERE status='Active'")->fetch_assoc()['total'];

$today = date('Y-m-d');
$timedIn = $completed = $late = 0;
if ($r = $conn->query("SELECT COUNT(CASE WHEN time_in IS NOT NULL THEN 1 END) timed_in, COUNT(CASE WHEN time_in IS NOT NULL AND time_out IS NOT NULL THEN 1 END) completed, COUNT(CASE WHEN status='Late' THEN 1 END) late_count FROM attendance WHERE attendance_date='" . $conn->real_escape_string($today) . "'")) {
    $a = $r->fetch_assoc(); $timedIn=(int)$a['timed_in']; $completed=(int)$a['completed']; $late=(int)$a['late_count'];
}

$payrollCount = $payrollGross = $payrollNet = 0; $payrollLabel = 'No payroll records yet';
if ($r = $conn->query("SELECT COUNT(*) total, COALESCE(SUM(gross_pay),0) gross, COALESCE(SUM(net_pay),0) net FROM payroll_records WHERE period_start >= '" . date('Y-m-01') . "' AND period_start <= '" . date('Y-m-t') . "'")) {
    $p = $r->fetch_assoc(); $payrollCount=(int)$p['total']; $payrollGross=(float)$p['gross']; $payrollNet=(float)$p['net'];
}
if ($payrollCount) $payrollLabel = $payrollCount . ' payroll record' . ($payrollCount === 1 ? '' : 's') . ' this month';

$missingDept = (int)$conn->query("SELECT COUNT(*) total FROM employees WHERE department IS NULL OR TRIM(department)='' ")->fetch_assoc()['total'];
$missingSalary = (int)$conn->query("SELECT COUNT(*) total FROM employees WHERE basic_salary IS NULL OR basic_salary<=0")->fetch_assoc()['total'];

$departmentData=[];
$res=$conn->query("SELECT d.department_name department, COUNT(e.employee_id) total FROM departments d LEFT JOIN employees e ON e.department=d.department_name WHERE d.status='Active' GROUP BY d.department_id,d.department_name ORDER BY total DESC,d.department_name LIMIT 7");
if($res) while($row=$res->fetch_assoc()) $departmentData[$row['department']]=(int)$row['total'];
$maxDept = $departmentData ? max(1, max($departmentData)) : 1;
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<link rel="icon" type="image/png" href="../assets/favicon.png"><title>LCC Payroll System</title>
<link rel="stylesheet" href="../assets/css/app.css?v=20260909-rail4"><script src="../assets/js/app.js?v=20260909-rail4" defer></script>
</head><body><div class="app">
<?php sidebar('dashboard'); ?><main class="main"><?php topbar('Dashboard'); ?><div class="content">
<section class="hero dashboard-hero">
  <div><div class="eyebrow" style="color:#b9efec">PAYROLL OPERATIONS</div><h1>Good <?php echo date('H')<12?'morning':(date('H')<18?'afternoon':'evening'); ?>, <?php echo e($_SESSION['admin_name'] ?? 'Administrator'); ?>.</h1><p>Your workforce and payroll overview for <?php echo e(date('F Y')); ?>.</p></div>
  <div class="hero-date"><span><?php echo e(date('l')); ?></span><strong><?php echo e(date('F d, Y')); ?></strong></div>
</section>

<section class="quick-actions">
<a class="quick-action" href="personalinfo.php?mode=new"><span class="quick-icon"><?php echo ui_icon('user-plus'); ?></span><span><strong>Add Employee</strong><small>Create a new employee record</small></span></a>
<a class="quick-action" href="attendance.php"><span class="quick-icon"><?php echo ui_icon('clock'); ?></span><span><strong>Review Attendance</strong><small>Check today's time records</small></span></a>
<a class="quick-action" href="payroll.php?add=1"><span class="quick-icon"><?php echo ui_icon('wallet'); ?></span><span><strong>Create Payroll</strong><small>Start a payroll record</small></span></a>
<a class="quick-action" href="reports.php"><span class="quick-icon"><?php echo ui_icon('file-chart'); ?></span><span><strong>View Reports</strong><small>Analyze payroll and workforce data</small></span></a>
</section>

<section class="stats dashboard-kpis">
<div class="card stat"><div class="stat-top"><span class="stat-icon"><?php echo ui_icon('users'); ?></span><span class="stat-trend">Workforce</span></div><div class="stat-label">Active Employees</div><div class="stat-number"><?php echo $activeEmployees; ?></div><div class="mini"><?php echo $totalEmployees; ?> total records</div></div>
<div class="card stat"><div class="stat-top"><span class="stat-icon"><?php echo ui_icon('clock'); ?></span><span class="stat-trend">Today</span></div><div class="stat-label">Attendance</div><div class="stat-number"><?php echo $timedIn; ?></div><div class="mini"><?php echo $late; ?> late · <?php echo $completed; ?> completed</div></div>
<div class="card stat"><div class="stat-top"><span class="stat-icon"><?php echo ui_icon('wallet'); ?></span><span class="stat-trend">This Month</span></div><div class="stat-label">Payroll Records</div><div class="stat-number"><?php echo $payrollCount; ?></div><div class="mini"><?php echo e($payrollLabel); ?></div></div>
</section>

<div class="dashboard-main-grid">
<section class="card payroll-overview-card">
<div class="card-head"><div><div class="eyebrow">PAYROLL</div><h2>Current payroll activity</h2><p>Keep payroll review and employee pay in one place.</p></div><a class="btn btn-secondary" href="payroll.php">Open Payroll <?php echo ui_icon('arrow-right'); ?></a></div>
<div class="payroll-overview-body">
<div class="payroll-status"><span class="status-dot"></span><div><strong><?php echo $payrollCount ? 'Records available for review' : 'Ready to start'; ?></strong><small><?php echo e($payrollLabel); ?></small></div></div>
<div class="payroll-safe-summary"><span class="safe-summary-icon"><?php echo ui_icon('shield'); ?></span><div><strong>Payroll amounts hidden</strong><small>Salary figures are available only in authorized payroll records.</small></div></div>
</div>
<div class="payroll-next"><span><?php echo ui_icon('calendar'); ?> Pay records are currently stored per employee and pay period.</span><a href="payroll.php?add=1">Add payroll record →</a></div>
</section>

<section class="card attention-card">
<div class="card-head"><div><div class="eyebrow">NEEDS ATTENTION</div><h2>Before you run payroll</h2><p>Fix incomplete information early.</p></div></div>
<div class="attention-list">
<a href="attendance.php" class="attention-row"><span class="attention-icon warning"><?php echo ui_icon('clock'); ?></span><span><strong><?php echo $late; ?> late attendance record<?php echo $late===1?'':'s'; ?></strong><small>Review today's attendance</small></span><b>›</b></a>
<a href="employees.php" class="attention-row"><span class="attention-icon <?php echo $missingDept?'warning':'success'; ?>"><?php echo $missingDept? '!' : '✓'; ?></span><span><strong><?php echo $missingDept; ?> employee<?php echo $missingDept===1?'':'s'; ?> without department</strong><small>Complete organization data</small></span><b>›</b></a>
<a href="employees.php" class="attention-row"><span class="attention-icon <?php echo $missingSalary?'warning':'success'; ?>"><?php echo $missingSalary? '!' : '✓'; ?></span><span><strong><?php echo $missingSalary; ?> employee<?php echo $missingSalary===1?'':'s'; ?> without salary</strong><small>Check compensation information</small></span><b>›</b></a>
</div></section>
</div>

<section class="dashboard-bottom-grid">
<section class="card department-card"><div class="card-head"><div><div class="eyebrow">WORKFORCE</div><h2>Employees by department</h2><p>Current active-organization headcount.</p></div><a href="departments.php" class="btn btn-secondary">Manage</a></div><div class="dept-list">
<?php if(!$departmentData): ?><div class="empty">No department data available.</div><?php else: foreach($departmentData as $name=>$count): $width=max(4,round(($count/$maxDept)*100)); ?><div class="dept-row"><div class="dept-label"><span><?php echo e($name); ?></span><strong><?php echo $count; ?></strong></div><div class="dept-track"><i style="width:<?php echo $width; ?>%"></i></div></div><?php endforeach; endif; ?></div></section>
<section class="card today-card"><div class="card-head"><div><div class="eyebrow">TODAY</div><h2>Attendance snapshot</h2><p><?php echo e(date('F d, Y')); ?></p></div><a href="attendance.php" class="btn btn-secondary">Open</a></div><div class="attendance-snapshot"><div><strong><?php echo $timedIn; ?></strong><span>Timed in</span></div><div><strong><?php echo $completed; ?></strong><span>Completed</span></div><div><strong><?php echo $late; ?></strong><span>Late</span></div></div><div class="snapshot-note">Attendance is designed to flow into payroll review, where exceptions can be checked before pay is finalized.</div></section>
</section>
</div></main></div></body></html>
