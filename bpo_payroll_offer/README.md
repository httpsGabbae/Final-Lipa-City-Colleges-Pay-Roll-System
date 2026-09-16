# BPO Suite — Hourly Timekeeping Offer (Demo)

Generic BPO timekeeping + hourly payroll demo. No school branding.

## What it does
- Google sign-in gates **Time In** (demo stub works now, real OAuth when keys are added).
- Pay starts at Time In, stops at Time Out. Shift is **8:00 AM – 5:00 PM**.
- Hidden grace period: lateness is assessed internally, but the UI only ever shows **8:00 AM**.
- Hourly pay = billable hours × member rate. Overtime beyond 5:00 PM only when an admin enables it per member (default 1.25×).
- SQLite storage for a zero-setup offer demo (swap to MySQL later).

## Run
1. `cd bpo_payroll_offer`
2. `composer install` (optional until real Google keys are used)
3. `php database/seed_demo.php` (creates `database/bpo.sqlite` + demo member)
4. Serve via XAMPP: open `http://localhost/bpo_payroll_offer/index.php` (standalone deploy; current dev path nests under the old system folder only for staging)

## Real Google OAuth (later)
1. Create OAuth Client (Web) in Google Cloud, add redirect `.../bpo_payroll_offer/employee/time_in.php?action=google_callback`.
2. Copy `.env.example` to `.env`, set `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URL`, `APP_URL`.
3. `composer require google/apiclient vlucas/phpdotenv`
4. In `config/google.php` set `'mode' => 'real'`. Demo button disappears.

## Future (not built)
- Desktop EXE for keyboard/mouse activity tracking. This demo only tracks Time In/Out + billable hours. The `attendance_logs` table has `source` + `activity_score` columns reserved for that feed.
