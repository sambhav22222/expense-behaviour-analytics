<?php
declare(strict_types=1);

/**
 * Behavioural Insights Engine
 * Expense Behaviour Analytical Platform (EBAP)
 * 
 * Generates transparent, deterministic, and explainable behavioural spending
 * insights based strictly on actual database transactions.
 */

require_once __DIR__ . '/AnalyticsService.php';
require_once __DIR__ . '/BudgetService.php';

class BehaviourInsightService {
    private AnalyticsService $analytics;
    private BudgetService $budgets;

    public function __construct(?AnalyticsService $analytics = null, ?BudgetService $budgets = null) {
        $this->analytics = $analytics ?? new AnalyticsService();
        $this->budgets = $budgets ?? new BudgetService();
    }

    /**
     * Generate all dynamic behavioural insights for user in period
     */
    public function generateInsights(int $userId, string $startDate, string $endDate, int $daysInPeriod): array {
        $summary = $this->analytics->getSummaryMetrics($userId, $startDate, $endDate, $daysInPeriod);
        $categories = $this->analytics->getCategoryBreakdown($userId, $startDate, $endDate);
        $essentialVsDiscretionary = $this->analytics->getEssentialVsDiscretionary($userId, $startDate, $endDate);
        $weekdayVsWeekend = $this->analytics->getWeekdayVsWeekend($userId, $startDate, $endDate);
        $comparison = $this->analytics->compareWithPreviousPeriod($userId, $startDate, $endDate);
        $activeBudgets = $this->budgets->getActiveBudgetsWithUtilization($userId, $endDate);

        $insights = [];

        // Check data sufficiency
        $txCount = $summary['transaction_count'];
        if ($txCount === 0) {
            return [
                [
                    'type' => 'info',
                    'icon' => 'bi-info-circle',
                    'badge' => 'Getting Started',
                    'title' => 'No Recorded Transactions in Selected Period',
                    'description' => 'You do not have any expenses logged in this date range. Once you record transactions, EBAP will analyze your spending habits and provide actionable behavioural patterns.',
                    'calculation' => 'Transaction Count = 0',
                    'suggested_action' => 'Add your daily expenses or import transactions via CSV.',
                    'confidence' => 'N/A'
                ]
            ];
        }

        // 1. Dominant Category Insight
        if (!empty($summary['highest_category'])) {
            $hc = $summary['highest_category'];
            $share = $hc['share_pct'];
            $isHigh = $share >= 30.0;

            $insights[] = [
                'type' => $isHigh ? 'warning' : 'primary',
                'icon' => 'bi-pie-chart-fill',
                'badge' => 'Category Concentration',
                'title' => "{$hc['name']} represents {$share}% of your spending",
                'description' => "During this period, you spent " . formatCurrency($hc['amount']) . " across {$hc['count']} transaction(s) in {$hc['name']}. This is your single highest expenditure category.",
                'calculation' => "Category Share = (" . formatCurrency($hc['amount']) . " / " . formatCurrency($summary['total_expenses']) . ") × 100 = {$share}%",
                'suggested_action' => ($isHigh 
                    ? "Consider establishing a dedicated category budget for {$hc['name']} to keep high-concentration expenses in check."
                    : "Your spending is reasonably distributed. Monitor recurring purchases in {$hc['name']}."),
                'confidence' => ($txCount >= 5) ? 'High' : 'Moderate (Few Transactions)'
            ];
        }

        // 2. Budget Health & Threshold Warnings
        foreach ($activeBudgets as $b) {
            if ($b['utilization_pct'] >= 100) {
                $insights[] = [
                    'type' => 'danger',
                    'icon' => 'bi-exclamation-triangle-fill',
                    'badge' => 'Budget Exceeded',
                    'title' => "Budget Exceeded: {$b['budget_name']}",
                    'description' => "You have spent " . formatCurrency($b['actual_spent']) . " against your allocated limit of " . formatCurrency($b['budget_amount']) . " (" . $b['utilization_pct'] . "% utilization).",
                    'calculation' => "Over budget by: " . formatCurrency($b['actual_spent'] - $b['budget_amount']),
                    'suggested_action' => 'Review recent transactions in this category and avoid non-essential expenses for the remainder of this cycle.',
                    'confidence' => 'High'
                ];
            } elseif ($b['utilization_pct'] >= $b['warning_threshold']) {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => 'bi-speedometer2',
                    'badge' => 'Budget Alert',
                    'title' => "Approaching Limit: {$b['budget_name']}",
                    'description' => "You have utilized {$b['utilization_pct']}% of {$b['budget_name']}. Only " . formatCurrency($b['remaining_amount']) . " remains.",
                    'calculation' => "Remaining = " . formatCurrency($b['budget_amount']) . " − " . formatCurrency($b['actual_spent']) . " = " . formatCurrency($b['remaining_amount']),
                    'suggested_action' => 'Pace your purchases to avoid exceeding your spending threshold before the period ends.',
                    'confidence' => 'High'
                ];
            }
        }

