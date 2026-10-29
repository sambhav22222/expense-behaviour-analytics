<?php
declare(strict_types=1);

/**
 * Core Analytics & Mathematical Calculation Engine
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class AnalyticsService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Compute comprehensive financial metrics summary for a selected period
     */
    public function getSummaryMetrics(int $userId, string $startDate, string $endDate, int $daysInPeriod): array {
        // 1. Total Expenses & Count
        $expStmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_expenses,
                   COUNT(*) as tx_count,
                   COALESCE(MAX(amount), 0) as max_tx,
                   COALESCE(MIN(amount), 0) as min_tx
            FROM expenses
            WHERE user_id = :uid AND transaction_date BETWEEN :start AND :end
        ");
        $expStmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $expData = $expStmt->fetch();

        $totalExpenses = (float)$expData['total_expenses'];
        $txCount = (int)$expData['tx_count'];
        $maxTx = (float)$expData['max_tx'];
        $minTx = ($txCount > 0) ? (float)$expData['min_tx'] : 0.0;

        // 2. Total Income
        $incStmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total_income, COUNT(*) as inc_count
            FROM income_records
            WHERE user_id = :uid AND received_date BETWEEN :start AND :end
        ");
        $incStmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $incData = $incStmt->fetch();

        $totalIncome = (float)$incData['total_income'];
        $incCount = (int)$incData['inc_count'];

        // 3. Net Cash Flow = Total Income - Total Expenses
        $netCashFlow = $totalIncome - $totalExpenses;

        // 4. Averages
        $avgDailySpend = ($daysInPeriod > 0) ? round($totalExpenses / $daysInPeriod, 2) : 0.0;
        $avgTxAmount = ($txCount > 0) ? round($totalExpenses / $txCount, 2) : 0.0;

        // 5. Median Transaction Amount
        $medianTx = 0.0;
        if ($txCount > 0) {
            $amtStmt = $this->db->prepare("
                SELECT amount FROM expenses 
                WHERE user_id = :uid AND transaction_date BETWEEN :start AND :end
                ORDER BY amount ASC
            ");
            $amtStmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
            $amounts = $amtStmt->fetchAll(PDO::FETCH_COLUMN);
            $count = count($amounts);
            $mid = (int)floor($count / 2);
            if ($count % 2 === 0) {
                $medianTx = round(((float)$amounts[$mid - 1] + (float)$amounts[$mid]) / 2, 2);
            } else {
                $medianTx = round((float)$amounts[$mid], 2);
            }
        }

        // 6. Highest Spending Category
        $highestCategory = null;
        if ($totalExpenses > 0) {
            $catStmt = $this->db->prepare("
                SELECT c.category_name, c.category_color, c.category_icon,
                       SUM(e.amount) as category_total,
                       COUNT(e.id) as category_count
                FROM expenses e
                JOIN expense_categories c ON e.category_id = c.id
                WHERE e.user_id = :uid AND e.transaction_date BETWEEN :start AND :end
                GROUP BY c.id, c.category_name, c.category_color, c.category_icon
                ORDER BY category_total DESC
                LIMIT 1
            ");
            $catStmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
            $highest = $catStmt->fetch();
            if ($highest) {
                $highestCategory = [
                    'name' => $highest['category_name'],
                    'color' => $highest['category_color'],
                    'icon' => $highest['category_icon'],
                    'amount' => (float)$highest['category_total'],
                    'count' => (int)$highest['category_count'],
                    'share_pct' => round(((float)$highest['category_total'] / $totalExpenses) * 100, 1)
                ];
            }
        }

        // 7. Overall Active Budget & Remaining
        $bgtStmt = $this->db->prepare("
            SELECT COALESCE(SUM(budget_amount), 0) as total_budget
            FROM budgets
            WHERE user_id = :uid AND status = 'active' AND category_id IS NULL
              AND :start BETWEEN start_date AND end_date
        ");
        $bgtStmt->execute([':uid' => $userId, ':start' => $startDate]);
        $bgtRow = $bgtStmt->fetch();
        $totalBudget = (float)($bgtRow['total_budget'] ?? 0);
        $remainingBudget = ($totalBudget > 0) ? ($totalBudget - $totalExpenses) : null;
        $budgetUtilization = ($totalBudget > 0) ? round(($totalExpenses / $totalBudget) * 100, 1) : null;

        // 8. Recurring expenses portion
        $recStmt = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as recurring_total
            FROM expenses
            WHERE user_id = :uid AND is_recurring = 1 AND transaction_date BETWEEN :start AND :end
        ");
        $recStmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $recRow = $recStmt->fetch();
        $recurringTotal = (float)($recRow['recurring_total'] ?? 0);
        $recurringSharePct = ($totalExpenses > 0) ? round(($recurringTotal / $totalExpenses) * 100, 1) : 0.0;

        return [
            'total_income' => $totalIncome,
            'income_count' => $incCount,
            'total_expenses' => $totalExpenses,
            'transaction_count' => $txCount,
            'net_cash_flow' => $netCashFlow,
            'avg_daily_spend' => $avgDailySpend,
            'avg_tx_amount' => $avgTxAmount,
            'median_tx_amount' => $medianTx,
            'max_tx_amount' => $maxTx,
            'min_tx_amount' => $minTx,
            'highest_category' => $highestCategory,
            'total_budget' => $totalBudget,
            'remaining_budget' => $remainingBudget,
            'budget_utilization_pct' => $budgetUtilization,
            'recurring_expenses_total' => $recurringTotal,
            'recurring_share_pct' => $recurringSharePct,
            'days_in_period' => $daysInPeriod
        ];
    }

    /**
     * Get Category Breakdown (amounts, transaction counts, share %, is_essential)
     */
    public function getCategoryBreakdown(int $userId, string $startDate, string $endDate): array {
        $stmt = $this->db->prepare("
            SELECT c.id as category_id, c.category_name, c.category_icon, c.category_color, c.is_essential,
                   COALESCE(SUM(e.amount), 0) as total_amount,
                   COUNT(e.id) as tx_count,
                   COALESCE(AVG(e.amount), 0) as avg_amount
            FROM expenses e
            JOIN expense_categories c ON e.category_id = c.id
            WHERE e.user_id = :uid AND e.transaction_date BETWEEN :start AND :end
            GROUP BY c.id, c.category_name, c.category_icon, c.category_color, c.is_essential
            ORDER BY total_amount DESC
        ");
        $stmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $categories = $stmt->fetchAll();

        $totalSpent = array_sum(array_column($categories, 'total_amount'));

        foreach ($categories as &$cat) {
            $cat['total_amount'] = (float)$cat['total_amount'];
            $cat['avg_amount'] = round((float)$cat['avg_amount'], 2);
            $cat['tx_count'] = (int)$cat['tx_count'];
            $cat['share_pct'] = ($totalSpent > 0) ? round(($cat['total_amount'] / $totalSpent) * 100, 1) : 0.0;
        }

        return [
            'categories' => $categories,
            'total_amount' => (float)$totalSpent
        ];
    }

    /**
     * Essential vs Discretionary spending breakdown
     */
    public function getEssentialVsDiscretionary(int $userId, string $startDate, string $endDate): array {
        $stmt = $this->db->prepare("
            SELECT c.is_essential,
                   COALESCE(SUM(e.amount), 0) as total_amount,
                   COUNT(e.id) as tx_count
            FROM expenses e
            JOIN expense_categories c ON e.category_id = c.id
            WHERE e.user_id = :uid AND e.transaction_date BETWEEN :start AND :end
            GROUP BY c.is_essential
        ");
        $stmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $rows = $stmt->fetchAll();

        $essentialAmt = 0.0;
        $essentialCount = 0;
        $discretionaryAmt = 0.0;
        $discretionaryCount = 0;

        foreach ($rows as $r) {
            if ((int)$r['is_essential'] === 1) {
                $essentialAmt = (float)$r['total_amount'];
                $essentialCount = (int)$r['tx_count'];
            } else {
                $discretionaryAmt = (float)$r['total_amount'];
                $discretionaryCount = (int)$r['tx_count'];
            }
        }

        $total = $essentialAmt + $discretionaryAmt;
        $essentialPct = ($total > 0) ? round(($essentialAmt / $total) * 100, 1) : 0.0;
        $discretionaryPct = ($total > 0) ? round(($discretionaryAmt / $total) * 100, 1) : 0.0;

        return [
            'essential_amount' => $essentialAmt,
            'essential_count' => $essentialCount,
            'essential_pct' => $essentialPct,
            'discretionary_amount' => $discretionaryAmt,
            'discretionary_count' => $discretionaryCount,
            'discretionary_pct' => $discretionaryPct,
            'total_amount' => $total
        ];
    }

    /**
     * Weekday vs Weekend spending breakdown
     */
    public function getWeekdayVsWeekend(int $userId, string $startDate, string $endDate): array {
        // DAYOFWEEK in MySQL: 1 = Sunday, 7 = Saturday
        $stmt = $this->db->prepare("
            SELECT 
                CASE WHEN DAYOFWEEK(transaction_date) IN (1, 7) THEN 'weekend' ELSE 'weekday' END AS day_type,
                COALESCE(SUM(amount), 0) as total_amount,
                COUNT(*) as tx_count
            FROM expenses
            WHERE user_id = :uid AND transaction_date BETWEEN :start AND :end
            GROUP BY day_type
        ");
        $stmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $rows = $stmt->fetchAll();

        $weekdayAmt = 0.0;
        $weekdayCount = 0;
        $weekendAmt = 0.0;
        $weekendCount = 0;

        foreach ($rows as $r) {
            if ($r['day_type'] === 'weekend') {
                $weekendAmt = (float)$r['total_amount'];
                $weekendCount = (int)$r['tx_count'];
            } else {
                $weekdayAmt = (float)$r['total_amount'];
                $weekdayCount = (int)$r['tx_count'];
            }
        }

        $total = $weekdayAmt + $weekendAmt;
        $weekdayPct = ($total > 0) ? round(($weekdayAmt / $total) * 100, 1) : 0.0;
        $weekendPct = ($total > 0) ? round(($weekendAmt / $total) * 100, 1) : 0.0;

        return [
            'weekday_amount' => $weekdayAmt,
            'weekday_count' => $weekdayCount,
            'weekday_pct' => $weekdayPct,
            'weekend_amount' => $weekendAmt,
            'weekend_count' => $weekendCount,
            'weekend_pct' => $weekendPct,
            'total_amount' => $total
        ];
    }

    /**
     * Payment Method Distribution
     */
    public function getPaymentMethodDistribution(int $userId, string $startDate, string $endDate): array {
        $stmt = $this->db->prepare("
            SELECT payment_method, 
                   COALESCE(SUM(amount), 0) as total_amount,
                   COUNT(*) as tx_count
            FROM expenses
            WHERE user_id = :uid AND transaction_date BETWEEN :start AND :end
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ");
        $stmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $rows = $stmt->fetchAll();

        $total = array_sum(array_column($rows, 'total_amount'));
        foreach ($rows as &$r) {
            $r['total_amount'] = (float)$r['total_amount'];
            $r['tx_count'] = (int)$r['tx_count'];
            $r['share_pct'] = ($total > 0) ? round(($r['total_amount'] / $total) * 100, 1) : 0.0;
        }

        return $rows;
    }

    /**
     * Daily spending trend series
     */
    public function getDailyTrend(int $userId, string $startDate, string $endDate): array {
        $stmt = $this->db->prepare("
            SELECT transaction_date, 
                   COALESCE(SUM(amount), 0) as daily_total,
                   COUNT(*) as tx_count
            FROM expenses
            WHERE user_id = :uid AND transaction_date BETWEEN :start AND :end
            GROUP BY transaction_date
            ORDER BY transaction_date ASC
        ");
        $stmt->execute([':uid' => $userId, ':start' => $startDate, ':end' => $endDate]);
        $rows = $stmt->fetchAll();

        // Build continuous date timeline
        $timeline = [];
        $current = new DateTime($startDate);
        $end = new DateTime($endDate);

        $lookup = [];
        foreach ($rows as $r) {
            $lookup[$r['transaction_date']] = [
                'amount' => (float)$r['daily_total'],
                'count' => (int)$r['tx_count']
            ];
        }

        while ($current <= $end) {
            $d = $current->format('Y-m-d');
            $timeline[] = [
                'date' => $d,
                'formatted_date' => $current->format('d M'),
                'amount' => $lookup[$d]['amount'] ?? 0.0,
                'count' => $lookup[$d]['count'] ?? 0
            ];
            $current->modify('+1 day');
        }

        return $timeline;
    }

    /**
     * Monthly trend comparison (last 6 months)
     */
    public function getMonthlyTrend(int $userId, int $months = 6): array {
        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(transaction_date, '%Y-%m') as ym,
                   COALESCE(SUM(amount), 0) as expense_total,
                   COUNT(*) as tx_count
            FROM expenses
            WHERE user_id = :uid AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL :m MONTH)
            GROUP BY ym
            ORDER BY ym ASC
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':m', $months, PDO::PARAM_INT);
        $stmt->execute();
        $expenseRows = $stmt->fetchAll();

        // Income for same months
        $incStmt = $this->db->prepare("
            SELECT DATE_FORMAT(received_date, '%Y-%m') as ym,
                   COALESCE(SUM(amount), 0) as income_total
            FROM income_records
            WHERE user_id = :uid AND received_date >= DATE_SUB(CURDATE(), INTERVAL :m MONTH)
            GROUP BY ym
            ORDER BY ym ASC
        ");
        $incStmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $incStmt->bindValue(':m', $months, PDO::PARAM_INT);
        $incStmt->execute();
        $incomeRows = $incStmt->fetchAll();

        $incLookup = [];
        foreach ($incomeRows as $ir) {
            $incLookup[$ir['ym']] = (float)$ir['income_total'];
        }

        $result = [];
        foreach ($expenseRows as $er) {
            $ym = $er['ym'];
            $exp = (float)$er['expense_total'];
            $inc = $incLookup[$ym] ?? 0.0;
            $dt = DateTime::createFromFormat('Y-m-d', $ym . '-01');
            $result[] = [
                'year_month' => $ym,
                'label' => $dt ? $dt->format('M Y') : $ym,
                'expense' => $exp,
                'income' => $inc,
                'net_savings' => $inc - $exp,
                'tx_count' => (int)$er['tx_count']
            ];
        }

        return $result;
    }

    /**
     * Compare current period with previous equivalent period
     */
    public function compareWithPreviousPeriod(int $userId, string $curStart, string $curEnd): array {
        $prev = getPrecedingEquivalentRange($curStart, $curEnd);

        // Current period expenses
        $stmtCur = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total, COUNT(*) as count 
            FROM expenses 
            WHERE user_id = :uid AND transaction_date BETWEEN :start AND :end
        ");
        $stmtCur->execute([':uid' => $userId, ':start' => $curStart, ':end' => $curEnd]);
        $curData = $stmtCur->fetch();
        $curTotal = (float)$curData['total'];
        $curCount = (int)$curData['count'];

        // Previous period expenses
        $stmtPrev = $this->db->prepare("
            SELECT COALESCE(SUM(amount), 0) as total, COUNT(*) as count 
            FROM expenses 
            WHERE user_id = :uid AND transaction_date BETWEEN :start AND :end
        ");
        $stmtPrev->execute([':uid' => $userId, ':start' => $prev['start'], ':end' => $prev['end']]);
        $prevData = $stmtPrev->fetch();
        $prevTotal = (float)$prevData['total'];
        $prevCount = (int)$prevData['count'];

        // Percentage change calculation
        $diffAmount = $curTotal - $prevTotal;
        $pctChange = null;
        $status = 'neutral';

        if ($prevTotal > 0) {
            $pctChange = round(($diffAmount / $prevTotal) * 100, 1);
            if ($pctChange > 0) {
                $status = 'increased'; // Spending went up
            } elseif ($pctChange < 0) {
                $status = 'decreased'; // Spending went down
            }
        }

        return [
            'current' => [
                'start' => $curStart,
                'end' => $curEnd,
                'total' => $curTotal,
                'count' => $curCount
            ],
            'previous' => [
                'start' => $prev['start'],
                'end' => $prev['end'],
                'total' => $prevTotal,
                'count' => $prevCount,
                'label' => $prev['label']
            ],
            'diff_amount' => $diffAmount,
            'pct_change' => $pctChange,
            'status' => $status
        ];
    }
}
