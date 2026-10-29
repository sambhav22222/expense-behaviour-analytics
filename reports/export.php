<?php
declare(strict_types=1);

/**
 * CSV Export Streaming Endpoint
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/ExpenseService.php';
require_once __DIR__ . '/../services/IncomeService.php';
require_once __DIR__ . '/../services/ReportService.php';

requireAuth();

$userId = getAuthUserId();
$type = $_GET['type'] ?? 'expenses';

$filters = [
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'category_id' => $_GET['category_id'] ?? ''
];

$reportService = new ReportService();

if ($type === 'income') {
    $incomeService = new IncomeService();
    $result = $incomeService->getFilteredIncome($userId, $filters, 1, 10000);
    $filename = 'ebap_income_export_' . date('Ymd_His') . '.csv';
    $reportService->exportIncomeToCsv($userId, $result['records'], $filename);
} else {
    $expenseService = new ExpenseService();
    $records = $expenseService->getExpensesForExport($userId, $filters);
    $filename = 'ebap_expenses_export_' . date('Ymd_His') . '.csv';
    $reportService->exportExpensesToCsv($userId, $records, $filename);
}
