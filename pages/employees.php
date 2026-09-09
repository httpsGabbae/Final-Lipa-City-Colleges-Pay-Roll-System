<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $conn->prepare('SELECT photo_path FROM employees WHERE employee_id=? LIMIT 1');
    $stmt->bind_param('i', $deleteId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare('DELETE FROM employees WHERE employee_id=?');
    $stmt->bind_param('i', $deleteId);
    $stmt->execute();

    if ($row) delete_employee_photo($row['photo_path']);

    header('Location: employees.php?deleted=1');
    exit;
}

$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'name';
$allowedFilters = ['name', 'position', 'department'];
if (!in_array($filter, $allowedFilters, true)) $filter = 'name';

$where = '';
$params = [];
$types = '';

if ($search !== '') {
    if ($filter === 'name') {
        $where = "WHERE CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ? OR CONCAT(last_name, ', ', first_name) LIKE ? OR employee_no LIKE ?";
        $term = '%' . $search . '%';
        $params = [$term, $term, $term];
        $types = 'sss';
    } else {
        $column = $filter === 'position' ? 'position' : 'department';
        $where = "WHERE $column LIKE ?";
        $params[] = '%' . $search . '%';
        $types = 's';
    }
}

$sql = "SELECT employee_id,employee_no,first_name,middle_name,last_name,photo_path,department,position,employment_status,basic_salary,created_at FROM employees $where ORDER BY last_name,first_name";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$employees = [];
while ($row = $result->fetch_assoc()) $employees[] = $row;

$activeCount = 0;
$departmentCount = [];
$statusCount = [];
foreach ($employees as $employee) {
    if ($employee['employment_status'] === 'Regular') $activeCount++;
    $department = trim((string)$employee['department']);
    if ($department !== '') $departmentCount[$department] = ($departmentCount[$department] ?? 0) + 1;
    $status = $employee['employment_status'] ?: 'Unknown';
    $statusCount[$status] = ($statusCount[$status] ?? 0) + 1;
}
arsort($departmentCount);
$topDepartment = $departmentCount ? array_key_first($departmentCount) : 'No department yet';

