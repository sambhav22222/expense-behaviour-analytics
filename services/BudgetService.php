<?php
declare(strict_types=1);

/**
 * Budget Management & Utilization Service
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

class BudgetService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Create a new budget
     */
    public function createBudget(int $userId, array $data): array {
        $errors = $this->validateBudgetData($userId, $data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->db->prepare("
            INSERT INTO budgets (
                user_id, category_id, budget_name, budget_amount, 
                period_type, start_date, end_date, warning_threshold, status, created_at
            ) VALUES (
                :uid, :cat_id, :name, :amount,
                :period, :start_date, :end_date, :threshold, 'active', NOW()
            )
        ");

        $stmt->execute([
            ':uid' => $userId,
            ':cat_id' => !empty($data['category_id']) ? (int)$data['category_id'] : null,
            ':name' => trim($data['budget_name']),
            ':amount' => number_format((float)$data['budget_amount'], 2, '.', ''),
            ':period' => $data['period_type'] ?? 'monthly',
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':threshold' => !empty($data['warning_threshold']) ? (int)$data['warning_threshold'] : 80
        ]);

        $budgetId = (int)$this->db->lastInsertId();
        logAudit($userId, 'CREATE_BUDGET', 'budgets', $budgetId, "Created budget: {$data['budget_name']}");

        return ['success' => true, 'id' => $budgetId];
    }

    /**
     * Get single budget with ownership check
     */
    public function getBudgetById(int $userId, int $budgetId): ?array {
        $stmt = $this->db->prepare("
            SELECT b.*, c.category_name, c.category_icon, c.category_color
            FROM budgets b
            LEFT JOIN expense_categories c ON b.category_id = c.id
            WHERE b.id = :id AND b.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $budgetId, ':uid' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update budget
     */
    public function updateBudget(int $userId, int $budgetId, array $data): array {
        $existing = $this->getBudgetById($userId, $budgetId);
        if (!$existing) {
            return ['success' => false, 'errors' => ['Budget not found or access denied.']];
        }

        $errors = $this->validateBudgetData($userId, $data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->db->prepare("
            UPDATE budgets SET
                category_id = :cat_id,
                budget_name = :name,
                budget_amount = :amount,
                period_type = :period,
                start_date = :start_date,
                end_date = :end_date,
                warning_threshold = :threshold,
                status = :status,
                updated_at = NOW()
            WHERE id = :id AND user_id = :uid
        ");

        $stmt->execute([
            ':cat_id' => !empty($data['category_id']) ? (int)$data['category_id'] : null,
            ':name' => trim($data['budget_name']),
            ':amount' => number_format((float)$data['budget_amount'], 2, '.', ''),
            ':period' => $data['period_type'] ?? 'monthly',
            ':start_date' => $data['start_date'],
            ':end_date' => $data['end_date'],
            ':threshold' => !empty($data['warning_threshold']) ? (int)$data['warning_threshold'] : 80,
            ':status' => $data['status'] ?? 'active',
            ':id' => $budgetId,
            ':uid' => $userId
        ]);

        logAudit($userId, 'UPDATE_BUDGET', 'budgets', $budgetId, "Updated budget ID: {$budgetId}");

        return ['success' => true];
    }

    /**
     * Delete budget
     */
    public function deleteBudget(int $userId, int $budgetId): bool {
        $stmt = $this->db->prepare("DELETE FROM budgets WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $budgetId, ':uid' => $userId]);
        $deleted = $stmt->rowCount() > 0;
        if ($deleted) {
            logAudit($userId, 'DELETE_BUDGET', 'budgets', $budgetId, "Deleted budget ID: {$budgetId}");
        }
        return $deleted;
    }

    /**
     * Get active budgets with real-time actual spending and utilization percentages
     */
    public function getActiveBudgetsWithUtilization(int $userId, ?string $asOfDate = null): array {
        $date = $asOfDate ?? date('Y-m-d');

        $stmt = $this->db->prepare("
            SELECT b.*, c.category_name, c.category_icon, c.category_color
            FROM budgets b
            LEFT JOIN expense_categories c ON b.category_id = c.id
            WHERE b.user_id = :uid 
              AND b.status = 'active'
              AND :asof BETWEEN b.start_date AND b.end_date
            ORDER BY (b.category_id IS NULL) DESC, b.budget_amount DESC
        ");
        $stmt->execute([':uid' => $userId, ':asof' => $date]);
        $budgets = $stmt->fetchAll();

        foreach ($budgets as &$b) {
            $catId = $b['category_id'];
            $startDate = $b['start_date'];
            $endDate = $b['end_date'];

            // Query actual spending in this date range
            if ($catId === null) {
                // Overall budget: all expenses in this period
                $expStmt = $this->db->prepare("
                    SELECT COALESCE(SUM(amount), 0) as spent, COUNT(*) as tx_count
                    FROM expenses
                    WHERE user_id = :uid AND transaction_date BETWEEN :start AND :end
                ");
                $expStmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
            } else {
                // Category-specific budget
                $expStmt = $this->db->prepare("
                    SELECT COALESCE(SUM(amount), 0) as spent, COUNT(*) as tx_count
                    FROM expenses
                    WHERE user_id = :uid AND category_id = :cid AND transaction_date BETWEEN :start AND :end
                ");
                $expStmt->execute([':uid' => $userId, ':cid' => $catId, ':start' => $startDate, ':end' => $endDate]);
            }

            $expRow = $expStmt->fetch();
            $spent = (float)($expRow['spent'] ?? 0);
            $txCount = (int)($expRow['tx_count'] ?? 0);
            $limit = (float)$b['budget_amount'];

            // Calculations
            $remaining = $limit - $spent;
            $utilization = ($limit > 0) ? round(($spent / $limit) * 100, 1) : 0.0;
            $threshold = (int)$b['warning_threshold'];

            // Status Badge Logic
            if ($utilization >= 100) {
                $statusLevel = 'danger'; // Exceeded budget
                $statusMessage = 'Exceeded by ' . formatCurrency(abs($remaining), getAuthCurrency());
            } elseif ($utilization >= $threshold) {
                $statusLevel = 'warning'; // Near threshold
                $statusMessage = 'Approaching limit (' . $utilization . '% used)';
            } else {
                $statusLevel = 'success'; // Safe
                $statusMessage = formatCurrency($remaining, getAuthCurrency()) . ' remaining';
            }

            $b['actual_spent'] = $spent;
            $b['transaction_count'] = $txCount;
            $b['remaining_amount'] = $remaining;
            $b['utilization_pct'] = $utilization;
            $b['status_level'] = $statusLevel;
            $b['status_message'] = $statusMessage;
        }

        return $budgets;
    }

    /**
     * Validate budget inputs
     */
    private function validateBudgetData(int $userId, array $data): array {
        $errors = [];

        if (empty($data['budget_name']) || strlen(trim($data['budget_name'])) < 2) {
            $errors[] = 'Budget name must be at least 2 characters.';
        }

        if (!isset($data['budget_amount']) || !is_numeric($data['budget_amount']) || (float)$data['budget_amount'] <= 0) {
            $errors[] = 'Budget limit must be a positive number.';
        }

        if (empty($data['start_date']) || empty($data['end_date'])) {
            $errors[] = 'Start date and end date are both required.';
        } elseif ($data['start_date'] > $data['end_date']) {
            $errors[] = 'Start date cannot be after end date.';
        }

        if (!empty($data['category_id'])) {
            $catStmt = $this->db->prepare("
                SELECT id FROM expense_categories 
                WHERE id = :cid AND (user_id IS NULL OR user_id = :uid) AND status = 'active'
                LIMIT 1
            ");
            $catStmt->execute([':cid' => (int)$data['category_id'], ':uid' => $userId]);
            if (!$catStmt->fetch()) {
                $errors[] = 'Invalid category selected for this budget.';
            }
        }

        return $errors;
    }
}
