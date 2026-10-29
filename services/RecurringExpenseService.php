<?php
declare(strict_types=1);

/**
 * Recurring Expenses & Subscription Management Service
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

class RecurringExpenseService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Create a recurring expense
     */
    public function createRecurring(int $userId, array $data): array {
        $errors = $this->validateRecurringData($userId, $data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->db->prepare("
            INSERT INTO recurring_expenses (
                user_id, category_id, title, amount, frequency,
                start_date, next_due_date, end_date, payment_method, notes, status, created_at
            ) VALUES (
                :uid, :cat_id, :title, :amount, :freq,
                :start_date, :next_due, :end_date, :payment, :notes, 'active', NOW()
            )
        ");

        $stmt->execute([
            ':uid' => $userId,
            ':cat_id' => (int)$data['category_id'],
            ':title' => trim($data['title']),
            ':amount' => number_format((float)$data['amount'], 2, '.', ''),
            ':freq' => $data['frequency'] ?? 'monthly',
            ':start_date' => $data['start_date'],
            ':next_due' => $data['next_due_date'] ?? $data['start_date'],
            ':end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
            ':payment' => $data['payment_method'] ?? 'UPI',
            ':notes' => !empty($data['notes']) ? trim($data['notes']) : null
        ]);

        $recId = (int)$this->db->lastInsertId();
        logAudit($userId, 'CREATE_RECURRING', 'recurring_expenses', $recId, "Added recurring subscription: {$data['title']}");

        return ['success' => true, 'id' => $recId];
    }

    /**
     * Get single recurring item with ownership verification
     */
    public function getRecurringById(int $userId, int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT r.*, c.category_name, c.category_icon, c.category_color
            FROM recurring_expenses r
            JOIN expense_categories c ON r.category_id = c.id
            WHERE r.id = :id AND r.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Update recurring expense
     */
    public function updateRecurring(int $userId, int $id, array $data): array {
        $existing = $this->getRecurringById($userId, $id);
        if (!$existing) {
            return ['success' => false, 'errors' => ['Subscription not found or access denied.']];
        }

        $errors = $this->validateRecurringData($userId, $data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->db->prepare("
            UPDATE recurring_expenses SET
                category_id = :cat_id,
                title = :title,
                amount = :amount,
                frequency = :freq,
                start_date = :start_date,
                next_due_date = :next_due,
                end_date = :end_date,
                payment_method = :payment,
                notes = :notes,
                status = :status,
                updated_at = NOW()
            WHERE id = :id AND user_id = :uid
        ");

        $stmt->execute([
            ':cat_id' => (int)$data['category_id'],
            ':title' => trim($data['title']),
            ':amount' => number_format((float)$data['amount'], 2, '.', ''),
            ':freq' => $data['frequency'] ?? 'monthly',
            ':start_date' => $data['start_date'],
            ':next_due' => $data['next_due_date'],
            ':end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
            ':payment' => $data['payment_method'] ?? 'UPI',
            ':notes' => !empty($data['notes']) ? trim($data['notes']) : null,
            ':status' => $data['status'] ?? 'active',
            ':id' => $id,
            ':uid' => $userId
        ]);

        logAudit($userId, 'UPDATE_RECURRING', 'recurring_expenses', $id, "Updated recurring subscription ID: {$id}");

        return ['success' => true];
    }

    /**
     * Delete recurring expense
     */
    public function deleteRecurring(int $userId, int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM recurring_expenses WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $deleted = $stmt->rowCount() > 0;
        if ($deleted) {
            logAudit($userId, 'DELETE_RECURRING', 'recurring_expenses', $id, "Deleted recurring subscription ID: {$id}");
        }
        return $deleted;
    }

    /**
     * Get all recurring items for user with normalized cost breakdown
     */
    public function getAllRecurring(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT r.*, c.category_name, c.category_icon, c.category_color
            FROM recurring_expenses r
            JOIN expense_categories c ON r.category_id = c.id
            WHERE r.user_id = :uid
            ORDER BY r.status ASC, r.next_due_date ASC
        ");
        $stmt->execute([':uid' => $userId]);
        $items = $stmt->fetchAll();

        $totalMonthlyNormalized = 0.0;
        $totalAnnualNormalized = 0.0;

        foreach ($items as &$item) {
            $amt = (float)$item['amount'];
            $freq = $item['frequency'];

            // Normalization calculations
            switch ($freq) {
                case 'weekly':
                    $monthly = $amt * (52 / 12);
                    $annual = $amt * 52;
                    break;
                case 'quarterly':
                    $monthly = $amt / 3;
                    $annual = $amt * 4;
                    break;
                case 'yearly':
                    $monthly = $amt / 12;
                    $annual = $amt;
                    break;
                case 'monthly':
                default:
                    $monthly = $amt;
                    $annual = $amt * 12;
                    break;
            }

            $item['monthly_normalized'] = round($monthly, 2);
            $item['annual_normalized'] = round($annual, 2);

            if ($item['status'] === 'active') {
                $totalMonthlyNormalized += $monthly;
                $totalAnnualNormalized += $annual;
            }
        }

        return [
            'items' => $items,
            'total_monthly' => round($totalMonthlyNormalized, 2),
            'total_annual' => round($totalAnnualNormalized, 2),
            'active_count' => count(array_filter($items, fn($i) => $i['status'] === 'active'))
        ];
    }

    /**
     * Log a recurring bill payment as a completed expense in 1 click and roll forward next_due_date
     */
    public function logPaymentAsExpense(int $userId, int $recurringId, ?string $paymentDate = null): array {
        $item = $this->getRecurringById($userId, $recurringId);
        if (!$item) {
            return ['success' => false, 'error' => 'Recurring item not found.'];
        }

        $date = $paymentDate ?? date('Y-m-d');

        try {
            $this->db->beginTransaction();

            // 1. Insert into expenses
            $stmt = $this->db->prepare("
                INSERT INTO expenses (
                    user_id, category_id, title, amount, transaction_date,
                    payment_method, merchant, description, is_recurring, recurring_expense_id, created_at
                ) VALUES (
                    :uid, :cat_id, :title, :amount, :tx_date,
                    :payment, :merchant, :desc, 1, :rec_id, NOW()
                )
            ");
            $stmt->execute([
                ':uid' => $userId,
                ':cat_id' => $item['category_id'],
                ':title' => $item['title'] . ' (Auto Recurring)',
                ':amount' => $item['amount'],
                ':tx_date' => $date,
                ':payment' => $item['payment_method'],
                ':merchant' => $item['title'],
                ':desc' => 'Logged from active recurring subscription #' . $item['id'],
                ':rec_id' => $item['id']
            ]);

            // 2. Compute next due date
            $nextDue = new DateTime($item['next_due_date']);
            switch ($item['frequency']) {
                case 'weekly':
                    $nextDue->modify('+1 week');
                    break;
                case 'quarterly':
                    $nextDue->modify('+3 months');
                    break;
                case 'yearly':
                    $nextDue->modify('+1 year');
                    break;
                case 'monthly':
                default:
                    $nextDue->modify('+1 month');
                    break;
            }

            $updateStmt = $this->db->prepare("
                UPDATE recurring_expenses 
                SET next_due_date = :next_due, updated_at = NOW()
                WHERE id = :id AND user_id = :uid
            ");
            $updateStmt->execute([
                ':next_due' => $nextDue->format('Y-m-d'),
                ':id' => $recurringId,
                ':uid' => $userId
            ]);

            $this->db->commit();
            logAudit($userId, 'LOG_RECURRING_EXPENSE', 'recurring_expenses', $recurringId, "Recorded expense payment for {$item['title']}");

            return ['success' => true, 'new_due_date' => $nextDue->format('Y-m-d')];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Failed to log recurring payment: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error while logging payment.'];
        }
    }

    /**
     * Validate recurring inputs
     */
    private function validateRecurringData(int $userId, array $data): array {
        $errors = [];

        if (empty($data['title']) || strlen(trim($data['title'])) < 2) {
            $errors[] = 'Title must be at least 2 characters.';
        }

        if (!isset($data['amount']) || !is_numeric($data['amount']) || (float)$data['amount'] <= 0) {
            $errors[] = 'Amount must be a positive number.';
        }

        if (empty($data['start_date'])) {
            $errors[] = 'Start date is required.';
        }

        if (empty($data['category_id'])) {
            $errors[] = 'Category is required.';
        }

        $validFrequencies = ['weekly', 'monthly', 'quarterly', 'yearly'];
        if (!in_array($data['frequency'] ?? '', $validFrequencies, true)) {
            $errors[] = 'Invalid billing frequency.';
        }

        return $errors;
    }
}
