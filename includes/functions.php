<?php
declare(strict_types=1);

/**
 * Common Helper Functions & Utility Library
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';

/**
 * Safe HTML escaping
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate full application URL
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return APP_URL . ($path ? '/' . $path : '');
}

/**
 * Flash messaging system
 */
function setFlash(string $type, string $message): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash'][$type][] = $message;
}

function getFlashes(): array {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Format date string safely
 */
function formatDate(?string $date, string $format = 'd M Y'): string {
    if (empty($date)) {
        return 'N/A';
    }
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return htmlspecialchars($date);
    }
}

/**
 * Sanitize cell values for CSV export to protect against spreadsheet formula injection (CSV Injection / Formula Injection)
 * Prevents execution of malicious formulas starting with =, +, -, @, tab, newline
 */
function sanitizeCsvCell(mixed $value): string {
    if ($value === null) {
        return '';
    }
    $str = (string)$value;
    $firstChar = substr(ltrim($str), 0, 1);
    if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $str;
    }
    return $str;
}

/**
 * Standardized JSON response helper
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Calculate Date Ranges for Analytical Periods
 */
function getDateRangeForPeriod(string $period, ?string $customStart = null, ?string $customEnd = null): array {
    $today = new DateTime('now');
    
    switch ($period) {
        case 'this_week':
            // Monday of this week to Sunday
            $start = (clone $today)->modify('monday this week');
            $end = (clone $today)->modify('sunday this week');
            $label = 'This Week (' . $start->format('d M') . ' - ' . $end->format('d M Y') . ')';
            break;

        case 'previous_month':
            $start = (clone $today)->modify('first day of previous month');
            $end = (clone $today)->modify('last day of previous month');
            $label = 'Previous Month (' . $start->format('F Y') . ')';
            break;

        case 'last_3_months':
            $start = (clone $today)->modify('-3 months')->modify('first day of this month');
            $end = (clone $today)->modify('last day of this month');
            $label = 'Last 3 Months (' . $start->format('M Y') . ' - ' . $end->format('M Y') . ')';
            break;

        case 'this_year':
            $start = new DateTime($today->format('Y-01-01'));
            $end = new DateTime($today->format('Y-12-31'));
            $label = 'This Year (' . $today->format('Y') . ')';
            break;

        case 'custom':
            if ($customStart && $customEnd) {
                try {
                    $start = new DateTime($customStart);
                    $end = new DateTime($customEnd);
                    if ($start > $end) {
                        $temp = $start;
                        $start = $end;
                        $end = $temp;
                    }
                    $label = 'Custom (' . $start->format('d M Y') . ' - ' . $end->format('d M Y') . ')';
                    break;
                } catch (Exception $e) {
                    // Fall through to default
                }
            }
            // If custom missing, fallback to this month
            // no break
        case 'this_month':
        default:
            $start = (clone $today)->modify('first day of this month');
            $end = (clone $today)->modify('last day of this month');
            $label = 'This Month (' . $start->format('F Y') . ')';
            $period = 'this_month';
            break;
    }

    $days = (int)$start->diff($end)->format('%a') + 1;

    return [
        'period' => $period,
        'start'  => $start->format('Y-m-d'),
        'end'    => $end->format('Y-m-d'),
        'days'   => max(1, $days),
        'label'  => $label
    ];
}

/**
 * Calculate the preceding equivalent date range for comparisons
 */
function getPrecedingEquivalentRange(string $startDate, string $endDate): array {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $days = (int)$start->diff($end)->format('%a') + 1;

    $prevEnd = (clone $start)->modify('-1 day');
    $prevStart = (clone $prevEnd)->modify('-' . ($days - 1) . ' days');

    return [
        'start' => $prevStart->format('Y-m-d'),
        'end'   => $prevEnd->format('Y-m-d'),
        'days'  => $days,
        'label' => 'Preceding Period (' . $prevStart->format('d M Y') . ' - ' . $prevEnd->format('d M Y') . ')'
    ];
}
