# LCC Payroll System — Product MVP

> **One-liner:** A web-based employee + payroll workspace for Lipa City Colleges: one admin app for people, attendance, pay, reports, and print-ready records — plus a self-service portal for employees.
>
> **Status:** Actively developed. This document defines the **Minimum Viable Product**: the smallest scope that is genuinely usable by a real payroll administrator. Anything not listed here is explicitly out of MVP scope.
>
> Full system documentation lives in [`docs/README.md`](docs/README.md).

---

## 1. Problem

Payroll administration at LCC runs on employee records, attendance facts, pay computations, and formal printed documents. When these live in separate places (spreadsheets, paper files, chat messages), the failure modes are predictable:

- Employee data and pay data drift out of sync (wrong department, stale salary).
- Payroll is prepared without seeing attendance exceptions first.
- There is no single status trail (was this period's pay drafted, approved, or released?).
- Printed records are re-typed by hand instead of generated from the same data.

The MVP exists to remove exactly these four failure modes — nothing more.

## 2. Users

| Role | Who | Needs |
|---|---|---|
| **Payroll administrator** | LCC staff operating the system | Add/maintain employees, record attendance, prepare and track payroll, review monthly reports, print official records |
| **Employee** | LCC personnel with portal access | View own profile, check own payroll history, time in/out daily, change own password |

There is deliberately **no admin self-registration**: administrator accounts are provisioned out-of-band. The employee portal login is Employee ID + password issued by the administrator.

## 3. MVP Goal & Success Criteria

**Goal:** One administrator can run a complete monthly pay cycle for all employees — from record maintenance to printed reports — without leaving the system.

The MVP is done when **all** of these are true:

1. An admin can add an employee and find them in the directory within a minute.
2. A full pay cycle (Draft → Approved → Paid) can be completed for every active employee for one pay period.
3. The monthly report's totals reconcile exactly with the sum of its payroll records.
4. Every payroll record and employee record can be produced as a formal printed/PDF document.
5. An employee can sign into the portal and see only their own profile, pay history, and attendance.
6. The app is usable on a desktop and a 360px-wide phone, in light and dark mode.

## 4. Scope — Must-Have Modules (MVP)

### M1. Authentication & Sessions
- [x] Admin login with session gate on every protected page (`includes/auth.php`, `$_SESSION['admin_id']`)
- [x] Employee portal login with active-employment-status check (`employee/auth.php`)
- [x] Logout for both roles
- [x] Password change (min 8 chars) for both roles; no admin self-registration by design
- [x] CSRF token on every `POST` handler (`require_csrf()` / `csrf_field()`), 419 on failure
- [x] All HTML output escaped via `e()`

**Acceptance:** logged-out access to any `pages/*` (except login) redirects to login; forged POSTs are rejected; passwords stored hashed.

### M2. Workforce Dashboard
- [x] Greeting + one-line operational summary (active headcount, today's check-ins, this month's records)
- [x] Employee card with live headcount and 6-month hires sparkline from real data
- [x] Three drill-down rows: Attendance today, Payroll this month, Needs attention (late / missing department / missing salary)
- [x] No salary figures on the dashboard (privacy: money lives only in Payroll/Reports)

**Acceptance:** every number shown matches its source table; every row links to the screen that explains it.

### M3. Employee Management
- [x] Searchable/sortable directory (name, position, department) with photos and avatars
- [x] Add employee with locked-by-default form (explicit unlock → edit → save)
- [x] Full record: identity, personal info, contact, government IDs (SSS/PhilHealth/Pag-IBIG/TIN/ATM), employment, emergency contact, dependent, education, character reference
- [x] Photo upload (2MB max, JPG/PNG verified, old-file cleanup)
- [x] Auto-generated employee numbers (`25-NNNN`, configurable prefix/digits via `app_settings`)
- [x] Employment statuses via `active_employment_statuses()` — never hardcoded lists
- [x] Dossier-style read-only record + salary view kept separate from the directory

**Acceptance:** duplicate employee numbers impossible; delete requires confirm; inactive/separated staff excluded from active counts but never silently dropped.

### M4. Departments & Positions
- [x] CRUD for departments (name, code, description, Active/Inactive) with per-department identity color
- [x] Positions nested per department, deletable only when no employee holds them
- [x] Department dropdown feeds the employee form (positions filter by chosen department)
- [x] Smooth expand/collapse of department cards; responsive stacking on small screens

**Acceptance:** deleting a department/position that is in use is refused with the affected headcount; inactive departments disappear from the employee form.

### M5. Attendance
- [x] Admin view: daily records with filters, late/present/absent/leave/half-day badges, printable report
- [x] Employee self-service: one-click Time In / Time Out with live clock, personal history + print
- [x] Today's exceptions (late/incomplete) surface on the dashboard *before* payroll runs

**Acceptance:** an employee cannot have two open time-ins for the same day; admin report prints cleanly in both themes.

### M6. Payroll Processing (the core transaction)
- [x] Create payroll per employee + pay period: allowances, other earnings, deductions editable; **basic salary always read from the employee record, never from POST**
- [x] Enforced math: `gross = basic + allowances + other_earnings`, `net = gross − deductions`
- [x] Enforced guards: no negatives, `deductions ≤ gross`, `period_end ≥ period_start`, no duplicate `(employee_id, period_start, period_end)`
- [x] Status lifecycle **Draft → Approved → Paid** via the single `update_status` action only
- [x] Payslip preview + individual payroll PDF

**Acceptance:** recomputing any record from its components reproduces gross/net to the centavo; illegal states are rejected server-side, not just hidden in the UI.

### M7. Monthly Reports
- [x] Month picker with totals: records, gross, deductions, net, paid count
- [x] Department payroll breakdown (identity-colored bars)
- [x] 6-month Gross-vs-Net trend chart
- [x] Month-overlapping query predicate kept everywhere (`period_start <= monthEnd AND period_end >= monthStart`)

**Acceptance:** report totals equal the SQL aggregates of the listed records; a payroll spanning two months appears in both.

### M8. Print & PDF Output
- [x] FPDF documents: single employee, employee directory, all profiles, single payslip, all payroll, monthly report — all with `Page X of Y` footers
- [x] Browser-print paths (directory, attendance, reports, payroll statement, employee preview) that force a light theme on paper regardless of dark mode
- [x] Money formatted as `PHP …` in PDFs (never `₱`, which FPDF cannot render); `₱ …` only in HTML

**Acceptance:** a printed page contains record data only — no nav, buttons, or dark backgrounds.

### M9. Employee Self-Service Portal
- [x] Dashboard with today's attendance state, recent payroll, profile completeness
- [x] Read-only profile, personal payroll history with print, attendance history with print
- [x] Sample seeded login from fresh install: `25-0001` / `Employee@123`

**Acceptance:** employees see zero records belonging to anyone else (verified at the SQL level, not just hidden links).

### M10. Cross-Cutting UX Baseline
- [x] Responsive layouts (desktop rail sidebar → mobile drawer, thumb-reach actions)
- [x] Dark mode with no light-surface leaks, honoring `prefers-reduced-motion` / transparency / contrast
- [x] Uniform type scale and one-color-per-meaning hierarchy (green = good, amber = needs eyes, red = destructive, blue = info, violet = special)
- [x] Instant press feedback, interruptible motion, printable-everything discipline

## 5. Explicitly OUT of MVP Scope

These are real needs but **not** required for v1. Do not build them before every box in §4 is checked:

- Automatic tax/SSS/PhilHealth/Pag-IBIG computation tables (deductions are entered amounts in MVP)
- Bank disbursement files / payroll auto-release to accounts
- Multi-admin roles and permissions (single admin role in MVP)
- Audit log / activity trail UI
- Email/SMS notifications (payslip ready, password reset)
- Leave management and overtime rules engine
- Biometric/time-clock hardware integration
- Multi-branch / multi-company support
- Public API

## 6. Core User Journeys (must all work end-to-end)

1. **Hire → pay:** Add employee → auto-number issued → appears in directory → payroll created for period → Draft → Approved → Paid → appears in monthly report → payslip printed.
2. **Exception first:** Employee times in late → dashboard "Needs attention" shows it → admin reviews attendance → then runs payroll.
3. **Fix the record:** Missing department/salary flagged on dashboard → admin completes the employee record → flag clears.
4. **Prove it on paper:** Any record or monthly report prints as a formal document from the same data shown on screen.
5. **Employee view:** Employee signs in → checks today's attendance state → reviews a past payslip → prints it.

## 7. Non-Functional Requirements

| Area | MVP bar |
|---|---|
| Security | Session gates, CSRF on all POSTs, escaped output, hashed passwords, salary never trusted from client input |
| Data integrity | Duplicate-period guard, non-negative amounts, status machine enforced server-side |
| Performance | Directory/report queries paginated or capped; pages usable on shared hosting + XAMPP defaults |
| Compatibility | Current Chrome/Edge/Firefox/Safari; 360px phones; print to A4 |
| Accessibility | Focus-visible states, `aria` labels on icon buttons, reduced-motion fallbacks |
| Maintainability | No framework to learn: plain PHP + MySQLi, shared `includes/`, one design-system stylesheet layered over page CSS |

## 8. Tech & Run (5 minutes)

- **Stack:** Plain PHP + MySQLi, FPDF via Composer (`setasign/fpdf`). No framework, no build step.
- **Run:** XAMPP → place repo as a docroot subfolder → entry `index.php` redirects to `pages/login.php`.
- **Database:** `config/database.php` → `localhost / root / '' / lcc_payroll`, `Asia/Manila`. Create the empty DB in phpMyAdmin, then import the canonical fresh-install schema from `database/` (`schema.sql`). Fresh installs only — never import over an old DB unless replacing it. (If `database/` is empty in your checkout, obtain the schema file before proceeding.)
- **Check a change:** `php -l <file>` + load the page. No tests/lint/CI in MVP.
- **Standalone demo (not part of MVP):** `bpo_payroll_offer/` is a separate SQLite demo with its own README — shares nothing with the main app.

## 9. Suggested Build Order (if resuming from zero)

1. Auth + DB + employee CRUD + auto-numbering (M1, M3-core)
2. Departments/positions + employee form wiring (M4)
3. Attendance, admin + employee sides (M5)
4. Payroll math + status lifecycle (M6) ← the riskiest module; build it early
5. Dashboard + reports (M2, M7)
6. Portal self-service (M9)
7. Print/PDF + print-theme discipline (M8)
8. UX baseline pass: responsive, dark mode, motion, hierarchy (M10)
9. Reconciliation testing against §3 success criteria, then release v1

## 10. Definition of Done (per feature)

- Code follows repo conventions (`AGENTS.md`): auth include first, `require_csrf()` on POSTs, `e()` on output, `money()`/`pdf_money()` correctly, no hardcoded employment-status lists.
- Works in light **and** dark mode, desktop **and** 360px mobile.
- Printable where the module promises print.
- `php -l` clean; manual walkthrough of its §6 journey passes.
