<?php
declare(strict_types=1);

/**
 * Expenses JSON API Endpoint
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../services/ExpenseService.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$userId = getAuthUserId();
$expenseService = new ExpenseService();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!empty($_GET['id'])) {
        $expense = $expenseService->getExpenseById($userId, (int)$_GET['id']);
        if (!$expense) {
            jsonResponse(['success' => false, 'error' => 'Expense not found'], 404);
        }
        jsonResponse(['success' => true, 'data' => $expense]);
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(5, min(100, (int)($_GET['per_page'] ?? 15)));
    $result = $expenseService->getFilteredExpenses($userId, $_GET, $page, $perPage);
    jsonResponse(['success' => true, 'data' => $result]);
}

jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
