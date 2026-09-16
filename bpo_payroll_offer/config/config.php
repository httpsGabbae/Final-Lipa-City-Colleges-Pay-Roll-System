<?php
date_default_timezone_set('Asia/Manila');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// Never display the grace cutoff. UI only shows SHIFT_START ("8:00 AM").
const BPO_SHIFT_START = '08:00';
const BPO_SHIFT_END = '17:00';
const BPO_GRACE_MINUTES = 5;
const BPO_OVERTIME_MULTIPLIER = 1.25;
const BPO_DB_FILE = __DIR__ . '/../database/bpo.sqlite';

function bpo_db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO('sqlite:' . BPO_DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function bpo_migrate(PDO $db): void
{
    $db->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    $cols = [];
    foreach ($db->query("PRAGMA table_info(members)")->fetchAll(PDO::FETCH_ASSOC) as $c) $cols[] = $c['name'];
    foreach (['team' => "ALTER TABLE members ADD COLUMN team TEXT NOT NULL DEFAULT ''", 'position' => "ALTER TABLE members ADD COLUMN position TEXT NOT NULL DEFAULT ''", 'employment_status' => "ALTER TABLE members ADD COLUMN employment_status TEXT NOT NULL DEFAULT 'Active'"] as $name => $sql) {
        if (!in_array($name, $cols, true)) $db->exec($sql);
    }
    $acols = [];
    foreach ($db->query("PRAGMA table_info(attendance_logs)")->fetchAll(PDO::FETCH_ASSOC) as $c) $acols[] = $c['name'];
    if (!in_array('remarks', $acols, true)) $db->exec("ALTER TABLE attendance_logs ADD COLUMN remarks TEXT NOT NULL DEFAULT ''");
}
