<?php
declare(strict_types=1);

/**
 * 1-Click Action: Log Recurring Payment as Completed Expense
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/RecurringExpenseService.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $userId = getAuthUserId();
    $recurringId = (int)($_POST['recurring_id'] ?? 0);

    $recService = new RecurringExpenseService();
    $result = $recService->logPaymentAsExpense($userId, $recurringId);

    if ($result['success']) {
        setFlash('success', 'Subscription payment successfully logged as an expense! Next billing date updated to ' . formatDate($result['new_due_date']) . '.');
    } else {
        setFlash('error', $result['error'] ?? 'Failed to log subscription payment.');
    }
}

header('Location: ' . url('recurring/index.php'));
exit;
