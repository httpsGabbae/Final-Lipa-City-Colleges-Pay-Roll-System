<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

$db = bpo_db();
bpo_migrate($db);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $do = (string)($_POST['do'] ?? '');
    if ($do === 'overtime') {
        $mid = (int)($_POST['member_id'] ?? 0);
        $enabled = !empty($_POST['enabled']) ? 1 : 0;
        if ($mid > 0) {
            $db->prepare('UPDATE members SET overtime_enabled = ? WHERE member_id = ?')->execute([$enabled, $mid]);
            $msg = 'Overtime switch updated.';
        }
    } elseif ($do === 'add') {
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $team = trim((string)($_POST['team'] ?? ''));
        $position = trim((string)($_POST['position'] ?? ''));
        $status = ($_POST['employment_status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';
        $rate = max(0, (float)($_POST['hourly_rate'] ?? 0));
        $ot = !empty($_POST['overtime_enabled']) ? 1 : 0;
        if ($fullName === '' || $email === '') {
            $err = 'Full name and email are required.';
        } else {
            try {
                $db->prepare('INSERT INTO members (full_name, email, team, position, employment_status, hourly_rate, overtime_enabled) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$fullName, $email, $team, $position, $status, $rate, $ot]);
                $msg = 'Member added.';
            } catch (PDOException $e) {
                $err = 'Could not add member (email may already exist).';
            }
        }
    }
    if ($msg === '' && $err === '') {
        header('Location: members.php');
        exit;
    }
    if ($msg !== '' && $err === '') {
        header('Location: members.php?added=1');
        exit;
    }
}

$search = trim((string)($_GET['search'] ?? ''));
if (isset($_GET['added'])) $msg = 'Member added.';
if ($search !== '') {
    $like = '%' . $search . '%';
    $st = $db->prepare("SELECT * FROM members WHERE full_name LIKE ? OR email LIKE ? OR team LIKE ? OR position LIKE ? ORDER BY full_name");
    $st->execute([$like, $like, $like, $like]);
    $members = $st->fetchAll(PDO::FETCH_ASSOC);
} else {
    $members = $db->query('SELECT * FROM members ORDER BY full_name')->fetchAll(PDO::FETCH_ASSOC);
}

$total = count($members);
$active = 0;
$otOn = 0;
$teams = [];
foreach ($members as $m) {
    if (($m['employment_status'] ?? 'Active') === 'Active') $active++;
    if (!empty($m['overtime_enabled'])) $otOn++;
    $t = trim((string)($m['team'] ?? ''));
    if ($t !== '') $teams[$t] = true;
}
$teamCount = count($teams);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Members — BPO SUITE</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="app">
<?php bpo_sidebar('members'); ?>
<main class="main">
<?php bpo_topbar('Members', 'BPO SUITE · team directory · shift 8:00 AM – 5:00 PM'); ?>
<div class="content">
<?php if ($msg): ?><div class="notice ok"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="notice err"><?= h($err) ?></div><?php endif; ?>

<section class="stats">
<div class="card stat"><div class="eyebrow">TOTAL</div><div class="stat-number"><?= (int)$total ?></div><div class="mini">members<?= $search !== '' ? ' matching search' : '' ?></div></div>
<div class="card stat"><div class="eyebrow">ACTIVE</div><div class="stat-number"><?= (int)$active ?></div><div class="mini">employment status Active</div></div>
<div class="card stat"><div class="eyebrow">OVERTIME ON</div><div class="stat-number"><?= (int)$otOn ?></div><div class="mini">paid past 5:00 PM at ×<?= h(BPO_OVERTIME_MULTIPLIER) ?></div></div>
<div class="card stat"><div class="eyebrow">TEAMS</div><div class="stat-number"><?= (int)$teamCount ?></div><div class="mini">distinct team names</div></div>
</section>

<section class="card">
<div class="card-head"><div><div class="eyebrow">BPO SUITE · DIRECTORY</div><h2>Member list</h2><p class="mini"><?= (int)$total ?> found · pay starts at Time In, billed from 8:00 AM</p></div></div>
<form class="toolbar" method="get">
<div><label>Search</label><input name="search" value="<?= h($search) ?>" placeholder="Name, email, team, or position"></div>
<button class="btn" type="submit">Search</button>
<a class="btn secondary" href="members.php">Clear</a>
</form>
<div class="table-wrap">
<table class="table">
<thead><tr><th>Member</th><th>Team / Position</th><th>Status</th><th>Rate</th><th>Overtime</th></tr></thead>
<tbody>
<?php if (!$members): ?><tr><td colspan="5" class="mini">No members matched your search.</td></tr><?php endif; ?>
<?php foreach ($members as $m): ?>
<tr>
<td><strong><?= h($m['full_name']) ?></strong><div class="mini"><?= h($m['email']) ?></div></td>
<td><?= h($m['team'] !== '' ? $m['team'] : '—') ?><div class="mini"><?= h($m['position'] !== '' ? $m['position'] : 'No position') ?></div></td>
<td><span class="badge <?= ($m['employment_status'] ?? 'Active') === 'Active' ? 'ontime' : 'late' ?>"><?= h($m['employment_status'] ?? 'Active') ?></span></td>
<td><?= pesos($m['hourly_rate']) ?>/hr</td>
<td>
<span class="badge <?= !empty($m['overtime_enabled']) ? 'ontime' : '' ?>"><?= !empty($m['overtime_enabled']) ? 'OT ON' : 'OT OFF' ?></span>
<form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="do" value="overtime"><input type="hidden" name="member_id" value="<?= (int)$m['member_id'] ?>"><input type="hidden" name="enabled" value="<?= !empty($m['overtime_enabled']) ? '0' : '1' ?>"><button class="btn secondary" type="submit">Turn <?= !empty($m['overtime_enabled']) ? 'OFF' : 'ON' ?></button></form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</section>

<section class="card">
<div class="eyebrow">BPO SUITE · ADD</div><h2>Add member</h2>
<form method="post">
<?= csrf_field() ?><input type="hidden" name="do" value="add">
<div class="toolbar">
<div><label>Full name</label><input name="full_name" required maxlength="120"></div>
<div><label>Email</label><input name="email" type="email" required maxlength="160"></div>
<div><label>Team</label><input name="team" maxlength="80" placeholder="e.g. Voice"></div>
<div><label>Position</label><input name="position" maxlength="80" placeholder="e.g. Agent"></div>
<div><label>Employment status</label><select name="employment_status"><option value="Active">Active</option><option value="Inactive">Inactive</option></select></div>
<div><label>Hourly rate</label><input name="hourly_rate" type="number" min="0" step="0.01" value="150" required></div>
<div><label>Overtime</label><select name="overtime_enabled"><option value="0">OT off</option><option value="1">OT on</option></select></div>
</div>
<p><button class="btn" type="submit">Add member</button></p>
</form>
<p class="mini">Overtime toggle accrues hours past 5:00 PM at ×<?= h(BPO_OVERTIME_MULTIPLIER) ?>. Shift shown as 8:00 AM – 5:00 PM.</p>
</section>

</div>
</main>
</div>
<script>document.getElementById('themeToggle')?.addEventListener('click',()=>document.body.classList.toggle('light'));</script>
</body>
</html>
