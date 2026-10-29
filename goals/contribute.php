<?php
declare(strict_types=1);

/**
 * Add Funds Contribution to Financial Goal
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/GoalService.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $userId = getAuthUserId();
    $goalId = (int)($_POST['goal_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $date = $_POST['contribution_date'] ?? date('Y-m-d');
    $desc = trim($_POST['description'] ?? '');

    $goalService = new GoalService();
    $result = $goalService->addContribution($userId, $goalId, $amount, $date, $desc);

    if ($result['success']) {
        if ($result['status'] === 'achieved') {
            setFlash('success', 'Congratulations! You have reached your financial target for this goal!');
        } else {
            setFlash('success', 'Contribution of ' . formatCurrency($amount) . ' added to goal successfully.');
        }
    } else {
        setFlash('error', $result['error'] ?? 'Failed to record goal contribution.');
    }
}

header('Location: ' . url('goals/index.php'));
exit;
