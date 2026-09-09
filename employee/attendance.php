<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

$employee = current_employee($conn);
if (!$employee) { header('Location: logout.php'); exit; }

$today = date('Y-m-d');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $stmt = $conn->prepare('SELECT * FROM attendance WHERE employee_id=? AND attendance_date=? LIMIT 1');
    $stmt->bind_param('is', $employee['employee_id'], $today);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();

    if ($action === 'time_in') {
        if ($record && $record['time_in']) {
            $error = 'You have already timed in today.';
        } else {
            $now = date('H:i:s');
            if ($record) {
                $up = $conn->prepare('UPDATE attendance SET time_in=?, status="Present" WHERE attendance_id=?');
                $up->bind_param('si', $now, $record['attendance_id']);
            } else {
                $up = $conn->prepare('INSERT INTO attendance(employee_id,attendance_date,time_in,status) VALUES(?,?,?,"Present")');
                $up->bind_param('iss', $employee['employee_id'], $today, $now);
            }
            if ($up->execute()) $message = 'Time in recorded at ' . date('h:i A', strtotime($now)) . '.';
            else $error = 'Unable to record your time in. Please try again.';
        }
    } elseif ($action === 'time_out') {
        if (!$record || !$record['time_in']) {
            $error = 'Please time in first.';
        } elseif ($record['time_out']) {
            $error = 'You have already timed out today.';
        } else {
            $now = date('H:i:s');
            $up = $conn->prepare('UPDATE attendance SET time_out=? WHERE attendance_id=?');
            $up->bind_param('si', $now, $record['attendance_id']);
            if ($up->execute()) $message = 'Time out recorded at ' . date('h:i A', strtotime($now)) . '.';
            else $error = 'Unable to record your time out. Please try again.';
        }
    }
}

$stmt = $conn->prepare('SELECT * FROM attendance WHERE employee_id=? AND attendance_date=? LIMIT 1');
$stmt->bind_param('is', $employee['employee_id'], $today);
$stmt->execute();
$todayRecord = $stmt->get_result()->fetch_assoc();

$historyMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $historyMonth)) $historyMonth = date('Y-m');
$historyStart = $historyMonth . '-01';
$historyEnd = date('Y-m-t', strtotime($historyStart));
$historyStmt = $conn->prepare('SELECT attendance_date,time_in,time_out,status FROM attendance WHERE employee_id=? AND attendance_date BETWEEN ? AND ? ORDER BY attendance_date DESC');
$historyStmt->bind_param('iss', $employee['employee_id'], $historyStart, $historyEnd);
$historyStmt->execute();
$history = $historyStmt->get_result();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<!-- FAVICON: Put your favicon file at /assets/favicon.png (replace the included LCC placeholder if desired). -->
<link rel="icon" type="image/png" href="../assets/favicon.png"><title>LCC Payroll System</title><link rel="stylesheet" href="../assets/css/app.css?v=20260909-rail4"><link rel="stylesheet" href="../assets/css/employee.css"><script src="../assets/js/app.js?v=20260909-rail4" defer></script></head>
<body><div class="app"><?php employee_sidebar('attendance'); ?><main class="main"><?php employee_topbar('Attendance'); ?><div class="content">
<div class="page-heading"><div><div class="eyebrow">EMPLOYEE PORTAL</div><h1>Attendance</h1><p>Record your daily attendance with one click.</p></div></div>
<?php if($message): ?><div class="notice ok"><?php echo e($message); ?></div><?php endif; ?><?php if($error): ?><div class="notice err"><?php echo e($error); ?></div><?php endif; ?>
<section class="attendance-card card"><div class="attendance-date"><span class="eyebrow">TODAY</span><strong><?php echo e(date('l, F d, Y')); ?></strong><span id="liveClock"><?php echo e(date('h:i:s A')); ?></span></div>
<div class="attendance-action">
<?php if (!$todayRecord || !$todayRecord['time_in']): ?><form method="post"><input type="hidden" name="action" value="time_in"><button class="attendance-button time-in" type="submit"><span class="attendance-button-icon">→</span><span><b>Time In</b><small>Start your workday</small></span></button></form>
<?php elseif (!$todayRecord['time_out']): ?><div class="attendance-status done"><span>✓</span><div><b>Timed in at <?php echo e(date('h:i A', strtotime($todayRecord['time_in']))); ?></b><small>You are currently clocked in.</small></div></div><form method="post"><input type="hidden" name="action" value="time_out"><button class="attendance-button time-out" type="submit"><span class="attendance-button-icon">←</span><span><b>Time Out</b><small>End your workday</small></span></button></form>
<?php else: ?><div class="attendance-status done full"><span>✓</span><div><b>Attendance complete</b><small>Time in <?php echo e(date('h:i A',strtotime($todayRecord['time_in']))); ?> · Time out <?php echo e(date('h:i A',strtotime($todayRecord['time_out']))); ?></small></div></div><?php endif; ?>
</div></section>
<section class="card employee-attendance-history"><div class="card-head"><div><div class="eyebrow">PERSONAL ATTENDANCE</div><h2><?php echo e(date('F Y', strtotime($historyStart))); ?></h2><p>Your attendance records for the selected month.</p></div><div class="attendance-history-tools no-print"><form method="get"><input type="month" name="month" value="<?php echo e($historyMonth); ?>"><button class="btn btn-secondary" type="submit">View Month</button></form><button type="button" class="btn btn-primary" onclick="window.print()">Print Month</button></div></div><div class="table-wrap"><table class="table"><thead><tr><th>Date</th><th>Time In</th><th>Time Out</th><th>Status</th></tr></thead><tbody><?php if($history->num_rows===0): ?><tr><td colspan="4" class="empty">No attendance records yet.</td></tr><?php else: while($r=$history->fetch_assoc()): ?><tr><td><?php echo e(date('M d, Y',strtotime($r['attendance_date']))); ?></td><td><?php echo $r['time_in']?e(date('h:i A',strtotime($r['time_in']))):'—'; ?></td><td><?php echo $r['time_out']?e(date('h:i A',strtotime($r['time_out']))):'—'; ?></td><td><span class="badge"><?php echo e($r['status']); ?></span></td></tr><?php endwhile; endif; ?></tbody></table></div></section>
<div class="attendance-print-footer print-only">Generated <?php echo e(date('M d, Y h:i A')); ?> · LCC Payroll System</div></div></main></div><script>setInterval(()=>{const d=new Date();const el=document.getElementById('liveClock');if(el)el.textContent=new Intl.DateTimeFormat('en-PH',{timeZone:'Asia/Manila',hour:'2-digit',minute:'2-digit',second:'2-digit'}).format(d);},1000);</script></body></html>
