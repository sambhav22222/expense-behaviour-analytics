<?php
declare(strict_types=1);

/**
 * Delete Recurring Subscription
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
    $id = (int)($_POST['id'] ?? 0);

    $recService = new RecurringExpenseService();
    $deleted = $recService->deleteRecurring($userId, $id);

    if ($deleted) {
        setFlash('success', 'Subscription was successfully removed.');
    } else {
        setFlash('error', 'Unable to delete subscription or record does not exist.');
    }
}

header('Location: ' . url('recurring/index.php'));
exit;
