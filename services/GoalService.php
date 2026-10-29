<?php
declare(strict_types=1);

/**
 * Financial Goal Management Service
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

class GoalService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Create new financial goal
     */
    public function createGoal(int $userId, array $data): array {
        $errors = $this->validateGoalData($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->db->prepare("
            INSERT INTO financial_goals (
                user_id, goal_name, goal_type, category_id, target_amount,
                baseline_amount, current_progress, start_date, target_date, status, description, created_at
            ) VALUES (
                :uid, :name, :type, :cat_id, :target,
                :baseline, :progress, :start_date, :target_date, 'in_progress', :desc, NOW()
            )
        ");

        $stmt->execute([
            ':uid' => $userId,
            ':name' => trim($data['goal_name']),
            ':type' => $data['goal_type'] ?? 'savings',
            ':cat_id' => !empty($data['category_id']) ? (int)$data['category_id'] : null,
            ':target' => number_format((float)$data['target_amount'], 2, '.', ''),
            ':baseline' => !empty($data['baseline_amount']) ? number_format((float)$data['baseline_amount'], 2, '.', '') : '0.00',
            ':progress' => !empty($data['current_progress']) ? number_format((float)$data['current_progress'], 2, '.', '') : '0.00',
            ':start_date' => $data['start_date'],
            ':target_date' => $data['target_date'],
            ':desc' => !empty($data['description']) ? trim($data['description']) : null
        ]);

        $goalId = (int)$this->db->lastInsertId();
        logAudit($userId, 'CREATE_GOAL', 'financial_goals', $goalId, "Created goal: {$data['goal_name']}");

        return ['success' => true, 'id' => $goalId];
    }

    /**
     * Get all goals for user
     */
    public function getGoals(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT g.*, c.category_name, c.category_icon, c.category_color
            FROM financial_goals g
            LEFT JOIN expense_categories c ON g.category_id = c.id
            WHERE g.user_id = :uid
            ORDER BY (g.status = 'in_progress') DESC, g.target_date ASC
        ");
        $stmt->execute([':uid' => $userId]);
        $goals = $stmt->fetchAll();

        $today = new DateTime('now');

        foreach ($goals as &$g) {
            $target = (float)$g['target_amount'];
            $progress = (float)$g['current_progress'];
            $pct = ($target > 0) ? min(100.0, round(($progress / $target) * 100, 1)) : 0.0;
            $g['progress_pct'] = $pct;

            // Target date calculations
            $targetDate = new DateTime($g['target_date']);
            $diff = $today->diff($targetDate);
            $daysLeft = (int)$diff->format('%r%a');
            $g['days_remaining'] = $daysLeft;
            $g['is_overdue'] = ($daysLeft < 0 && $g['status'] === 'in_progress');
        }

        return $goals;
    }

    /**
     * Get single goal by ID
     */
    public function getGoalById(int $userId, int $goalId): ?array {
        $stmt = $this->db->prepare("
            SELECT g.*, c.category_name, c.category_icon, c.category_color
            FROM financial_goals g
            LEFT JOIN expense_categories c ON g.category_id = c.id
            WHERE g.id = :id AND g.user_id = :uid
            LIMIT 1
        ");
        $stmt->execute([':id' => $goalId, ':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Add contribution to a savings goal
     */
    public function addContribution(int $userId, int $goalId, float $amount, string $date, ?string $description = null): array {
        $goal = $this->getGoalById($userId, $goalId);
        if (!$goal) {
            return ['success' => false, 'error' => 'Goal not found or access denied.'];
        }

        if ($amount <= 0) {
            return ['success' => false, 'error' => 'Contribution amount must be greater than zero.'];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("
                INSERT INTO goal_contributions (goal_id, user_id, amount, contribution_date, description, created_at)
                VALUES (:gid, :uid, :amt, :date, :desc, NOW())
            ");
            $stmt->execute([
                ':gid' => $goalId,
                ':uid' => $userId,
                ':amt' => $amount,
                ':date' => $date,
                ':desc' => $description
            ]);

            // Update goal current progress
            $newProgress = (float)$goal['current_progress'] + $amount;
            $target = (float)$goal['target_amount'];
            $newStatus = ($newProgress >= $target) ? 'achieved' : $goal['status'];

            $updateStmt = $this->db->prepare("
                UPDATE financial_goals 
                SET current_progress = :prog, status = :status, updated_at = NOW()
                WHERE id = :id AND user_id = :uid
            ");
            $updateStmt->execute([
                ':prog' => $newProgress,
                ':status' => $newStatus,
                ':id' => $goalId,
                ':uid' => $userId
            ]);

            $this->db->commit();
            logAudit($userId, 'ADD_GOAL_CONTRIBUTION', 'financial_goals', $goalId, "Contributed {$amount} to goal {$goal['goal_name']}");

            return ['success' => true, 'new_progress' => $newProgress, 'status' => $newStatus];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Failed to add goal contribution: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error while recording contribution.'];
        }
    }

    /**
     * Delete goal
     */
    public function deleteGoal(int $userId, int $goalId): bool {
        $stmt = $this->db->prepare("DELETE FROM financial_goals WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $goalId, ':uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Validate goal data
     */
    private function validateGoalData(array $data): array {
        $errors = [];
        if (empty($data['goal_name']) || strlen(trim($data['goal_name'])) < 2) {
            $errors[] = 'Goal name must be at least 2 characters.';
        }
        if (!isset($data['target_amount']) || !is_numeric($data['target_amount']) || (float)$data['target_amount'] <= 0) {
            $errors[] = 'Target amount must be a positive number.';
        }
        if (empty($data['start_date']) || empty($data['target_date'])) {
            $errors[] = 'Start date and target date are required.';
        } elseif ($data['start_date'] > $data['target_date']) {
            $errors[] = 'Target date must be after start date.';
        }
        return $errors;
    }
}
