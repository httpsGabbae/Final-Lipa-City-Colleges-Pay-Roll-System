<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

$message = '';
$error = '';
$showForm = isset($_GET['add']) && $_GET['add'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = (int)($_POST['employee_id'] ?? 0);
    $start = $_POST['period_start'] ?? '';
    $end = $_POST['period_end'] ?? '';
    $allowances = (float)($_POST['allowances'] ?? 0);
    $other = (float)($_POST['other_earnings'] ?? 0);
    $deductions = (float)($_POST['deductions'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if ($employeeId <= 0 || $start === '' || $end === '') {
        $error = 'Please choose an employee and enter the payroll period.';
        $showForm = true;
    } elseif ($end < $start) {
        $error = 'The payroll end date cannot be earlier than the start date.';
        $showForm = true;
    } elseif ($allowances < 0 || $other < 0 || $deductions < 0) {
        $error = 'Payroll amounts cannot be negative.';
        $showForm = true;
    } else {
        $stmt = $conn->prepare('SELECT basic_salary FROM employees WHERE employee_id=? LIMIT 1');
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $employee = $stmt->get_result()->fetch_assoc();

        if (!$employee) {
            $error = 'The selected employee was not found.';
            $showForm = true;
        } else {
            $basic = (float)$employee['basic_salary'];
            $gross = $basic + $allowances + $other;
            $net = $gross - $deductions;

            $stmt = $conn->prepare('INSERT INTO payroll_records(employee_id,period_start,period_end,basic_salary,allowances,other_earnings,deductions,gross_pay,net_pay,status,notes) VALUES(?,?,?,?,?,?,?,?,?,"Draft",?)');
            $stmt->bind_param('issdddddds', $employeeId, $start, $end, $basic, $allowances, $other, $deductions, $gross, $net, $notes);

            if ($stmt->execute()) {
                header('Location: payroll.php?saved=1');
                exit;
            }

            $error = 'Unable to save the payroll record.';
            $showForm = true;
        }
    }
}

if (isset($_GET['saved'])) {
    $message = 'Payroll record saved successfully.';
}

$records = $conn->query('SELECT p.*,e.employee_no,e.first_name,e.middle_name,e.last_name,e.department,e.position FROM payroll_records p JOIN employees e ON e.employee_id=p.employee_id ORDER BY p.payroll_id DESC');
$employees = $conn->query('SELECT employee_id,employee_no,first_name,last_name,basic_salary FROM employees ORDER BY last_name,first_name');

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<!-- FAVICON: Put your favicon file at /assets/favicon.png (replace the included LCC placeholder if desired). -->
<link rel="icon" type="image/png" href="../assets/favicon.png">
    <title>LCC Payroll System</title>
    <link rel="stylesheet" href="../assets/css/app.css?v=20260909-rail4">
    <script src="../assets/js/app.js?v=20260909-rail4" defer></script>
</head>

<body>
    <div class="app">
        <?php sidebar('payroll'); ?>
        <main class="main">
            <?php topbar('Payroll'); ?>
            <div class="content">
                <div class="page-heading">
                    <div>
                        <div class="eyebrow">Payroll Management</div>
                        <h1>All Payroll</h1>
                        <p>Review existing payroll records first. Add a new payroll only when you are ready.</p>
                    </div>
                    <div class="print-tools">
                        <a class="btn btn-secondary" href="reports.php"><?php echo ui_icon('file-chart'); ?> Payroll Reports</a>
                        <button type="button" class="btn btn-secondary" onclick="window.print()"><?php echo ui_icon('printer'); ?> Print All Payroll</button>
                        <a class="btn btn-primary" href="payroll.php?add=1"><?php echo ui_icon('plus'); ?> Add New Payroll</a>
                    </div>
                </div>

                <?php if ($message): ?><div class="notice ok"><?php echo e($message); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="notice err"><?php echo e($error); ?></div><?php endif; ?>

                <section class="card payroll-list-card">
                    <div class="card-head">
                        <div>
                            <div class="eyebrow">Payroll Records</div>
                            <h2>All Payroll Records</h2>
                            <p>Open a record to print a clean payroll slip.</p>
                        </div>
                        
                    </div>
                    <div class="table-wrap">
                        <table class="table payroll-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Period</th>
                                    <th>Gross Pay</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$records || $records->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="8" class="empty">No payroll records yet. Click <strong>Add New Payroll</strong> to create the first one.</td>
                                    </tr>
                                    <?php else: while ($row = $records->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($row['last_name'] . ', ' . $row['first_name']); ?></strong>
                                                <div class="mini">Employee ID: <?php echo e($row['employee_no']); ?></div>
                                            </td>
                                            <td><?php echo e($row['department'] ?: 'Not assigned'); ?></td>
                                            <td><?php echo e($row['position'] ?: 'Not assigned'); ?></td>
                                            <td><?php echo e(date('M d, Y', strtotime($row['period_start'])) . ' - ' . date('M d, Y', strtotime($row['period_end']))); ?></td>
                                            <td><?php echo money($row['gross_pay']); ?></td>
                                            <td><strong><?php echo money($row['net_pay']); ?></strong></td>
                                            <td><span class="badge"><?php echo e($row['status']); ?></span></td>
                                            <td><div class="icon-actions"><button type="button" class="icon-action print" title="Print payroll statement" aria-label="Print payroll statement" onclick="printPayrollRecord(<?php echo (int)$row['payroll_id']; ?>); return false;"><?php echo ui_icon('printer'); ?></button></div></td>
                                        </tr>
                                <?php endwhile;
                                endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="card report-shortcut">
                    <div class="report-shortcut-icon"><?php echo ui_icon('file-chart'); ?></div>
                    <div><div class="eyebrow">Payroll Reporting</div><h3>Need a month-by-month view?</h3><p>Open Payroll Reports to filter a month, review totals, and create a formal printable register.</p></div>
                    <a class="btn btn-secondary" href="reports.php">Open Reports <?php echo ui_icon('arrow-right'); ?></a>
                </section>

                <div id="nativePayrollPrintSheet" class="native-print-sheet" aria-hidden="true"></div>

    <?php if ($showForm): ?>
                    <section class="card form-card payroll-form-card">
                        <div class="card-head">
                            <div>
                                <div class="eyebrow">New Payroll</div>
                                <h2>Add New Payroll</h2>
                                <p>Basic salary is taken automatically from the employee record.</p>
                            </div>
                            <a class="btn btn-secondary" href="payroll.php">Cancel</a>
                        </div>
                        <form method="post">
                            <div class="form-body">
                                <div class="section-title">Employee and Payroll Period</div>
                                <div class="form-grid">
                                    <div class="field full">
                                        <label>Employee <span class="required">*</span></label>
                                        <select name="employee_id" id="employee_id" required>
                                            <option value="">Select employee</option>
                                            <?php while ($employee = $employees->fetch_assoc()): ?>
                                                <option value="<?php echo (int)$employee['employee_id']; ?>" data-salary="<?php echo e($employee['basic_salary']); ?>">
                                                    <?php echo e($employee['employee_no'] . ' — ' . $employee['last_name'] . ', ' . $employee['first_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="field"><label>Period Start <span class="required">*</span></label><input type="date" name="period_start" required></div>
                                    <div class="field"><label>Period End <span class="required">*</span></label><input type="date" name="period_end" required></div>
                                </div>

                                <div class="section-title">Earnings and Deductions</div>
                                <div class="form-grid">
                                    <div class="field"><label>Basic Salary</label><input id="basic_salary" value="0.00" disabled></div>
                                    <div class="field"><label>Allowances</label><input type="number" min="0" step="0.01" name="allowances" id="allowances" value="0"></div>
                                    <div class="field"><label>Other Earnings</label><input type="number" min="0" step="0.01" name="other_earnings" id="other_earnings" value="0"></div>
                                    <div class="field"><label>Deductions</label><input type="number" min="0" step="0.01" name="deductions" id="deductions" value="0"></div>
                                    <div class="field full"><label>Notes</label><textarea name="notes" placeholder="Optional payroll notes"></textarea></div>
                                </div>

                                <div class="pay-preview">
                                    <div><span>Gross Pay</span><strong id="gross">₱ 0.00</strong></div>
                                    <div><span>Net Pay</span><strong id="net">₱ 0.00</strong></div>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button class="btn btn-primary" type="submit">✓ Save Payroll</button>
                                <a class="btn btn-secondary" href="payroll.php">Cancel</a>
                            </div>
                        </form>
                    </section>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php if ($showForm): ?>
        <script>
            function updatePay() {
                const employee = document.getElementById('employee_id');
                const selected = employee.options[employee.selectedIndex];
                const basic = parseFloat(selected?.dataset.salary || 0);
                const allowances = parseFloat(document.getElementById('allowances').value || 0);
                const other = parseFloat(document.getElementById('other_earnings').value || 0);
                const deductions = parseFloat(document.getElementById('deductions').value || 0);
                const gross = basic + allowances + other;
                const net = gross - deductions;
                document.getElementById('basic_salary').value = basic.toFixed(2);
                document.getElementById('gross').textContent = '₱ ' + gross.toLocaleString('en-PH', {
                    minimumFractionDigits: 2
                });
                document.getElementById('net').textContent = '₱ ' + net.toLocaleString('en-PH', {
                    minimumFractionDigits: 2
                });
            }
            document.getElementById('employee_id').addEventListener('change', updatePay);
            document.getElementById('allowances').addEventListener('input', updatePay);
            document.getElementById('other_earnings').addEventListener('input', updatePay);
            document.getElementById('deductions').addEventListener('input', updatePay);
            updatePay();
        </script>
    <?php endif; ?>
<script>
async function printPayrollRecord(id){
    const sheet=document.getElementById('nativePayrollPrintSheet');
    if(!sheet) return;

    sheet.innerHTML='<div class="print-loading">Preparing payroll statement…</div>';
    sheet.classList.add('active');

    const cleanup=()=>{
        sheet.classList.remove('active');
        sheet.innerHTML='';
    };

    window.addEventListener('afterprint', cleanup, {once:true});

    try{
        const response=await fetch('payroll_preview.php?id='+encodeURIComponent(id),{headers:{'X-Requested-With':'XMLHttpRequest'}});
        if(!response.ok) throw new Error('Request failed');
        sheet.innerHTML=await response.text();
        window.print();
    }catch(error){
        cleanup();
        alert('Unable to prepare the payroll statement.');
    }
}
</script>
</body>

</html>