<?php
declare(strict_types=1);

/**
 * Income Management Service
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

class IncomeService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Add new income record
     */
    public function createIncome(int $userId, array $data): array {
        $errors = $this->validateIncomeData($userId, $data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->db->prepare("
            INSERT INTO income_records (
                user_id, category_id, title, amount, received_date, 
                payment_method, payer, description, is_recurring, created_at
            ) VALUES (
                :uid, :cat_id, :title, :amount, :rx_date,
                :payment, :payer, :description, :recurring, NOW()
            )
        ");

        $stmt->execute([
            ':uid' => $userId,
            ':cat_id' => (int)$data['category_id'],
            ':title' => trim($data['title']),
            ':amount' => number_format((float)$data['amount'], 2, '.', ''),
            ':rx_date' => $data['received_date'],
            ':payment' => $data['payment_method'] ?? 'Bank Transfer',
            ':payer' => !empty($data['payer']) ? trim($data['payer']) : null,
            ':description' => !empty($data['description']) ? trim($data['description']) : null,
            ':recurring' => !empty($data['is_recurring']) ? 1 : 0
        ]);

        $incomeId = (int)$this->db->lastInsertId();
        logAudit($userId, 'CREATE_INCOME', 'income_records', $incomeId, "Added income: {$data['title']} of {$data['amount']}");

        return ['success' => true, 'id' => $incomeId];
    }

    /**
     * Get single income by ID with ownership enforcement
     */
    public function getIncomeById(int $userId, int $incomeId): ?array {
        $stmt = $this->db->prepare("
            SELECT r.*, c.category_name, c.category_icon, c.category_color
            FROM income_records r
            JOIN income_categories c ON r.category_id = c.id
            WHERE r.id = :id AND r.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $incomeId, ':uid' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update income record
     */
    public function updateIncome(int $userId, int $incomeId, array $data): array {
        $existing = $this->getIncomeById($userId, $incomeId);
        if (!$existing) {
            return ['success' => false, 'errors' => ['Income record not found or access denied.']];
        }

        $errors = $this->validateIncomeData($userId, $data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->db->prepare("
            UPDATE income_records SET
                category_id = :cat_id,
                title = :title,
                amount = :amount,
                received_date = :rx_date,
                payment_method = :payment,
                payer = :payer,
                description = :description,
                is_recurring = :recurring,
                updated_at = NOW()
            WHERE id = :id AND user_id = :uid
        ");

        $stmt->execute([
            ':cat_id' => (int)$data['category_id'],
            ':title' => trim($data['title']),
            ':amount' => number_format((float)$data['amount'], 2, '.', ''),
            ':rx_date' => $data['received_date'],
            ':payment' => $data['payment_method'] ?? 'Bank Transfer',
            ':payer' => !empty($data['payer']) ? trim($data['payer']) : null,
            ':description' => !empty($data['description']) ? trim($data['description']) : null,
            ':recurring' => !empty($data['is_recurring']) ? 1 : 0,
            ':id' => $incomeId,
            ':uid' => $userId
        ]);

        logAudit($userId, 'UPDATE_INCOME', 'income_records', $incomeId, "Updated income ID: {$incomeId}");

        return ['success' => true];
    }

    /**
     * Delete income record
     */
    public function deleteIncome(int $userId, int $incomeId): bool {
        $stmt = $this->db->prepare("DELETE FROM income_records WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $incomeId, ':uid' => $userId]);
        $deleted = $stmt->rowCount() > 0;
        if ($deleted) {
            logAudit($userId, 'DELETE_INCOME', 'income_records', $incomeId, "Deleted income ID: {$incomeId}");
        }
        return $deleted;
    }

    /**
     * Get filtered income list with pagination
     */
    public function getFilteredIncome(int $userId, array $filters = [], int $page = 1, int $perPage = 15): array {
        $where = ['r.user_id = :uid'];
        $params = [':uid' => $userId];

        if (!empty($filters['search'])) {
            $where[] = "(r.title LIKE :search OR r.payer LIKE :search OR r.description LIKE :search)";
            $params[':search'] = '%' . trim($filters['search']) . '%';
        }

        if (!empty($filters['category_id'])) {
            $where[] = "r.category_id = :cat_id";
            $params[':cat_id'] = (int)$filters['category_id'];
        }

        if (!empty($filters['start_date'])) {
            $where[] = "r.received_date >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $where[] = "r.received_date <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        $whereClause = implode(' AND ', $where);

        $countStmt = $this->db->prepare("
            SELECT COUNT(*) as total_records, COALESCE(SUM(r.amount), 0) as total_amount
            FROM income_records r
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $summary = $countStmt->fetch();
        $totalRecords = (int)$summary['total_records'];
        $totalAmount = (float)$summary['total_amount'];

        $page = max(1, $page);
        $perPage = max(5, min(100, $perPage));
        $offset = ($page - 1) * $perPage;
        $totalPages = (int)ceil($totalRecords / $perPage);

        $sql = "
            SELECT r.*, c.category_name, c.category_icon, c.category_color
            FROM income_records r
            JOIN income_categories c ON r.category_id = c.id
            WHERE {$whereClause}
            ORDER BY r.received_date DESC, r.id DESC
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
     * Validate income input
     */
    private function validateIncomeData(int $userId, array $data): array {
        $errors = [];

        if (empty($data['title']) || strlen(trim($data['title'])) < 2) {
            $errors[] = 'Title must be at least 2 characters.';
        }

        if (!isset($data['amount']) || !is_numeric($data['amount']) || (float)$data['amount'] <= 0) {
            $errors[] = 'Amount must be a positive number.';
        }

        if (empty($data['received_date'])) {
            $errors[] = 'Received date is required.';
        } else {
            $d = DateTime::createFromFormat('Y-m-d', $data['received_date']);
            if (!$d || $d->format('Y-m-d') !== $data['received_date']) {
                $errors[] = 'Date must be in YYYY-MM-DD format.';
            }
        }

        if (empty($data['category_id'])) {
            $errors[] = 'Please select an income source category.';
        } else {
            $catStmt = $this->db->prepare("
                SELECT id FROM income_categories 
                WHERE id = :cid AND (user_id IS NULL OR user_id = :uid) AND status = 'active'
                LIMIT 1
            ");
            $catStmt->execute([':cid' => (int)$data['category_id'], ':uid' => $userId]);
            if (!$catStmt->fetch()) {
                $errors[] = 'Invalid income category selected.';
            }
        }

        return $errors;
    }
}
