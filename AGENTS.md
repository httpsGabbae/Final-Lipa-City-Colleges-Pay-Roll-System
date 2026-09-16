# AGENTS.md — LCC Payroll System

## Stack / run
- Plain PHP + MySQLi (no framework/router), FPDF via Composer (`setasign/fpdf`). No tests, lint, or CI.
- Run under XAMPP: docroot subfolder, entry `index.php` → `pages/login.php`. Verify with `php -l <file>` + manual page load.
- DB: `config/database.php` hardcodes `localhost / root / '' / lcc_payroll`, timezone `Asia/Manila` + `SET time_zone='+08:00'`. Setup: create empty DB in phpMyAdmin, import `database/schema.sql` (header warns: never import into old DB unless replacing it).

## Repo boundaries
- Root app = LCC admin system (`pages/`, `auth/`, `includes/`, `print/`, `employee/` portal, `assets/`, `uploads/`, `database/schema.sql`).
- `bpo_payroll_offer/` is a separate standalone SQLite demo with its own `README.md`, `index.php`, `config/`. Do not share includes/assets between the two; see its README for its own run steps.
- `vendor/` (FPDF) and `*.zip` archives at root are committed; leave alone.

## Conventions that matter
- Protected admin pages must start with `require_once __DIR__.'/../includes/auth.php'` (redirects to `pages/login.php` if no `$_SESSION['admin_id']`). Employee-portal pages use `employee/auth.php` instead (`$_SESSION['employee_id']`, relative `login.php` redirect, plus active-employment-status check).
- Every `POST` handler in `pages/`/`print/` must call `require_csrf()` and every form must echo `csrf_field()` (helpers in `config/database.php`; 419 on failure).
- Output: use `e()` for all HTML interpolation. Money: `money()` (`₱ …`) for HTML, `pdf_money()` (`PHP …`) + `pdf_text()` (UTF-8→windows-1252) for FPDF. Never put `₱` directly in PDFs.
- Shared UI: `includes/layout.php` — `sidebar($active)`, `topbar($title)`, `tutorial()`, `ui_icon()`. Shared PDF: `includes/pdf.php` — `pdf_header()`, `pdf_footer()`, `pdf_*` row helpers. `print/*.php` are FPDF documents requiring `includes/auth.php` + `includes/pdf.php`, not web views.
- Payroll math (see `pages/payroll.php`): `gross = basic_salary (from employees, never from POST) + allowances + other_earnings`; `net = gross - deductions`. Reject negatives, `deductions > gross`, `end < start`, and duplicate `(employee_id, period_start, period_end)`. Status changes go only through the `update_status` action with `Draft|Approved|Paid`.
- Employees: display names via `employee_name()`; employee numbers via `next_employee_no()` / `app_settings` (`employee_number_prefix` + `employee_number_digits`, default `25-NNNN`). Employment status must use `active_employment_statuses()` / `is_active_employment_status()`, not hardcoded lists.
- Photos: always use `upload_employee_photo()` / `delete_employee_photo()` in `includes/auth.php` (2MB max, JPG/PNG + `getimagesize` check, `uploads/employee_photos/emp_…` naming, old-file cleanup, path guard). Logos live at `uploads/logo*.jpg|png` (`layout.php` uses `uploads/logo.png`, login uses `uploads/logo1.jpg`, PDFs use `uploads/logo.png`).
- Assets use `?v=` cache-bust query strings — bump when changing CSS/JS. Password change posts to `auth/change_password.php` (min 8 chars); there is no admin self-registration by design.

## Gotchas
- `database/` has three SQL files; only `schema.sql` is the canonical fresh-install schema (others are legacy portals).
- Reports/print overlap months (`period_start <= monthEnd AND period_end >= monthStart`) — keep that predicate when touching monthly queries.
- Sample portal login seeded in `schema.sql`: `25-0001` / `Employee@123`.
