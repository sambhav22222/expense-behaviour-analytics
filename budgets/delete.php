<?php
declare(strict_types=1);

/**
 * Delete Budget Action Handler
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/BudgetService.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $userId = getAuthUserId();
    $budgetId = (int)($_POST['budget_id'] ?? 0);

    $budgetService = new BudgetService();
    $deleted = $budgetService->deleteBudget($userId, $budgetId);

    if ($deleted) {
        setFlash('success', 'Budget was successfully deleted.');
    } else {
        setFlash('error', 'Unable to delete budget or record not found.');
    }
}

header('Location: ' . url('budgets/index.php'));
exit;
