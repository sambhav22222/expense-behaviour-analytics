<?php
declare(strict_types=1);

/**
 * Budgets JSON API Endpoint
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../services/BudgetService.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$userId = getAuthUserId();
$budgetService = new BudgetService();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = $_GET['as_of'] ?? date('Y-m-d');
    $budgets = $budgetService->getActiveBudgetsWithUtilization($userId, $date);
    jsonResponse(['success' => true, 'data' => $budgets]);
}

jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
