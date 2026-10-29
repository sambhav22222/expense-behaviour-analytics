<?php
declare(strict_types=1);

/**
 * Category Management Service
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

class CategoryService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Get expense categories available to user (System defaults + user created)
     */
    public function getExpenseCategories(int $userId, bool $activeOnly = true): array {
        $sql = "
            SELECT id, user_id, category_name, category_icon, category_color, is_essential, status, created_at,
                   (CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) AS is_system
            FROM expense_categories
            WHERE (user_id IS NULL OR user_id = :uid)
        ";
        if ($activeOnly) {
            $sql .= " AND status = 'active'";
        }
        $sql .= " ORDER BY is_system DESC, category_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get income categories available to user
     */
    public function getIncomeCategories(int $userId, bool $activeOnly = true): array {
        $sql = "
            SELECT id, user_id, category_name, category_icon, category_color, status, created_at,
                   (CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) AS is_system
            FROM income_categories
            WHERE (user_id IS NULL OR user_id = :uid)
        ";
        if ($activeOnly) {
            $sql .= " AND status = 'active'";
        }
        $sql .= " ORDER BY is_system DESC, category_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Create a custom expense category for user
     */
    public function createExpenseCategory(int $userId, string $name, string $icon, string $color, bool $isEssential): array {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'error' => 'Category name is required.'];
        }

        // Check duplicates for this user
        $checkStmt = $this->db->prepare("
            SELECT id FROM expense_categories 
            WHERE (user_id = :uid OR user_id IS NULL) AND LOWER(category_name) = LOWER(:name)
        ");
        $checkStmt->execute([':uid' => $userId, ':name' => $name]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'error' => 'A category with this name already exists.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO expense_categories (user_id, category_name, category_icon, category_color, is_essential, status)
            VALUES (:uid, :name, :icon, :color, :essential, 'active')
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':name' => $name,
            ':icon' => !empty($icon) ? $icon : 'bi-tag',
            ':color' => !empty($color) ? $color : '#4f46e5',
            ':essential' => $isEssential ? 1 : 0
        ]);

        return ['success' => true, 'id' => (int)$this->db->lastInsertId()];
    }

    /**
     * Create custom income category
     */
    public function createIncomeCategory(int $userId, string $name, string $icon, string $color): array {
        $name = trim($name);
        if (empty($name)) {
            return ['success' => false, 'error' => 'Category name is required.'];
        }

        $checkStmt = $this->db->prepare("
            SELECT id FROM income_categories 
            WHERE (user_id = :uid OR user_id IS NULL) AND LOWER(category_name) = LOWER(:name)
        ");
        $checkStmt->execute([':uid' => $userId, ':name' => $name]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'error' => 'An income category with this name already exists.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO income_categories (user_id, category_name, category_icon, category_color, status)
            VALUES (:uid, :name, :icon, :color, 'active')
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':name' => $name,
            ':icon' => !empty($icon) ? $icon : 'bi-wallet2',
            ':color' => !empty($color) ? $color : '#10b981'
        ]);

        return ['success' => true, 'id' => (int)$this->db->lastInsertId()];
    }
}
