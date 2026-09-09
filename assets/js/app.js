document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;
    const sidebar = document.getElementById('appSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const adminButton = document.getElementById('adminMenuButton');
    const adminDropdown = document.getElementById('adminDropdown');
    const settingsOverlay = document.getElementById('settingsOverlay');
    const openSettings = document.getElementById('openSettings');
    const closeSettings = document.getElementById('closeSettings');
    const cancelSettings = document.getElementById('cancelSettings');
    const passwordForm = document.getElementById('changePasswordForm');
    const passwordMessage = document.getElementById('passwordMessage');

    const mobileQuery = window.matchMedia('(max-width: 820px)');
    const isMobile = () => mobileQuery.matches;

    const savedSidebar = localStorage.getItem('lccSidebarCollapsed');
    const savedMobileRail = localStorage.getItem('lccMobileSidebarExpanded');
    if (isMobile()) {
        if (savedMobileRail !== 'true') body.classList.add('sidebar-collapsed');
    } else if (savedSidebar === 'true') {
        body.classList.add('sidebar-collapsed');
    }

    function syncMobileSidebarState() {
        const mobile = isMobile();
        const expanded = mobile && !body.classList.contains('sidebar-collapsed');

        document.querySelectorAll('.sidebar, .employee-sidebar').forEach(function (el) {
            el.classList.toggle('mobile-collapsed', mobile && !expanded);
            el.classList.toggle('mobile-expanded', expanded);
        });
    }

    function updateSidebarButton() {
        if (!sidebarToggle) return;
        const collapsed = body.classList.contains('sidebar-collapsed');
        syncMobileSidebarState();
        sidebarToggle.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        sidebarToggle.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
        sidebarToggle.innerHTML = collapsed
            ? '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>'
            : '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>';
    }

    sidebarToggle?.addEventListener('click', function (event) {
        event.stopPropagation();
        body.classList.toggle('sidebar-collapsed');
        if (isMobile()) {
            localStorage.setItem('lccMobileSidebarExpanded', body.classList.contains('sidebar-collapsed') ? 'false' : 'true');
        } else {
            localStorage.setItem('lccSidebarCollapsed', body.classList.contains('sidebar-collapsed'));
        }
        updateSidebarButton();
    });

    // Navigation links must NEVER toggle the sidebar on mobile. Preserve the
    // current rail/drawer state across the page navigation. This prevents a
    // tap on Dashboard, Employees, Attendance, Payroll, etc. from briefly
    // opening the drawer and then collapsing it again.
    document.querySelectorAll('.sidebar .nav-link, .employee-sidebar .nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
            if (!isMobile()) return;
            const collapsed = body.classList.contains('sidebar-collapsed');
            localStorage.setItem('lccMobileSidebarExpanded', collapsed ? 'false' : 'true');
        }, true);
    });

    // The sidebar itself is not a toggle target. Only the dedicated arrow
    // button may change collapsed/expanded state.
    sidebar?.addEventListener('click', function (event) {
        const link = event.target.closest('.nav-link');
        if (link) event.stopPropagation();
    });

    function handleViewportChange() {
        if (isMobile() && localStorage.getItem('lccMobileSidebarExpanded') !== 'true') {
            body.classList.add('sidebar-collapsed');
        }
        syncMobileSidebarState();
        updateSidebarButton();
    }

    window.addEventListener('resize', handleViewportChange);
    if (mobileQuery.addEventListener) {
        mobileQuery.addEventListener('change', handleViewportChange);
    } else {
        mobileQuery.addListener(handleViewportChange);
    }

    const savedTheme = localStorage.getItem('lccTheme');
    if (savedTheme === 'dark') body.classList.add('dark-mode');

    function updateThemeIcon() {
        if (themeIcon) themeIcon.textContent = body.classList.contains('dark-mode') ? '☀' : '☾';
    }
    updateThemeIcon();

    themeToggle?.addEventListener('click', function () {
        body.classList.toggle('dark-mode');
        localStorage.setItem('lccTheme', body.classList.contains('dark-mode') ? 'dark' : 'light');
        updateThemeIcon();
    });

    adminButton?.addEventListener('click', function (event) {
        event.stopPropagation();
        const open = adminDropdown.classList.toggle('show');
        adminButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.admin-menu')) {
            adminDropdown?.classList.remove('show');
            adminButton?.setAttribute('aria-expanded', 'false');
        }
    });

    // Mobile app-style More menu. Desktop is unaffected by this behavior.
    const mobileMoreToggle = document.querySelector('.mobile-more-toggle');
    const mobileMoreMenu = document.querySelector('.mobile-more-menu');

    function closeMobileMore() {
        mobileMoreMenu?.classList.remove('show');
        mobileMoreToggle?.setAttribute('aria-expanded', 'false');
    }

    mobileMoreToggle?.addEventListener('click', function (event) {
        event.stopPropagation();
        if (!isMobile()) return;
        const open = mobileMoreMenu?.classList.toggle('show');
        mobileMoreToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.mobile-more-wrap')) closeMobileMore();
    });

    window.addEventListener('resize', function () {
        if (!isMobile()) closeMobileMore();
    });

    // On mobile, tapping outside the drawer closes the expanded navigation.
    document.addEventListener('click', function (event) {
        if (!isMobile()) return;
        if (!body.classList.contains('sidebar-collapsed')) {
            if (!event.target.closest('.sidebar') && !event.target.closest('.employee-sidebar')) {
                body.classList.add('sidebar-collapsed');
                localStorage.setItem('lccMobileSidebarExpanded', 'false');
                updateSidebarButton();
            }
        }
    });

    function closeSettingsModal() {
        settingsOverlay?.classList.remove('show');
        settingsOverlay?.setAttribute('aria-hidden', 'true');
        passwordForm?.reset();
        if (passwordMessage) passwordMessage.hidden = true;
    }

    openSettings?.addEventListener('click', function () {
        adminDropdown?.classList.remove('show');
        adminButton?.setAttribute('aria-expanded', 'false');
        settingsOverlay?.classList.add('show');
        settingsOverlay?.setAttribute('aria-hidden', 'false');
        settingsOverlay?.querySelector('input')?.focus();
    });
    closeSettings?.addEventListener('click', closeSettingsModal);
    cancelSettings?.addEventListener('click', closeSettingsModal);
    settingsOverlay?.addEventListener('click', function (event) {
        if (event.target === settingsOverlay) closeSettingsModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeSettingsModal();
    });

    passwordForm?.addEventListener('submit', function (event) {
        const newPassword = passwordForm.elements.new_password.value;
        const confirmPassword = passwordForm.elements.confirm_password.value;
        if (newPassword !== confirmPassword) {
            event.preventDefault();
            passwordMessage.textContent = 'The new passwords do not match.';
            passwordMessage.className = 'settings-message error';
            passwordMessage.hidden = false;
            return;
        }
        if (newPassword.length < 8) {
            event.preventDefault();
            passwordMessage.textContent = 'The new password must be at least 8 characters.';
            passwordMessage.className = 'settings-message error';
            passwordMessage.hidden = false;
        }
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('password_changed') === '1') {
        alert('Password changed successfully.');
        history.replaceState({}, '', window.location.pathname);
    }
    if (params.get('password_error')) {
        settingsOverlay?.classList.add('show');
        settingsOverlay?.setAttribute('aria-hidden', 'false');
        passwordMessage.textContent = params.get('password_error');
        passwordMessage.className = 'settings-message error';
        passwordMessage.hidden = false;
        history.replaceState({}, '', window.location.pathname);
    }

    updateSidebarButton();
});
