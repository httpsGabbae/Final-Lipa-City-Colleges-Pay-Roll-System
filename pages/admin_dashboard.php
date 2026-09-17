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

$hireTrend = []; $hireLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $k = date('Y-m', strtotime("first day of -$i month"));
    $hireLabels[] = date('M', strtotime($k . '-01'));
    $hireTrend[$k] = 0;
}
$cut = date('Y-m-01', strtotime('-5 month'));
if ($r = $conn->query("SELECT DATE_FORMAT(created_at,'%Y-%m') m, COUNT(*) c FROM employees WHERE created_at IS NOT NULL AND created_at >= '" . $conn->real_escape_string($cut) . "' GROUP BY m")) {
    while ($row = $r->fetch_assoc()) { if (array_key_exists($row['m'], $hireTrend)) $hireTrend[$row['m']] = (int)$row['c']; }
}
$hireVals = array_values($hireTrend);
$hireMax = max(1, max($hireVals));
$hiredThisMonth = end($hireVals);
$pts = []; $n = count($hireVals);
foreach ($hireVals as $i => $v) {
    $x = $n > 1 ? 10 + ($i / ($n - 1)) * 280 : 150;
    $y = 86 - ($v / $hireMax) * 68;
    $pts[] = [round($x, 1), round($y, 1)];
}
$line = 'M ' . $pts[0][0] . ' ' . $pts[0][1];
foreach (array_slice($pts, 1) as $p) $line .= ' L ' . $p[0] . ' ' . $p[1];
$area = $line . ' L ' . $pts[$n-1][0] . ' 96 L ' . $pts[0][0] . ' 96 Z';
$last = $pts[$n-1];

$hour = (int)date('H');
$greeting = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');
$firstName = strtok(trim($_SESSION['admin_name'] ?? 'Administrator'), ' ');

$summaryBits = [];
$summaryBits[] = $activeEmployees . ' active employee' . ($activeEmployees === 1 ? '' : 's');
$summaryBits[] = $timedIn . ' in today';
$summaryBits[] = $payrollCount . ' payroll record' . ($payrollCount === 1 ? '' : 's') . ' this month';

$issues = [];
if ($late) $issues[] = ['attendance.php', $late . ' late attendance record' . ($late === 1 ? '' : 's')];
if ($missingDept) $issues[] = ['employees.php', $missingDept . ' without department'];
if ($missingSalary) $issues[] = ['employees.php', $missingSalary . ' without salary'];
$attentionTarget = $issues ? $issues[0][0] : 'reports.php';
$attentionText = $issues ? implode(' · ', array_column($issues, 1)) : 'All clear — nothing needs attention.';
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<link rel="icon" type="image/png" href="../assets/favicon.png"><title>LCC Payroll System</title>
<link rel="stylesheet" href="../assets/css/app.css?v=20260909-rail4"><link rel="stylesheet" href="../assets/css/apple-system.css?v=20260916-apple9"><script src="../assets/js/app.js?v=20260916-rail5" defer></script><script src="../assets/js/apple-motion.js?v=20260916-apple1" defer></script>
</head><body><div class="app">
<?php sidebar('dashboard'); ?><main class="main"><?php topbar('Dashboard'); ?><div class="content">
<section class="card dash-greet">
  <div class="dash-greet-text"><div class="eyebrow"><?php echo e(date('l, F d')); ?></div><h1>Good <?php echo $greeting; ?>, <?php echo e($firstName); ?>.</h1><p><?php echo e(implode(' · ', $summaryBits)); ?>.</p></div>
  <div class="dash-greet-actions"><a class="btn btn-primary" href="personalinfo.php?mode=new"><?php echo ui_icon('plus'); ?> Add employee</a><a class="btn btn-secondary" href="payroll.php?add=1">New payroll</a></div>
</section>

<div class="dash-grid">
<section class="card dash-employees">
  <div class="eyebrow">Employees</div>
  <div class="dash-big"><?php echo $activeEmployees; ?></div>
  <div class="dash-sub"><?php echo $regularEmployees; ?> regular<?php echo $hiredThisMonth ? ' · ' . $hiredThisMonth . ' joined this month' : ' · ' . $totalEmployees . ' total records'; ?></div>
  <svg class="dash-spark" viewBox="0 0 300 96" role="img" aria-label="Hires over the last six months" preserveAspectRatio="none"><path class="spark-area" d="<?php echo e($area); ?>"/><path class="spark-line" d="<?php echo e($line); ?>"/><circle class="spark-dot" cx="<?php echo $last[0]; ?>" cy="<?php echo $last[1]; ?>" r="4"/></svg>
  <div class="dash-months"><?php foreach ($hireLabels as $l): ?><span><?php echo e($l); ?></span><?php endforeach; ?></div>
  <a class="dash-link" href="employees.php">View directory →</a>
</section>

<div class="dash-rows">
  <a class="card dash-row" href="attendance.php"><span class="dash-row-icon"><?php echo ui_icon('clock'); ?></span><span class="dash-row-text"><strong>Attendance today</strong><small><?php echo $timedIn; ?> timed in · <?php echo $late; ?> late · <?php echo $completed; ?> done</small></span><span class="dash-chev">›</span></a>
  <a class="card dash-row" href="payroll.php"><span class="dash-row-icon"><?php echo ui_icon('wallet'); ?></span><span class="dash-row-text"><strong>Payroll this month</strong><small><?php echo e($payrollLabel); ?></small></span><span class="dash-chev">›</span></a>
  <a class="card dash-row" href="<?php echo e($attentionTarget); ?>"><span class="dash-row-icon <?php echo $issues ? 'warn' : 'ok'; ?>"><?php echo ui_icon($issues ? 'eye' : 'shield'); ?></span><span class="dash-row-text"><strong>Needs attention</strong><small><?php echo e($attentionText); ?></small></span><span class="dash-chev">›</span></a>
</div>
</div>
</div></main></div></body></html>
