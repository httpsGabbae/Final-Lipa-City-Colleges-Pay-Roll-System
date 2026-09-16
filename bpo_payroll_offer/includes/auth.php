<?php
require_once __DIR__ . '/helpers.php';

function google_user(): ?array { return $_SESSION['google_user'] ?? null; }
function current_member(PDO $db): ?array
{
    $id = (int)($_SESSION['member_id'] ?? 0);
    if ($id <= 0) return null;
    $st = $db->prepare('SELECT * FROM members WHERE member_id = ? LIMIT 1');
    $st->execute([$id]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
function require_member(PDO $db): array
{
    $m = current_member($db);
    if (!$m) { header('Location: time_in.php'); exit; }
    return $m;
}
