<?php
declare(strict_types=1);

/**
 * Transparent Anomaly Detection Service
 * Expense Behaviour Analytical Platform (EBAP)
 * 
 * Implements deterministic, explainable rule-based anomaly detection.
 * Clearly documents historical baseline, observed value, and decision rule.
 * Does NOT claim to detect fraud or alter records automatically.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

class AnomalyDetectionService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Run rule-based anomaly detection across user transactions in a period
     */
    public function scanAndDetectAnomalies(int $userId, string $startDate, string $endDate): int {
        $detectedCount = 0;

        // Rule 1: Category Statistical Outliers (Transaction > 2.5x Category Median)
        $detectedCount += $this->detectCategoryOutliers($userId, $startDate, $endDate);

        // Rule 2: Single Large Transaction (> 3x Overall Average Transaction)
        $detectedCount += $this->detectLargeTransactionOutliers($userId, $startDate, $endDate);

        // Rule 3: Rapid Duplicate / Identical Charges within 48 Hours
        $detectedCount += $this->detectDuplicateCharges($userId, $startDate, $endDate);

        return $detectedCount;
    }

    /**
     * Rule 1: Category Outliers
     */
    private function detectCategoryOutliers(int $userId, string $startDate, string $endDate): int {
        // Fetch all categories with at least 3 transactions to establish a valid baseline
        $catStmt = $this->db->prepare("
            SELECT category_id, COUNT(*) as cat_count
            FROM expenses
            WHERE user_id = :uid
            GROUP BY category_id
            HAVING cat_count >= 3
        ");
        $catStmt->execute([':uid' => $userId]);
        $eligibleCategories = $catStmt->fetchAll();

        $count = 0;

        foreach ($eligibleCategories as $catRow) {
            $catId = (int)$catRow['category_id'];

            // Get all historical amounts for this category
            $amtStmt = $this->db->prepare("
                SELECT amount FROM expenses 
                WHERE user_id = :uid AND category_id = :cid
                ORDER BY amount ASC
            ");
            $amtStmt->execute([':uid' => $userId, ':cid' => $catId]);
            $amounts = $amtStmt->fetchAll(PDO::FETCH_COLUMN);
            $n = count($amounts);
            if ($n < 3) continue;

            $mid = (int)floor($n / 2);
            $median = ($n % 2 === 0) 
                ? (((float)$amounts[$mid - 1] + (float)$amounts[$mid]) / 2) 
                : (float)$amounts[$mid];

            // Threshold: 2.5x median, minimum difference ₹1,000
            $threshold = max($median * 2.5, $median + 1000);

            // Find transactions in date range exceeding threshold
            $txStmt = $this->db->prepare("
                SELECT e.id, e.title, e.amount, e.merchant, e.transaction_date, c.category_name
                FROM expenses e
                JOIN expense_categories c ON e.category_id = c.id
                WHERE e.user_id = :uid AND e.category_id = :cid 
                  AND e.transaction_date BETWEEN :start AND :end
                  AND e.amount > :thresh
            ");
            $txStmt->execute([
                ':uid' => $userId,
                ':cid' => $catId,
                ':start' => $startDate,
                ':end' => $endDate,
                ':thresh' => $threshold
            ]);
            $outliers = $txStmt->fetchAll();

            foreach ($outliers as $out) {
                $expId = (int)$out['id'];
                $amount = (float)$out['amount'];
                $multiplier = round($amount / max(1, $median), 1);

                $explanation = "Transaction '{$out['title']}' of " . formatCurrency($amount) . " on {$out['transaction_date']} is {$multiplier}x higher than your historical median of " . formatCurrency($median) . " for category '{$out['category_name']}'.";

                if ($this->recordAnomalyIfNotExists($userId, $expId, $catId, 'Category Outlier (>2.5x Median)', $median, $amount, $explanation)) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Rule 2: Single Large Transaction Outlier (> 3.0x Overall User Average)
     */
    private function detectLargeTransactionOutliers(int $userId, string $startDate, string $endDate): int {
        $avgStmt = $this->db->prepare("
            SELECT AVG(amount) as avg_amt, COUNT(*) as total_tx
            FROM expenses
            WHERE user_id = :uid
        ");
        $avgStmt->execute([':uid' => $userId]);
        $row = $avgStmt->fetch();

        $totalTx = (int)($row['total_tx'] ?? 0);
        $avgAmt = (float)($row['avg_amt'] ?? 0);

        if ($totalTx < 5 || $avgAmt <= 0) {
            return 0; // Insufficient history to determine global average
        }

        $threshold = max($avgAmt * 3.0, 5000.0);

        $stmt = $this->db->prepare("
            SELECT e.id, e.category_id, e.title, e.amount, e.transaction_date
            FROM expenses e
            WHERE e.user_id = :uid 
              AND e.transaction_date BETWEEN :start AND :end
              AND e.amount >= :thresh
        ");
        $stmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate, ':thresh' => $threshold]);
        $rows = $stmt->fetchAll();

        $count = 0;
        foreach ($rows as $r) {
            $expId = (int)$r['id'];
            $amount = (float)$r['amount'];
            $multiplier = round($amount / $avgAmt, 1);

            $explanation = "Transaction '{$r['title']}' (" . formatCurrency($amount) . ") exceeds your overall average transaction (" . formatCurrency($avgAmt) . ") by {$multiplier}x.";

            if ($this->recordAnomalyIfNotExists($userId, $expId, (int)$r['category_id'], 'High Single Transaction (>3x Avg)', $avgAmt, $amount, $explanation)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Rule 3: Rapid Duplicate / Identical Charges within 48 Hours
     */
    private function detectDuplicateCharges(int $userId, string $startDate, string $endDate): int {
        $stmt = $this->db->prepare("
            SELECT e1.id as exp1_id, e2.id as exp2_id, e1.title, e1.amount, e1.transaction_date as date1, e2.transaction_date as date2, e1.category_id
            FROM expenses e1
            JOIN expenses e2 ON e1.user_id = e2.user_id 
                            AND e1.id < e2.id 
                            AND e1.amount = e2.amount 
                            AND LOWER(TRIM(e1.title)) = LOWER(TRIM(e2.title))
                            AND DATEDIFF(e2.transaction_date, e1.transaction_date) BETWEEN 0 AND 2
            WHERE e1.user_id = :uid 
              AND e1.is_recurring = 0 AND e2.is_recurring = 0
              AND (e1.transaction_date BETWEEN :start1 AND :end1 OR e2.transaction_date BETWEEN :start2 AND :end2)
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':start1' => $startDate,
            ':end1' => $endDate,
            ':start2' => $startDate,
            ':end2' => $endDate
        ]);
        $duplicates = $stmt->fetchAll();

        $count = 0;
        foreach ($duplicates as $dup) {
            $expId = (int)$dup['exp2_id'];
            $amount = (float)$dup['amount'];
            $explanation = "Potential duplicate expense detected: Identical charge '{$dup['title']}' for " . formatCurrency($amount) . " occurred on {$dup['date1']} and again on {$dup['date2']}.";

            if ($this->recordAnomalyIfNotExists($userId, $expId, (int)$dup['category_id'], 'Rapid Duplicate Transaction', $amount, $amount, $explanation)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Safely insert anomaly review record if not previously logged
     */
    private function recordAnomalyIfNotExists(
        int $userId, ?int $expenseId, ?int $categoryId, 
        string $method, float $baseline, float $observed, string $explanation
    ): bool {
        // Check if already logged for this expense and detection method
        if ($expenseId) {
            $checkStmt = $this->db->prepare("
                SELECT id FROM anomaly_reviews 
                WHERE user_id = :uid AND expense_id = :eid AND detection_method = :method
                LIMIT 1
            ");
            $checkStmt->execute([':uid' => $userId, ':eid' => $expenseId, ':method' => $method]);
            if ($checkStmt->fetch()) {
                return false;
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO anomaly_reviews (
                user_id, expense_id, category_id, detection_method,
                baseline_value, observed_value, explanation, review_status, created_at
            ) VALUES (
                :uid, :eid, :cid, :method,
                :baseline, :observed, :explanation, 'pending', NOW()
            )
        ");

        $stmt->execute([
            ':uid' => $userId,
            ':eid' => $expenseId,
            ':cid' => $categoryId,
            ':method' => $method,
            ':baseline' => $baseline,
            ':observed' => $observed,
            ':explanation' => $explanation
        ]);

        return true;
    }

    /**
     * Get all anomalies for user
     */
    public function getAnomalies(int $userId, ?string $status = null): array {
        $where = ['a.user_id = :uid'];
        $params = [':uid' => $userId];

        if (!empty($status) && in_array($status, ['pending', 'reviewed', 'false_positive', 'dismissed'], true)) {
            $where[] = 'a.review_status = :status';
            $params[':status'] = $status;
        }

        $whereClause = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT a.*, e.title as expense_title, e.transaction_date, e.payment_method, e.merchant,
                   c.category_name, c.category_icon, c.category_color
            FROM anomaly_reviews a
            LEFT JOIN expenses e ON a.expense_id = e.id
            LEFT JOIN expense_categories c ON a.category_id = c.id
            WHERE {$whereClause}
            ORDER BY (a.review_status = 'pending') DESC, a.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Update review status of an anomaly
     */
    public function updateAnomalyStatus(int $userId, int $anomalyId, string $status): bool {
        $validStatuses = ['pending', 'reviewed', 'false_positive', 'dismissed'];
        if (!in_array($status, $validStatuses, true)) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE anomaly_reviews 
            SET review_status = :status, reviewed_at = NOW() 
            WHERE id = :id AND user_id = :uid
        ");
        $stmt->execute([':status' => $status, ':id' => $anomalyId, ':uid' => $userId]);
        $updated = $stmt->rowCount() > 0;

        if ($updated) {
            logAudit($userId, 'REVIEW_ANOMALY', 'anomaly_reviews', $anomalyId, "Updated anomaly review status to {$status}");
        }

        return $updated;
    }
}
