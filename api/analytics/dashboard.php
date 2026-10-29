<?php
declare(strict_types=1);

/**
 * Analytics Dashboard JSON API Endpoint
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../services/AnalyticsService.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$userId = getAuthUserId();
$period = $_GET['period'] ?? 'this_month';
$customStart = $_GET['start_date'] ?? null;
$customEnd = $_GET['end_date'] ?? null;

$range = getDateRangeForPeriod($period, $customStart, $customEnd);
$analytics = new AnalyticsService();

$summary = $analytics->getSummaryMetrics($userId, $range['start'], $range['end'], $range['days']);
$categories = $analytics->getCategoryBreakdown($userId, $range['start'], $range['end']);
$dailyTrend = $analytics->getDailyTrend($userId, $range['start'], $range['end']);
$essentialVsDisc = $analytics->getEssentialVsDiscretionary($userId, $range['start'], $range['end']);

jsonResponse([
    'success' => true,
    'range' => $range,
    'summary' => $summary,
    'categories' => $categories,
    'daily_trend' => $dailyTrend,
    'essential_vs_discretionary' => $essentialVsDisc
]);
