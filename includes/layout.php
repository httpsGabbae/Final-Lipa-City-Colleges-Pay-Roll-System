<?php

function ui_icon(string $name, string $class = ''): string
{
    $paths = [
        'dashboard' => '<path d="M3 13h8V3H3v10Zm10 8h8V3h-8v18ZM3 21h8v-6H3v6Z"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'user-plus' => '<path d="M15 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M19 8v6M16 11h6"/>',
        'wallet' => '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H20a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5.5A2.5 2.5 0 0 1 3 17.5v-10Z"/><path d="M3 8h17M16 13h6"/><circle cx="16" cy="13" r="1" fill="currentColor" stroke="none"/>',
        'file-chart' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 17v-3M12 17v-6M16 17v-4"/>',
        'eye' => '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/>',
        'pencil' => '<path d="m4 20 4.5-1L19 8.5a2.12 2.12 0 0 0-3-3L5.5 16 4 20Z"/><path d="m14.5 7.5 3 3"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 14h10l1-14M9 7V4h6v3"/>',
        'printer' => '<path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6z"/><circle cx="18" cy="12" r="1" fill="currentColor" stroke="none"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow-left' => '<path d="M19 12H5M12 19l-7-7 7-7"/>',
        'arrow-right' => '<path d="M5 12h14M12 5l7 7-7 7"/>',
        'more' => '<circle cx="5" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="19" cy="12" r="1" fill="currentColor" stroke="none"/>',
        'close' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'trend' => '<path d="M3 17l6-6 4 4 8-9"/><path d="M15 6h6v6"/>',
        'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16M2 21h20M8 7h2M14 7h2M8 11h2M14 11h2M8 15h2M14 15h2M11 21v-4h2v4"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18M10 12v2h4v-2"/>',
        'shield' => '<path d="M12 3 20 6v5c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-3Z"/><path d="m9 12 2 2 4-4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    ];
    $path = $paths[$name] ?? $paths['more'];
    return '<svg class="ui-icon ' . e($class) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

function sidebar(string $active): void
{
    $items = [
        ['dashboard', 'dashboard', 'Dashboard', '../pages/admin_dashboard.php'],
        ['employees', 'users', 'Employees', '../pages/employees.php'],
        ['departments', 'building', 'Departments', '../pages/departments.php'],
        ['add', 'user-plus', 'Add Employee', '../pages/personalinfo.php?mode=new'],
        ['attendance', 'clock', 'Attendance', '../pages/attendance.php'],
        ['payroll', 'wallet', 'Payroll', '../pages/payroll.php'],
        ['reports', 'file-chart', 'Reports', '../pages/reports.php']
    ];

    echo '<aside class="sidebar" id="appSidebar">';
    echo '<button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Collapse sidebar" title="Collapse sidebar">' . ui_icon('arrow-left') . '</button>';
    echo '<a class="brand" href="../pages/admin_dashboard.php">';
    echo '<img class="brand-logo" src="../uploads/logo.png" alt="LCC Payroll">';
    echo '<div class="brand-text"><div class="brand-title">LCC PAYROLL</div><div class="brand-sub">EMPLOYEE SYSTEM</div></div>';
    echo '</a>';

    echo '<nav class="nav">';
    echo '<div class="nav-section">OVERVIEW</div>';
    foreach ($items as $item) {
        if ($item[0] === 'employees') echo '<div class="nav-section nav-section-spaced">PEOPLE</div>';
        if ($item[0] === 'attendance') echo '<div class="nav-section nav-section-spaced">WORKFORCE</div>';
        if ($item[0] === 'payroll') echo '<div class="nav-section nav-section-spaced">PAYROLL</div>';
        if ($item[0] === 'reports') echo '<div class="nav-section nav-section-spaced">INSIGHTS</div>';
        $isActive = $active === $item[0] ? ' active' : '';
        $mobilePriority = in_array($item[0], ['dashboard','employees','attendance','payroll'], true) ? ' mobile-priority' : ' mobile-secondary';
        echo '<a class="nav-link' . $isActive . $mobilePriority . '" href="' . e($item[3]) . '" title="' . e($item[2]) . '" data-nav-title="' . e($item[2]) . '">';
        echo '<span class="nav-icon">' . ui_icon($item[1]) . '</span><span class="nav-label">' . e($item[2]) . '</span>';
        echo '</a>';
    }
    echo '</nav>';
    echo '<div class="mobile-more-wrap">';
    echo '<button type="button" class="mobile-more-toggle" aria-expanded="false" aria-label="More navigation"><span class="nav-icon">' . ui_icon('more') . '</span><span class="nav-label">More</span></button>';
    echo '<div class="mobile-more-menu" role="menu">';
    echo '<a href="../pages/departments.php" role="menuitem"><span>' . ui_icon('building') . '</span>Departments</a>';
    echo '<a href="../pages/personalinfo.php?mode=new" role="menuitem"><span>' . ui_icon('user-plus') . '</span>Add Employee</a>';
    echo '<a href="../pages/reports.php" role="menuitem"><span>' . ui_icon('file-chart') . '</span>Reports</a>';
    echo '</div></div>';

    // echo '<div class="sidebar-help">';
    // echo '<strong>Simple steps</strong><span>1. Add an employee<br>2. Complete details<br>3. Process payroll<br>4. Print when ready</span>';
    // echo '</div>';
    echo '</aside>';
}

function topbar(string $title): void
{
    $adminName = $_SESSION['admin_name'] ?? 'Admin';
    $initial = strtoupper(substr(trim($adminName), 0, 1)) ?: 'A';

    echo '<header class="topbar">';
    echo '<div class="topbar-left">';
    echo '<div class="topbar-title">' . e($title) . '</div>';
    echo '</div>';

    echo '<div class="topbar-actions">';
    echo '<button type="button" class="icon-btn theme-toggle" id="themeToggle" aria-label="Toggle dark mode" title="Toggle dark mode"><span id="themeIcon">☾</span></button>';
    echo '<div class="admin-menu">';
    echo '<button type="button" class="admin-chip" id="adminMenuButton" aria-expanded="false">';
    echo '<span class="avatar">' . e($initial) . '</span><span class="admin-name">' . e($adminName) . '</span><span class="admin-chevron">⌄</span>';
    echo '</button>';
    echo '<div class="admin-dropdown" id="adminDropdown">';
    echo '<div class="admin-dropdown-name">' . e($adminName) . '</div>';
    echo '<button type="button" class="dropdown-item" id="openSettings"><span>⚙</span> Account Settings</button>';
    echo '<a class="dropdown-item danger-item" href="../auth/logout.php"><span>↪</span> Logout</a>';
    echo '</div></div></div>';
    echo '</header>';

    echo '<div class="settings-overlay" id="settingsOverlay" aria-hidden="true">';
    echo '<div class="settings-modal" role="dialog" aria-modal="true" aria-labelledby="settingsTitle">';
    echo '<div class="settings-head"><div><div class="eyebrow">ACCOUNT</div><h2 id="settingsTitle">Account Settings</h2></div><button type="button" class="modal-close" id="closeSettings" aria-label="Close">×</button></div>';
    echo '<div class="settings-body">';
    echo '<div class="settings-section"><h3>Change Password</h3><p>Change the password for the currently signed-in administrator.</p></div>';
    echo '<form id="changePasswordForm" method="post" action="../auth/change_password.php" autocomplete="off">';
    echo '<label>Current Password<input type="password" name="current_password" required></label>';
    echo '<label>New Password<input type="password" name="new_password" minlength="8" required><small>Use at least 8 characters.</small></label>';
    echo '<label>Confirm New Password<input type="password" name="confirm_password" minlength="8" required></label>';
    echo '<div class="admin-note"><strong>Administrator accounts</strong><span>This system does not allow administrators to create another admin account. To add a new admin, contact the developer.</span></div>';
    echo '<div id="passwordMessage" class="settings-message" hidden></div>';
    echo '<div class="settings-actions"><button type="button" class="btn btn-secondary" id="cancelSettings">Cancel</button><button type="submit" class="btn btn-primary">Confirm Password Change</button></div>';
    echo '</form></div></div></div>';
}


/*
|--------------------------------------------------------------------------
| Tutorial
|--------------------------------------------------------------------------
*/

function tutorial(): void
{
?>

    <div id="tutorialOverlay" class="tutorial-overlay">

        <!-- Step 1 -->
        <div class="tutorial-card" data-tutorial-step="0">

            <div class="tutorial-icon">👋</div>

            <div class="tutorial-label">
                ADMINISTRATOR GUIDE
            </div>

            <h2>
                Welcome to LCC Payroll!
            </h2>

            <p>
                This quick tutorial will show you
                how to use the payroll system.
            </p>

            <div class="tutorial-buttons">

                <button
                    type="button"
                    class="tutorial-skip"
                    onclick="skipTutorial()">
                    Skip
                </button>

                <button
                    type="button"
                    class="tutorial-next"
                    onclick="nextTutorial()">
                    Start Tour
                </button>

            </div>

        </div>


        <!-- Step 2 -->
        <div class="tutorial-card" data-tutorial-step="1">

            <div class="tutorial-icon">⌂</div>

            <div class="tutorial-label">
                STEP 1 OF 4
            </div>

            <h2>
                Dashboard
            </h2>

            <p>
                The Dashboard is your main page.
                You can return here anytime by clicking
                Dashboard on the left.
            </p>

            <div class="tutorial-buttons">

                <button
                    type="button"
                    class="tutorial-skip"
                    onclick="skipTutorial()">
                    Skip
                </button>

                <button
                    type="button"
                    class="tutorial-next"
                    onclick="nextTutorial()">
                    Next
                </button>

            </div>

        </div>


        <!-- Step 3 -->
        <div class="tutorial-card" data-tutorial-step="2">

            <div class="tutorial-icon">♙</div>

            <div class="tutorial-label">
                STEP 2 OF 4
            </div>

            <h2>
                Employees
            </h2>

            <p>
                Use Employees to view and manage
                the employee records already saved
                in the system.
            </p>

            <div class="tutorial-buttons">

                <button
                    type="button"
                    class="tutorial-skip"
                    onclick="skipTutorial()">
                    Skip
                </button>

                <button
                    type="button"
                    class="tutorial-next"
                    onclick="nextTutorial()">
                    Next
                </button>

            </div>

        </div>


        <!-- Step 4 -->
        <div class="tutorial-card" data-tutorial-step="3">

            <div class="tutorial-icon">＋</div>

            <div class="tutorial-label">
                STEP 3 OF 4
            </div>

            <h2>
                Add Employee
            </h2>

            <p>
                Use Add Employee when you need to
                register a new employee and enter
                their personal information.
            </p>

            <div class="tutorial-buttons">

                <button
                    type="button"
                    class="tutorial-skip"
                    onclick="skipTutorial()">
                    Skip
                </button>

                <button
                    type="button"
                    class="tutorial-next"
                    onclick="nextTutorial()">
                    Next
                </button>

            </div>

        </div>


        <!-- Step 5 -->
        <div class="tutorial-card" data-tutorial-step="4">

            <div class="tutorial-icon">₱</div>

            <div class="tutorial-label">
                STEP 4 OF 4
            </div>

            <h2>
                Payroll
            </h2>

            <p>
                Use Payroll to create payroll records,
                view previous payroll, and print
                payroll statements.
            </p>

            <div class="tutorial-buttons">

                <button
                    type="button"
                    class="tutorial-skip"
                    onclick="skipTutorial()">
                    Skip
                </button>

                <button
                    type="button"
                    class="tutorial-next"
                    onclick="nextTutorial()">
                    Finish
                </button>

            </div>

        </div>


        <!-- Progress -->
        <div class="tutorial-progress">

            <span class="tutorial-dot active"></span>
            <span class="tutorial-dot"></span>
            <span class="tutorial-dot"></span>
            <span class="tutorial-dot"></span>
            <span class="tutorial-dot"></span>

        </div>

    </div>


    <script>
        let tutorialStep = 0;

        const tutorialCards =
            document.querySelectorAll(
                '[data-tutorial-step]'
            );

        const tutorialDots =
            document.querySelectorAll(
                '.tutorial-dot'
            );


        function showTutorialStep(step) {
            tutorialCards.forEach(function(card) {

                card.classList.remove('active');

            });


            tutorialDots.forEach(function(dot) {

                dot.classList.remove('active');

            });


            if (tutorialCards[step]) {

                tutorialCards[step]
                    .classList
                    .add('active');

            }


            if (tutorialDots[step]) {

                tutorialDots[step]
                    .classList
                    .add('active');

            }
        }


        function nextTutorial() {
            if (tutorialStep <
                tutorialCards.length - 1) {

                tutorialStep++;

                showTutorialStep(
                    tutorialStep
                );

            } else {

                finishTutorial();

            }
        }


        function skipTutorial() {
            finishTutorial();
        }


        function finishTutorial() {
            const overlay =
                document.getElementById(
                    'tutorialOverlay'
                );

            if (overlay) {

                overlay.classList.remove(
                    'show'
                );

            }

            localStorage.setItem(
                'lccPayrollTutorialCompleted',
                'true'
            );
        }


        function startTutorial() {
            const overlay =
                document.getElementById(
                    'tutorialOverlay'
                );

            if (!overlay) {
                return;
            }


            tutorialStep = 0;

            showTutorialStep(0);

            overlay.classList.add(
                'show'
            );
        }


        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const completed =
                    localStorage.getItem(
                        'lccPayrollTutorialCompleted'
                    );


                /*
                 * Only automatically show the tutorial
                 * if the administrator has never
                 * completed it before.
                 */

                if (!completed) {

                    startTutorial();

                }

            }
        );
    </script>

<?php
}

?>