$recent = $conn->query("SELECT employee_no,first_name,last_name,department,created_at FROM employees ORDER BY created_at DESC LIMIT 3");
$recentEmployees = [];
if ($recent) while ($r = $recent->fetch_assoc()) $recentEmployees[] = $r;
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
    <?php sidebar('employees'); ?>
    <main class="main">
        <?php topbar('Employees'); ?>
        <div class="content">
            <div class="page-heading">
                <div>
                    <div class="eyebrow">Employee Directory</div>
                    <h1>All Employees</h1>
                    <p>Keep the main directory focused. Sensitive compensation details stay behind the Salary action.</p>
                </div>
                <div class="print-tools">
                    <button type="button" class="btn btn-secondary" onclick="window.print()"><?php echo ui_icon('printer'); ?> Print List</button>
                    <a class="btn btn-primary" href="personalinfo.php?mode=new"><?php echo ui_icon('plus'); ?> Add Employee</a>
                </div>
            </div>

            <?php if (isset($_GET['deleted'])): ?><div class="notice ok">Employee deleted successfully.</div><?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'notfound'): ?><div class="notice err">The employee could not be found.</div><?php endif; ?>

            <section class="card" style="overflow:hidden">
                <form class="toolbar" method="get">
                    <div><label>Search</label><div class="input-icon-wrap"><?php echo ui_icon('search'); ?><input name="search" value="<?php echo e($search); ?>" placeholder="Name, employee ID, position, or department"></div></div>
                    <div><label>Search By</label><select name="filter">
                        <option value="name" <?php echo $filter === 'name' ? 'selected' : ''; ?>>Name / Employee ID</option>
                        <option value="position" <?php echo $filter === 'position' ? 'selected' : ''; ?>>Position</option>
                        <option value="department" <?php echo $filter === 'department' ? 'selected' : ''; ?>>Department</option>
                    </select></div>
                    <button class="btn btn-primary" type="submit"><?php echo ui_icon('search'); ?> Search</button>
                    <a class="btn btn-secondary" href="employees.php">Clear</a>
                </form>
                <div class="card-head">
                    <div>
                        <h2>Employee List</h2>
                        <p><?php echo count($employees); ?> employee<?php echo count($employees) === 1 ? '' : 's'; ?> found.</p>
                    </div>
                    <div class="table-legend"><span><?php echo ui_icon('eye'); ?> View</span><span><?php echo ui_icon('wallet'); ?> Salary</span><span><?php echo ui_icon('pencil'); ?> Edit</span><span><?php echo ui_icon('trash'); ?> Delete</span></div>
                </div>
                <div class="table-wrap">
                    <table class="table employee-table">
                        <thead><tr><th>Employee</th><th>Employee ID</th><th>Department</th><th>Position</th><th>Status</th><th class="action-col">Actions</th></tr></thead>
                        <tbody>
                        <?php if (!$employees): ?>
                            <tr><td colspan="6" class="empty">No employee matched your search.</td></tr>
                        <?php else: foreach ($employees as $row): ?>
                            <tr>
                                <td><div class="person-cell">
                                    <?php if (!empty($row['photo_path'])): ?><img class="list-photo" src="../<?php echo e(ltrim($row['photo_path'], '/')); ?>" alt="">
                                    <?php else: ?><div class="list-avatar"><?php echo e(strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1))); ?></div><?php endif; ?>
                                    <div><strong><?php echo e(employee_full_name_last_first($row)); ?></strong><div class="mini"><?php echo e($row['employment_status']); ?></div></div>
                                </div></td>
                                <td><span class="employee-no"><?php echo e($row['employee_no']); ?></span></td>
                                <td><?php echo e($row['department'] ?: 'Not assigned'); ?></td>
                                <td><?php echo e($row['position'] ?: 'Not assigned'); ?></td>
                                <td><span class="badge"><?php echo e($row['employment_status']); ?></span></td>
                                <td class="action-col">
                                    <div class="icon-actions">
                                        <button type="button" class="icon-action view" title="View employee record" aria-label="View employee record" onclick="openEmployeePreview(<?php echo (int)$row['employee_id']; ?>)"><?php echo ui_icon('eye'); ?></button>
                                        <button type="button" class="icon-action salary" title="View salary information" aria-label="View salary information" onclick="openSalaryModal(<?php echo (int)$row['employee_id']; ?>)"><?php echo ui_icon('wallet'); ?></button><a class="icon-action" title="Open employee portal" aria-label="Open employee portal" href="../employee/login.php?employee=<?php echo urlencode($row['employee_no']); ?>" target="_blank">↗</a>
                                        <a class="icon-action edit" title="Edit employee" aria-label="Edit employee" href="personalinfo.php?edit=<?php echo (int)$row['employee_id']; ?>"><?php echo ui_icon('pencil'); ?></a>
                                        <a class="icon-action delete" title="Delete employee" aria-label="Delete employee" href="employees.php?delete=<?php echo (int)$row['employee_id']; ?>" onclick="return confirm('Delete this employee? This cannot be undone.')"><?php echo ui_icon('trash'); ?></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="directory-pulse">
                <div class="pulse-main card">
                    <div class="card-head"><div><div class="eyebrow">Directory Pulse</div><h2>What needs attention?</h2><p>A quick operational snapshot so the space below the directory is useful instead of empty.</p></div><div class="pulse-badge"><?php echo ui_icon('trend'); ?> Live</div></div>
                    <div class="pulse-grid">
                        <div class="pulse-stat"><span class="pulse-icon"><?php echo ui_icon('users'); ?></span><div><strong><?php echo count($employees); ?></strong><span>Total employees</span></div></div>
                        <div class="pulse-stat"><span class="pulse-icon"><?php echo ui_icon('shield'); ?></span><div><strong><?php echo $activeCount; ?></strong><span>Regular staff</span></div></div>
                        <div class="pulse-stat"><span class="pulse-icon"><?php echo ui_icon('dashboard'); ?></span><div><strong><?php echo count($departmentCount); ?></strong><span>Departments</span></div></div>
                        <div class="pulse-stat"><span class="pulse-icon"><?php echo ui_icon('users'); ?></span><div><strong><?php echo e($topDepartment); ?></strong><span>Largest department</span></div></div>
                    </div>
                </div>
                <div class="recent-card card">
                    <div class="card-head"><div><div class="eyebrow">Recently Added</div><h2>New employee records</h2></div></div>
                    <div class="recent-list">
                        <?php if (!$recentEmployees): ?><div class="empty">No recent records.</div><?php else: foreach ($recentEmployees as $recent): ?>
                            <div class="recent-row"><div class="recent-avatar"><?php echo e(strtoupper(substr($recent['first_name'],0,1).substr($recent['last_name'],0,1))); ?></div><div><strong><?php echo e($recent['last_name'].', '.$recent['first_name']); ?></strong><span><?php echo e($recent['employee_no']); ?> · <?php echo e($recent['department'] ?: 'No department'); ?></span></div><time><?php echo e(date('M d', strtotime($recent['created_at']))); ?></time></div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<div id="employeePreviewModal" class="detail-modal-overlay" aria-hidden="true">
    <div class="detail-modal" role="dialog" aria-modal="true" aria-labelledby="employeePreviewTitle">
        <div class="detail-modal-head"><div><div class="eyebrow">OFFICIAL RECORD</div><h2 id="employeePreviewTitle">Employee Record</h2></div><div class="modal-head-actions"><button type="button" class="icon-action print" title="Print employee record" aria-label="Print employee record" onclick="printEmployeePreview()"><?php echo ui_icon('printer'); ?></button><button type="button" class="modal-icon-close" onclick="closeEmployeePreview()" aria-label="Close"><?php echo ui_icon('close'); ?></button></div></div>
        <div id="employeePreviewBody" class="detail-modal-body"><div class="preview-loading"><span class="spinner"></span> Loading employee record…</div></div>
    </div>
