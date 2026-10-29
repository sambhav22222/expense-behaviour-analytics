<?php
declare(strict_types=1);

/**
 * Income JSON API Endpoint
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../services/IncomeService.php';

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
}

$userId = getAuthUserId();
$incomeService = new IncomeService();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(5, min(100, (int)($_GET['per_page'] ?? 15)));
    $result = $incomeService->getFilteredIncome($userId, $_GET, $page, $perPage);
    jsonResponse(['success' => true, 'data' => $result]);
}

jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
