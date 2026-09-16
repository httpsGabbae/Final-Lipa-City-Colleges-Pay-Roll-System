CREATE TABLE IF NOT EXISTS members (
    member_id INTEGER PRIMARY KEY AUTOINCREMENT,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    team TEXT NOT NULL DEFAULT '',
    position TEXT NOT NULL DEFAULT '',
    employment_status TEXT NOT NULL DEFAULT 'Active',
    hourly_rate REAL NOT NULL DEFAULT 0,
    overtime_enabled INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS attendance_logs (
    log_id INTEGER PRIMARY KEY AUTOINCREMENT,
    member_id INTEGER NOT NULL REFERENCES members(member_id),
    work_date TEXT NOT NULL,
    time_in TEXT,
    time_out TEXT,
    status TEXT NOT NULL DEFAULT 'Open',
    remarks TEXT NOT NULL DEFAULT '',
    source TEXT NOT NULL DEFAULT 'web',
    activity_score REAL,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE(member_id, work_date)
);

CREATE TABLE IF NOT EXISTS adjustments (
    adjustment_id INTEGER PRIMARY KEY AUTOINCREMENT,
    member_id INTEGER NOT NULL REFERENCES members(member_id),
    work_date TEXT NOT NULL,
    label TEXT NOT NULL,
    amount REAL NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
