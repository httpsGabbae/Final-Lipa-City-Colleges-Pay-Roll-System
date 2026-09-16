<?php
require_once __DIR__ . '/../config/config.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pesos($v): string { return '₱' . number_format((float)$v, 2); }

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">'; }
function require_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(419); exit('Invalid session token.');
    }
}

function shift_start_for(string $date): DateTimeImmutable { return new DateTimeImmutable($date . ' ' . BPO_SHIFT_START . ':00'); }
function shift_end_for(string $date): DateTimeImmutable { return new DateTimeImmutable($date . ' ' . BPO_SHIFT_END . ':00'); }
// Grace is intentionally hidden: callers must never print the cutoff.
function grace_cutoff_for(string $date): DateTimeImmutable
{
    return shift_start_for($date)->modify('+' . BPO_GRACE_MINUTES . ' minutes');
}
function attendance_status_for(string $date, string $timeIn): string
{
    return (new DateTimeImmutable($timeIn)) <= grace_cutoff_for($date) ? 'OnTime' : 'Late';
}

/**
 * Hourly engine. Pay starts at Time In (early clock-ins start at 8:00 AM),
 * stops at Time Out capped at 5:00 PM unless overtime is enabled per member.
 * Returns [regular_hours, overtime_hours, billable_start, billable_end].
 */
function billable_window(string $date, string $timeIn, ?string $timeOut, bool $overtimeEnabled, ?string $now = null): array
{
    $start = shift_start_for($date);
    $end = shift_end_for($date);
    $in = new DateTimeImmutable($timeIn);
    $out = $timeOut ? new DateTimeImmutable($timeOut) : ($now ? new DateTimeImmutable($now) : new DateTimeImmutable('now'));
    $billStart = $in > $start ? $in : $start;
    $regularEnd = $out < $end ? $out : $end;
    $regular = $regularEnd > $billStart ? ($regularEnd->getTimestamp() - $billStart->getTimestamp()) / 3600 : 0;
    $overtime = 0.0;
    if ($overtimeEnabled && $out > $end) {
        $otStart = $billStart > $end ? $billStart : $end;
        $overtime = $out > $otStart ? ($out->getTimestamp() - $otStart->getTimestamp()) / 3600 : 0;
    }
    return [max(0, $regular), max(0, $overtime), $billStart->format('Y-m-d H:i:s'), $out->format('Y-m-d H:i:s')];
}

function pay_for(float $regularHours, float $overtimeHours, float $rate, float $multiplier = BPO_OVERTIME_MULTIPLIER): float
{
    return round($regularHours * $rate + $overtimeHours * $rate * $multiplier, 2);
}
