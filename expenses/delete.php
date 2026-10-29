<?php
declare(strict_types=1);

/**
 * Delete Expense Action Handler
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/ExpenseService.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $userId = getAuthUserId();
    $expenseId = (int)($_POST['expense_id'] ?? 0);

    $expenseService = new ExpenseService();
    $deleted = $expenseService->deleteExpense($userId, $expenseId);

    if ($deleted) {
        setFlash('success', 'Expense transaction was successfully deleted.');
    } else {
        setFlash('error', 'Unable to delete expense record or record does not exist.');
    }
}

header('Location: ' . url('expenses/index.php'));
exit;
