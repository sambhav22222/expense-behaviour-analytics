<?php
declare(strict_types=1);

/**
 * Report Generation & CSV Export Service
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/AnalyticsService.php';
require_once __DIR__ . '/BudgetService.php';
require_once __DIR__ . '/RecurringExpenseService.php';
require_once __DIR__ . '/GoalService.php';
require_once __DIR__ . '/AnomalyDetectionService.php';

class ReportService {
    private PDO $db;
    private AnalyticsService $analytics;
    private BudgetService $budgets;
    private RecurringExpenseService $recurring;
    private GoalService $goals;
    private AnomalyDetectionService $anomalies;

    public function __construct(
        ?PDO $db = null,
        ?AnalyticsService $analytics = null,
        ?BudgetService $budgets = null,
        ?RecurringExpenseService $recurring = null,
        ?GoalService $goals = null,
        ?AnomalyDetectionService $anomalies = null
    ) {
        $this->db = $db ?? getDBConnection();
        $this->analytics = $analytics ?? new AnalyticsService($this->db);
        $this->budgets = $budgets ?? new BudgetService($this->db);
        $this->recurring = $recurring ?? new RecurringExpenseService($this->db);
        $this->goals = $goals ?? new GoalService($this->db);
        $this->anomalies = $anomalies ?? new AnomalyDetectionService($this->db);
    }

    /**
     * Compile comprehensive report dataset for a specific date range
     */
    public function generateReportData(int $userId, string $reportType, string $startDate, string $endDate, int $days): array {
        switch ($reportType) {
            case 'income_vs_expense':
                $summary = $this->analytics->getSummaryMetrics($userId, $startDate, $endDate, $days);
                $monthlyTrends = $this->analytics->getMonthlyTrend($userId, 6);
                return [
                    'title' => 'Income vs. Expense Cash Flow Report',
                    'summary' => $summary,
                    'monthly_trends' => $monthlyTrends
                ];

            case 'category_breakdown':
                $categories = $this->analytics->getCategoryBreakdown($userId, $startDate, $endDate);
                $essentialVsDisc = $this->analytics->getEssentialVsDiscretionary($userId, $startDate, $endDate);
                return [
                    'title' => 'Category-wise Spending Breakdown Report',
                    'categories' => $categories['categories'],
                    'total_amount' => $categories['total_amount'],
                    'essential_vs_discretionary' => $essentialVsDisc
                ];

            case 'budget_performance':
                $activeBudgets = $this->budgets->getActiveBudgetsWithUtilization($userId, $endDate);
                return [
                    'title' => 'Budget Performance & Variance Analysis Report',
                    'budgets' => $activeBudgets
                ];

            case 'recurring_expenses':
                $recData = $this->recurring->getAllRecurring($userId);
                return [
                    'title' => 'Recurring Subscriptions & Fixed Commitments Report',
                    'items' => $recData['items'],
                    'total_monthly' => $recData['total_monthly'],
                    'total_annual' => $recData['total_annual']
                ];

            case 'goals_progress':
                $goalsList = $this->goals->getGoals($userId);
                return [
                    'title' => 'Financial Goals Progress & Savings Report',
                    'goals' => $goalsList
                ];

            case 'anomalies_review':
                $anomalyList = $this->anomalies->getAnomalies($userId);
                return [
                    'title' => 'Spending Anomaly Review & Detection Report',
                    'anomalies' => $anomalyList
                ];

            case 'comprehensive_monthly':
            default:
                $summary = $this->analytics->getSummaryMetrics($userId, $startDate, $endDate, $days);
                $categories = $this->analytics->getCategoryBreakdown($userId, $startDate, $endDate);
                $activeBudgets = $this->budgets->getActiveBudgetsWithUtilization($userId, $endDate);
                $weekdayVsWeekend = $this->analytics->getWeekdayVsWeekend($userId, $startDate, $endDate);
                $paymentMethods = $this->analytics->getPaymentMethodDistribution($userId, $startDate, $endDate);

                return [
                    'title' => 'Comprehensive Monthly Financial Report',
                    'summary' => $summary,
                    'categories' => $categories['categories'],
                    'budgets' => $activeBudgets,
                    'weekday_weekend' => $weekdayVsWeekend,
                    'payment_methods' => $paymentMethods
                ];
        }
    }

    /**
     * Stream CSV Export for Expenses with Formula Injection Sanitization
     */
    public function exportExpensesToCsv(int $userId, array $records, string $filename = 'expenses_export.csv'): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV Header
        fputcsv($out, [
            'Transaction ID',
            'Date',
            'Title',
            'Category',
            'Amount',
            'Payment Method',
            'Merchant',
            'Description',
            'Recurring',
            'Created At'
        ]);

        foreach ($records as $r) {
            fputcsv($out, [
                sanitizeCsvCell($r['id']),
                sanitizeCsvCell($r['transaction_date']),
                sanitizeCsvCell($r['title']),
                sanitizeCsvCell($r['category_name']),
                sanitizeCsvCell($r['amount']),
                sanitizeCsvCell($r['payment_method']),
                sanitizeCsvCell($r['merchant'] ?? ''),
                sanitizeCsvCell($r['description'] ?? ''),
                sanitizeCsvCell(!empty($r['is_recurring']) ? 'Yes' : 'No'),
                sanitizeCsvCell($r['created_at'])
            ]);
        }

        fclose($out);
        exit;
    }

    /**
     * Stream CSV Export for Income Records
     */
    public function exportIncomeToCsv(int $userId, array $records, string $filename = 'income_export.csv'): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($out, [
            'Income ID',
            'Date Received',
            'Title',
            'Source Category',
            'Amount',
            'Payment Method',
            'Payer',
            'Description',
            'Created At'
        ]);

        foreach ($records as $r) {
            fputcsv($out, [
                sanitizeCsvCell($r['id']),
                sanitizeCsvCell($r['received_date']),
                sanitizeCsvCell($r['title']),
                sanitizeCsvCell($r['category_name']),
                sanitizeCsvCell($r['amount']),
                sanitizeCsvCell($r['payment_method']),
                sanitizeCsvCell($r['payer'] ?? ''),
                sanitizeCsvCell($r['description'] ?? ''),
                sanitizeCsvCell($r['created_at'])
            ]);
        }

        fclose($out);
        exit;
    }
}
