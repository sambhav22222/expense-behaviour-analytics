<?php
declare(strict_types=1);

/**
 * Expense Management Service
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

class ExpenseService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Add new expense record
     */
    public function createExpense(int $userId, array $data): array {
        $errors = $this->validateExpenseData($userId, $data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->db->prepare("
            INSERT INTO expenses (
                user_id, category_id, title, amount, transaction_date, 
                payment_method, merchant, description, is_recurring, recurring_expense_id, created_at
            ) VALUES (
                :uid, :cat_id, :title, :amount, :tx_date,
                :payment, :merchant, :description, :recurring, :recurring_id, NOW()
            )
        ");

        $stmt->execute([
            ':uid' => $userId,
            ':cat_id' => (int)$data['category_id'],
            ':title' => trim($data['title']),
            ':amount' => number_format((float)$data['amount'], 2, '.', ''),
            ':tx_date' => $data['transaction_date'],
            ':payment' => $data['payment_method'] ?? 'UPI',
            ':merchant' => !empty($data['merchant']) ? trim($data['merchant']) : null,
            ':description' => !empty($data['description']) ? trim($data['description']) : null,
            ':recurring' => !empty($data['is_recurring']) ? 1 : 0,
            ':recurring_id' => !empty($data['recurring_expense_id']) ? (int)$data['recurring_expense_id'] : null
        ]);

        $expenseId = (int)$this->db->lastInsertId();
        logAudit($userId, 'CREATE_EXPENSE', 'expenses', $expenseId, "Added expense: {$data['title']} for {$data['amount']}");

        return ['success' => true, 'id' => $expenseId];
    }

    /**
     * Get single expense by ID with ownership enforcement
     */
    public function getExpenseById(int $userId, int $expenseId): ?array {
        $stmt = $this->db->prepare("
            SELECT e.*, c.category_name, c.category_icon, c.category_color, c.is_essential
            FROM expenses e
            JOIN expense_categories c ON e.category_id = c.id
            WHERE e.id = :id AND e.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $expenseId, ':uid' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update an existing expense
     */
    public function updateExpense(int $userId, int $expenseId, array $data): array {
        // Ownership verification
        $existing = $this->getExpenseById($userId, $expenseId);
        if (!$existing) {
            return ['success' => false, 'errors' => ['Expense record not found or access denied.']];
        }

        $errors = $this->validateExpenseData($userId, $data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->db->prepare("
            UPDATE expenses SET 
                category_id = :cat_id,
                title = :title,
                amount = :amount,
                transaction_date = :tx_date,
                payment_method = :payment,
                merchant = :merchant,
                description = :description,
                is_recurring = :recurring,
                updated_at = NOW()
            WHERE id = :id AND user_id = :uid
        ");

        $stmt->execute([
            ':cat_id' => (int)$data['category_id'],
            ':title' => trim($data['title']),
            ':amount' => number_format((float)$data['amount'], 2, '.', ''),
            ':tx_date' => $data['transaction_date'],
            ':payment' => $data['payment_method'] ?? 'UPI',
            ':merchant' => !empty($data['merchant']) ? trim($data['merchant']) : null,
            ':description' => !empty($data['description']) ? trim($data['description']) : null,
            ':recurring' => !empty($data['is_recurring']) ? 1 : 0,
            ':id' => $expenseId,
            ':uid' => $userId
        ]);

        logAudit($userId, 'UPDATE_EXPENSE', 'expenses', $expenseId, "Updated expense ID: {$expenseId}");

        return ['success' => true];
    }

    /**
     * Delete an expense
     */
    public function deleteExpense(int $userId, int $expenseId): bool {
        $stmt = $this->db->prepare("DELETE FROM expenses WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $expenseId, ':uid' => $userId]);
        $deleted = $stmt->rowCount() > 0;
        if ($deleted) {
            logAudit($userId, 'DELETE_EXPENSE', 'expenses', $expenseId, "Deleted expense ID: {$expenseId}");
        }
        return $deleted;
    }

    /**
     * Fetch filtered and paginated expenses
     */
    public function getFilteredExpenses(int $userId, array $filters = [], int $page = 1, int $perPage = 15): array {
        $where = ['e.user_id = :uid'];
        $params = [':uid' => $userId];

        // Search Keyword
        if (!empty($filters['search'])) {
            $where[] = "(e.title LIKE :search OR e.merchant LIKE :search OR e.description LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $where[] = "e.category_id = :cat_id";
            $params[':cat_id'] = (int)$filters['category_id'];
        }

        // Date range
        if (!empty($filters['start_date'])) {
            $where[] = "e.transaction_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = "e.transaction_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        // Payment method
        if (!empty($filters['payment_method'])) {
            $where[] = "e.payment_method = :payment";
            $params[':payment'] = $filters['payment_method'];
        }

        // Amount range
        if (!empty($filters['min_amount']) && is_numeric($filters['min_amount'])) {
            $where[] = "e.amount >= :min_amount";
            $params[':min_amount'] = (float)$filters['min_amount'];
        }
        if (!empty($filters['max_amount']) && is_numeric($filters['max_amount'])) {
            $where[] = "e.amount <= :max_amount";
            $params[':max_amount'] = (float)$filters['max_amount'];
        }

        $whereClause = implode(' AND ', $where);

        // Count total rows
        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total_records, COALESCE(SUM(e.amount), 0) as total_amount
            FROM expenses e
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $summary = $countStmt->fetch();
        $totalRecords = (int)$summary['total_records'];
        $totalAmount = (float)$summary['total_amount'];

        // Sorting
        $allowedSortCols = [
            'date' => 'e.transaction_date',
            'amount' => 'e.amount',
            'title' => 'e.title',
            'category' => 'c.category_name'
        ];
        $sortCol = $allowedSortCols[$filters['sort'] ?? 'date'] ?? 'e.transaction_date';
        $sortDir = (isset($filters['order']) && strtoupper($filters['order']) === 'ASC') ? 'ASC' : 'DESC';

        // Pagination
        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "
            SELECT e.*, c.category_name, c.category_icon, c.category_color, c.is_essential
            FROM expenses e
            JOIN expense_categories c ON e.category_id = c.id
            WHERE {$whereClause}
            ORDER BY {$sortCol} {$sortDir}, e.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll();

        return [
            'records' => $records,
            'total_records' => $totalRecords,
            'total_amount' => $totalAmount,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages
        ];
    }

    /**
     * Get records for CSV export (no limit)
     */
    public function getExpensesForExport(int $userId, array $filters = []): array {
        $where = ['e.user_id = :uid'];
        $params = [':uid' => $userId];

        if (!empty($filters['start_date'])) {
            $where[] = "e.transaction_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = "e.transaction_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }
        if (!empty($filters['category_id'])) {
            $where[] = "e.category_id = :cat_id";
            $params[':cat_id'] = (int)$filters['category_id'];
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT e.id, e.transaction_date, e.title, e.amount, c.category_name, 
                   e.payment_method, e.merchant, e.description, e.is_recurring, e.created_at
            FROM expenses e
            JOIN expense_categories c ON e.category_id = c.id
            WHERE {$whereClause}
            ORDER BY e.transaction_date DESC, e.id DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Validate expense input fields
     */
    private function validateExpenseData(int $userId, array $data): array {
        $errors = [];

        if (empty($data['title']) || strlen(trim($data['title'])) < 2) {
            $errors[] = 'Title must be at least 2 characters.';
        }

        if (!isset($data['amount']) || !is_numeric($data['amount']) || (float)$data['amount'] <= 0) {
            $errors[] = 'Amount must be a valid positive number.';
        }

        if (empty($data['transaction_date'])) {
            $errors[] = 'Transaction date is required.';
        } else {
            $d = DateTime::createFromFormat('Y-m-d', $data['transaction_date']);
            if (!$d || $d->format('Y-m-d') !== $data['transaction_date']) {
                $errors[] = 'Transaction date must be in YYYY-MM-DD format.';
            }
        }

        if (empty($data['category_id'])) {
            $errors[] = 'Please select a spending category.';
        } else {
            // Verify category exists and is active for this user
            $catStmt = $this->db->prepare("
                SELECT id FROM expense_categories 
                WHERE id = :cid AND (user_id IS NULL OR user_id = :uid) AND status = 'active'
                LIMIT 1
            ");
            $catStmt->execute([':cid' => (int)$data['category_id'], ':uid' => $userId]);
            if (!$catStmt->fetch()) {
                $errors[] = 'Invalid expense category selected.';
            }
        }

        return $errors;
    }
}
