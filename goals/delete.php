<?php
declare(strict_types=1);

/**
 * Delete Financial Goal
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

    $goalService = new GoalService();
    $deleted = $goalService->deleteGoal($userId, $goalId);

    if ($deleted) {
        setFlash('success', 'Financial goal was successfully removed.');
    } else {
        setFlash('error', 'Unable to delete goal or record not found.');
    }
}

header('Location: ' . url('goals/index.php'));
exit;
