<?php
declare(strict_types=1);

/**
 * Delete Income Action Handler
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/IncomeService.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $userId = getAuthUserId();
    $incomeId = (int)($_POST['income_id'] ?? 0);

    $incomeService = new IncomeService();
    $deleted = $incomeService->deleteIncome($userId, $incomeId);

    if ($deleted) {
        setFlash('success', 'Income record deleted successfully.');
    } else {
        setFlash('error', 'Unable to delete income record or record does not exist.');
    }
}

header('Location: ' . url('income/index.php'));
exit;
