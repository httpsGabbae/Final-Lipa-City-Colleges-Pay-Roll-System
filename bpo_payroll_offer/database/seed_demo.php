<?php
require_once __DIR__ . '/../config/config.php';
$db = bpo_db();
$db->exec(file_get_contents(__DIR__ . '/schema.sql'));
$st = $db->prepare('INSERT OR IGNORE INTO members (full_name, email, hourly_rate, overtime_enabled) VALUES (?, ?, ?, ?)');
$st->execute(['Demo Agent', 'demo.agent@example.com', 150.00, 0]);
echo "Seeded database/bpo.sqlite\n";