</div>

<div id="salaryModal" class="detail-modal-overlay" aria-hidden="true">
    <div class="detail-modal salary-modal" role="dialog" aria-modal="true" aria-labelledby="salaryModalTitle">
        <div class="detail-modal-head"><div><div class="eyebrow">PRIVATE COMPENSATION</div><h2 id="salaryModalTitle">Salary Information</h2></div><button type="button" class="modal-icon-close" onclick="closeSalaryModal()" aria-label="Close"><?php echo ui_icon('close'); ?></button></div>
        <div id="salaryModalBody" class="detail-modal-body"><div class="preview-loading"><span class="spinner"></span> Loading compensation details…</div></div>
    </div>
</div>

<script>
async function openEmployeePreview(id) {
    const overlay = document.getElementById('employeePreviewModal');
    const body = document.getElementById('employeePreviewBody');
    overlay.classList.add('show'); overlay.setAttribute('aria-hidden','false');
    body.innerHTML = '<div class="preview-loading"><span class="spinner"></span> Loading employee record…</div>';
    try {
        const response = await fetch('employee_preview.php?id=' + encodeURIComponent(id), {headers:{'X-Requested-With':'XMLHttpRequest'}});
        if (!response.ok) throw new Error('Request failed');
        body.innerHTML = await response.text();
    } catch (error) {
        body.innerHTML = '<div class="notice err">Unable to load this employee record. Please try again.</div>';
    }
}
function closeEmployeePreview(){ const el=document.getElementById('employeePreviewModal'); el.classList.remove('show'); el.setAttribute('aria-hidden','true'); }
async function openSalaryModal(id) {
    const overlay = document.getElementById('salaryModal');
    const body = document.getElementById('salaryModalBody');
    overlay.classList.add('show'); overlay.setAttribute('aria-hidden','false');
    body.innerHTML = '<div class="preview-loading"><span class="spinner"></span> Loading compensation details…</div>';
    try {
        const response = await fetch('salary_info.php?id=' + encodeURIComponent(id), {headers:{'X-Requested-With':'XMLHttpRequest'}});
        if (!response.ok) throw new Error('Request failed');
        body.innerHTML = await response.text();
    } catch (error) {
        body.innerHTML = '<div class="notice err">Unable to load salary information. Please try again.</div>';
    }
}
function closeSalaryModal(){ const el=document.getElementById('salaryModal'); el.classList.remove('show'); el.setAttribute('aria-hidden','true'); }
document.addEventListener('keydown', function(e){ if(e.key==='Escape'){ closeEmployeePreview(); closeSalaryModal(); } });
document.querySelectorAll('.detail-modal-overlay').forEach(function(el){ el.addEventListener('click', function(e){ if(e.target===el){ el.classList.remove('show'); el.setAttribute('aria-hidden','true'); } }); });
function printEmployeePreview(){
    const overlay=document.getElementById('employeePreviewModal');
    if(!overlay.classList.contains('show')) return;
    window.print();
}
</script>
</body>
</html>
