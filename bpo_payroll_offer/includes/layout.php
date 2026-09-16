<?php
require_once __DIR__ . '/helpers.php';

function bpo_icon(string $name): string
{
    $paths = [
        'dashboard' => '<path d="M3 13h8V3H3v10Zm10 8h8V3h-8v18ZM3 21h8v-6H3v6Z"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'wallet' => '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H20a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5.5A2.5 2.5 0 0 1 3 17.5v-10Z"/><path d="M3 8h17M16 13h6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'chart' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 17v-3M12 17v-6M16 17v-4"/>',
        'login' => '<path d="M10 17l5-5-5-5M15 12H3M21 19V5a2 2 0 0 0-2-2h-5"/>',
    ];
    $p = $paths[$name] ?? $paths['dashboard'];
    return '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
}

function bpo_sidebar(string $active): void
{
    $items = [
        ['dashboard', 'dashboard', 'Dashboard', '../admin/dashboard.php'],
        ['members', 'users', 'Members', '../admin/members.php'],
        ['attendance', 'clock', 'Attendance', '../admin/attendance.php'],
        ['payroll', 'wallet', 'Payroll', '../admin/payroll.php'],
        ['timein', 'login', 'Agent Time In', '../employee/time_in.php'],
    ];
    echo '<aside class="sidebar"><a class="brand" href="../admin/dashboard.php"><span class="brand-mark">B</span><span class="brand-text"><span class="brand-title">BPO SUITE</span><br><span class="brand-sub">HOURLY OFFER</span></span></a><nav class="nav">';
    echo '<div class="nav-section">OVERVIEW</div>';
    foreach ($items as $it) {
        $a = $active === $it[0] ? ' active' : '';
        echo '<a class="nav-link' . $a . '" href="' . h($it[3]) . '">' . bpo_icon($it[1]) . ' <span class="nav-label">' . h($it[2]) . '</span></a>';
    }
    echo '</nav></aside>';
}

function bpo_topbar(string $title, string $sub = ''): void
{
    echo '<header class="topbar"><div><div class="topbar-title">' . h($title) . '</div>';
    if ($sub !== '') echo '<div class="mini">' . h($sub) . '</div>';
    echo '</div><div><button type="button" class="btn secondary" id="themeToggle">Theme</button></div></header>';
}
