"""RED test for Paywise fix: distinct role dashboards + corporate (non-school) + no AI slop.
Run: python3 verify_paywise_fix.py
Must FAIL before fix, PASS after.
"""
import pathlib, re, sys

ROOT = pathlib.Path(r"D:\Xampp\htdocs\Pay-wise")
fails = []

def check(name, cond, hint=""):
    print(("PASS " if cond else "FAIL ") + name + (f" -- {hint}" if (not cond and hint) else ""))
    if not cond:
        fails.append(name)

def read(p):
    return (ROOT / p).read_text(encoding="utf-8", errors="ignore")

SCHOOL_TERMS = ["College of", "Dean", "Professor", "Instructor", "Lecturer",
                "CCTE", "CCJE", "CITE", "CELA", "Clinical Instructor"]
# CON is too short (false positives) - check as code 'CON' with quotes or standalone
CORP_DEPTS = ["Operations", "Finance", "Human Resources", "Information Technology", "Sales"]

admin = read("pages/admin_dashboard.php")
emp = read("employee/dashboard.php")
dept_page = read("pages/departments.php")
personal = read("pages/personalinfo.php")
layout = read("includes/layout.php")
dbconf = read("config/database.php")
tutorial = read("includes/layout.php")  # tutorial() lives here
polish = read("assets/css/dashboard-polish.css")
emp_css = read("assets/css/employee.css")

schema = read("paywise_schema.sql")
try:
    schema2 = read("schema.sql")
except Exception:
    schema2 = ""
try:
    schema3 = read("supabase_schema.sql")
except Exception:
    schema3 = ""
try:
    apple_css = read("assets/css/apple-system.css")
except Exception:
    apple_css = ""

# 1. No school terms in seed + fallbacks + placeholders
for term in SCHOOL_TERMS:
    check(f"no-school-term in paywise_schema.sql: {term}", term not in schema, f"found {term}")
    check(f"no-school-term in schema.sql: {term}", term not in schema2, f"found {term}")
    check(f"no-school-term in personalinfo.php: {term}", term not in personal, f"found {term}")
    check(f"no-school-term in departments.php: {term}", term not in dept_page, f"found {term}")

# corporate present
for d in CORP_DEPTS:
    check(f"corporate dept in paywise_schema.sql: {d}", d in schema)
    check(f"corporate dept in personalinfo fallback: {d}", d in personal)

# dept_color_key maps corporate codes
for code in ["OPS", "FIN", "HR", "IT", "SALES", "SUPPORT"]:
    check(f"dept_color_key maps {code}", code in dbconf or code.lower() in dbconf.lower())

# 2. Distinct dashboards per role (admin has 4 roles: superadmin/finance/hr + employee portal separate)
# Each role must have its own H1/task title, not shared "Good morning"
check("admin dashboard has superadmin control-center heading",
      "control center" in admin.lower() or "control centre" in admin.lower())
check("admin dashboard has finance pipeline heading",
      "payroll pipeline" in admin.lower())
check("admin dashboard has hr people-ops heading",
      "people operations" in admin.lower() or "people-ops" in admin.lower())
check("admin dashboard no generic greeting hero",
      "$greeting" not in admin and "good morning" not in admin.lower(),
      "still has Good morning/afternoon greeting")
check("employee dashboard has My work heading",
      "my work" in emp.lower())
check("employee dashboard primary action is Time In/Out first (attendance before profile)",
      emp.lower().find("time in") < emp.lower().find("my profile"),
      "profile hero still before attendance action")
# dashboards must not share identical H1
check("admin vs employee H1 differ",
      "my work" not in admin.lower() and ("control center" in admin.lower() or "payroll pipeline" in admin.lower()))

# 3-5. AI slop rules
check("admin dashboard has no eyebrow class", 'class="eyebrow"' not in admin and 'eyebrow">' not in admin,
      "eyebrow div still present")
check("employee dashboard has no eyebrow class", 'class="eyebrow"' not in emp,
      "eyebrow div still present")
check("dashboard-polish.css documents 5 fixes + role workspaces",
      "role" in polish.lower() and "flat" in polish.lower())
check("employee.css has no linear-gradient hero (flat)",
      "linear-gradient" not in emp_css,
      "gradient hero still present")
check("tutorial has no emoji icons",
      all(e not in tutorial for e in ["👋", "♙", "＋", "₱", "⌂"]),
      "emoji tutorial icons still present")
check("departments placeholders are corporate (no College/CITE/Instructor examples)",
      "College of Information Technology" not in dept_page and "CITE" not in dept_page and "Instructor" not in dept_page)

# Round 2: remaining school traces (supabase + css tokens + legacy db name + live migration)
for term in SCHOOL_TERMS:
    check(f"no-school-term in supabase_schema.sql: {term}", term not in schema3, f"found {term}")
for d in CORP_DEPTS:
    check(f"corporate dept in supabase_schema.sql: {d}", d in schema3)
    check(f"corporate dept in schema.sql: {d}", d in schema2)
for tok in ["--c-ccte", "--c-ccje", "--c-cela", "--c-nursing", "--c-cithm", "--c-cba", "--c-bsa"]:
    check(f"no school css token {tok}", tok not in apple_css.lower(), f"found {tok}")
for tok in ["--c-ops", "--c-fin", "--c-hr", "--c-it", "--c-sales", "--c-support"]:
    check(f"corporate css token {tok}", tok in apple_css.lower(), f"missing {tok}")
check("no legacy school db name lcc_payroll", "lcc_payroll" not in dbconf, "lcc_payroll fallback still present")
check("corporate migration exists",
      (ROOT / "migrate_to_corporate.sql").exists(),
      "migrate_to_corporate.sql missing - live DBs still show Colleges")

# Round 3: live database must hold corporate data (what the UI actually renders)
import subprocess
MYSQL = r"D:\Xampp\mysql\bin\mysql.exe"
def live_rows(db, sql):
    try:
        out = subprocess.run([MYSQL, "-u", "root", "-N", "-e", sql, db],
                             capture_output=True, text=True, timeout=30)
        if out.returncode != 0:
            return None
        return out.stdout
    except Exception:
        return None

LIVE_SCHOOL = ["College of", "Dean", "Professor", "Instructor", "Lecturer",
               "CCTE", "CCJE", "CELA", "CITM", "Clinical Instructor"]
live_text = ""
for _db, _q in [
    ("paywise_payroll", "SELECT department_name, department_code FROM departments"),
    ("paywise_payroll", "SELECT position_name FROM positions"),
    ("paywise_payroll", "SELECT department, position FROM employees"),
]:
    _r = live_rows(_db, _q)
    if _r is None:
        check(f"live db reachable: {_db} ({_q[:40]}…)", False, "mysql query failed")
    else:
        live_text += _r
if live_text:
    for term in LIVE_SCHOOL:
        check(f"no-school-term in live paywise_payroll: {term}",
              term.lower() not in live_text.lower(), f"live DB still shows {term}")
    for d in CORP_DEPTS:
        check(f"corporate dept in live paywise_payroll: {d}",
              d.lower() in live_text.lower(), f"live DB missing {d}")

print()
if fails:
    print(f"{len(fails)} FAILING: {', '.join(fails)}")
    sys.exit(1)
print("ALL CHECKS PASS")