        // 3. Period-over-Period Momentum
        if ($comparison['previous']['total'] > 0 && $comparison['pct_change'] !== null) {
            $change = $comparison['pct_change'];
            $absChange = abs($change);
            if ($absChange >= 5.0) {
                $isIncrease = $change > 0;
                $insights[] = [
                    'type' => $isIncrease ? 'warning' : 'success',
                    'icon' => $isIncrease ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow',
                    'badge' => 'Period Trend',
                    'title' => $isIncrease 
                        ? "Expenses increased by {$absChange}% compared to previous period"
                        : "Expenses decreased by {$absChange}% compared to previous period",
                    'description' => "Current period spending is " . formatCurrency($comparison['current']['total']) . " compared to " . formatCurrency($comparison['previous']['total']) . " in the preceding comparable timeframe.",
                    'calculation' => "Change = ((" . formatCurrency($comparison['current']['total']) . " − " . formatCurrency($comparison['previous']['total']) . ") / " . formatCurrency($comparison['previous']['total']) . ") × 100 = {$change}%",
                    'suggested_action' => $isIncrease 
                        ? 'Check whether this increase was driven by one-off emergencies or recurring lifestyle creep.'
                        : 'Great progress in reducing your overall spending compared to the preceding cycle!',
                    'confidence' => 'High'
                ];
            }
        } elseif ($comparison['previous']['total'] == 0) {
            $insights[] = [
                'type' => 'info',
                'icon' => 'bi-clock-history',
                'badge' => 'Historical Baseline',
                'title' => 'No Prior Baseline Available',
                'description' => 'There were no recorded expenses in the immediately preceding equivalent period (' . $comparison['previous']['label'] . '). A trend comparison will be enabled once subsequent periods are completed.',
                'calculation' => 'Prior Period Spend = 0.00',
                'suggested_action' => 'Maintain consistent transaction logging to build a reliable historical baseline.',
                'confidence' => 'Baseline Initializing'
            ];
        }

        // 4. Weekday vs Weekend Behavioral Bias
        if ($weekdayVsWeekend['total_amount'] > 0 && $weekdayVsWeekend['weekend_count'] > 0) {
            $weekendShare = $weekdayVsWeekend['weekend_pct'];
            // Since weekends are 2/7 days (~28.6% of time), spending > 40% on weekends signals lifestyle weekend clustering
            if ($weekendShare >= 38.0) {
                $insights[] = [
                    'type' => 'secondary',
                    'icon' => 'bi-calendar2-week',
                    'badge' => 'Weekend Habit Pattern',
                    'title' => "Weekend Spending Bias: {$weekendShare}% spent on Saturdays & Sundays",
                    'description' => "Although weekends represent only 28.6% of the week, they accounted for " . formatCurrency($weekdayVsWeekend['weekend_amount']) . " ({$weekendShare}%) of your total spending across {$weekdayVsWeekend['weekend_count']} transactions.",
                    'calculation' => "Weekend Share = (" . formatCurrency($weekdayVsWeekend['weekend_amount']) . " / " . formatCurrency($weekdayVsWeekend['total_amount']) . ") × 100 = {$weekendShare}%",
                    'suggested_action' => 'Weekend outings and dining represent a major spending acceleration. Planning leisure budgets in advance helps regulate weekend spikes.',
                    'confidence' => ($txCount >= 8) ? 'High' : 'Moderate'
                ];
            }
        }

