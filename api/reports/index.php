<?php
declare(strict_types=1);

/**
 * Reports JSON API Endpoint
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../services/ReportService.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$userId = getAuthUserId();
$reportType = $_GET['type'] ?? 'comprehensive_monthly';
$period = $_GET['period'] ?? 'this_month';
$range = getDateRangeForPeriod($period, $_GET['start_date'] ?? null, $_GET['end_date'] ?? null);

$reportService = new ReportService();
$data = $reportService->generateReportData($userId, $reportType, $range['start'], $range['end'], $range['days']);

jsonResponse(['success' => true, 'range' => $range, 'report' => $data]);