        // 5. Essential vs Discretionary Ratio
        if ($essentialVsDiscretionary['total_amount'] > 0) {
            $discPct = $essentialVsDiscretionary['discretionary_pct'];
            $essPct = $essentialVsDiscretionary['essential_pct'];
            $insights[] = [
                'type' => ($discPct > 50.0) ? 'warning' : 'info',
                'icon' => 'bi-shield-check',
                'badge' => 'Spending Quality',
                'title' => "{$essPct}% Essential Needs vs {$discPct}% Discretionary Spending",
                'description' => "Essential requirements (groceries, housing, utilities, transport, healthcare) totaled " . formatCurrency($essentialVsDiscretionary['essential_amount']) . ", while discretionary lifestyle choices totaled " . formatCurrency($essentialVsDiscretionary['discretionary_amount']) . ".",
                'calculation' => "Essential = {$essPct}% | Discretionary = {$discPct}%",
                'suggested_action' => ($discPct > 45.0) 
                    ? 'Discretionary spending is relatively high. Review dining out, apparel, and entertainment for opportunities to reallocate into savings goals.'
                    : 'A healthy financial foundation: essential expenses form the majority of your cash flow.',
                'confidence' => 'High'
            ];
        }

        // 6. Subscription / Recurring Commitments Load
        if ($summary['recurring_expenses_total'] > 0 && $summary['recurring_share_pct'] > 0) {
            $recShare = $summary['recurring_share_pct'];
            $insights[] = [
                'type' => ($recShare >= 25.0) ? 'warning' : 'primary',
                'icon' => 'bi-arrow-repeat',
                'badge' => 'Fixed Commitments',
                'title' => "Recurring Subscriptions form {$recShare}% of your spending",
                'description' => "Fixed and recurring obligations accounted for " . formatCurrency($summary['recurring_expenses_total']) . " during this period.",
                'calculation' => "Recurring Share = (" . formatCurrency($summary['recurring_expenses_total']) . " / " . formatCurrency($summary['total_expenses']) . ") × 100 = {$recShare}%",
                'suggested_action' => 'Regularly review active digital subscriptions, cloud services, and memberships in the Subscriptions module to identify unused services.',
                'confidence' => 'High'
            ];
        }

        // 7. Transaction Size Dispersion (Mean vs Median)
        if ($txCount >= 4 && $summary['avg_tx_amount'] > 0 && $summary['median_tx_amount'] > 0) {
            $mean = $summary['avg_tx_amount'];
            $median = $summary['median_tx_amount'];
            $ratio = round($mean / $median, 2);

            if ($ratio >= 1.6) {
                $insights[] = [
                    'type' => 'secondary',
                    'icon' => 'bi-calculator',
                    'badge' => 'Outlier Distribution',
                    'title' => "Average transaction (" . formatCurrency($mean) . ") is {$ratio}x higher than median (" . formatCurrency($median) . ")",
                    'description' => "A large gap between the average and the median indicates that a few disproportionately large transactions are pulling up your average expenditure.",
                    'calculation' => "Mean = " . formatCurrency($mean) . " | Median = " . formatCurrency($median) . " | Largest = " . formatCurrency($summary['max_tx_amount']),
                    'suggested_action' => 'Focus on controlling infrequent high-ticket purchases, as your typical daily routine purchases are much lower.',
                    'confidence' => 'High'
                ];
            }
        }

        return $insights;
    }
}
