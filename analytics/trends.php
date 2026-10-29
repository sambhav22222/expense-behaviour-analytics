<?php
declare(strict_types=1);

/**
 * Historical Spending Trends Analytics
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/AnalyticsService.php';

requireAuth();

$userId = getAuthUserId();
$currency = getAuthCurrency();

$period = $_GET['period'] ?? 'this_month';
$range = getDateRangeForPeriod($period);

$analytics = new AnalyticsService();
$dailyTrend = $analytics->getDailyTrend($userId, $range['start'], $range['end']);
$monthlyTrend = $analytics->getMonthlyTrend($userId, 12);
$paymentMethods = $analytics->getPaymentMethodDistribution($userId, $range['start'], $range['end']);

$pageTitle = 'Spending Trends';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Spending Trends & Historical Velocity</h4>
        <p class="text-secondary fs-7 mb-0">Track how your spending patterns evolve over days, months, and payment modes.</p>
    </div>

    <form method="GET" action="<?= url('analytics/trends.php') ?>" class="d-flex align-items-center gap-2">
        <select name="period" onchange="this.form.submit()" class="form-select form-select-sm">
            <option value="this_week" <?= $period === 'this_week' ? 'selected' : '' ?>>This Week</option>
            <option value="this_month" <?= $period === 'this_month' ? 'selected' : '' ?>>This Month</option>
            <option value="previous_month" <?= $period === 'previous_month' ? 'selected' : '' ?>>Previous Month</option>
            <option value="last_3_months" <?= $period === 'last_3_months' ? 'selected' : '' ?>>Last 3 Months</option>
            <option value="this_year" <?= $period === 'this_year' ? 'selected' : '' ?>>This Year</option>
        </select>
    </form>
</div>

<!-- Daily Spending Trend Line -->
<div class="ebap-card bg-white p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0 fs-6">Daily Spending Timeline (<?= e($range['label']) ?>)</h6>
    </div>
    <div style="height: 300px;">
        <canvas id="dailyTrendFullChart"></canvas>
    </div>
</div>

<!-- 12-Month Multi-Month Cash Flow Trend Table & Chart -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="ebap-card bg-white p-4 h-100">
            <h6 class="fw-bold text-dark mb-3 fs-6">Multi-Month Income vs. Expense Trajectory</h6>
            <div style="height: 280px;">
                <canvas id="multiMonthChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="ebap-card bg-white p-4 h-100">
            <h6 class="fw-bold text-dark mb-3 fs-6">Payment Method Distribution</h6>
            <div style="height: 280px;">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="ebap-card bg-white p-4 mb-4">
    <h6 class="fw-bold text-dark mb-3 fs-6">Historical Monthly Performance Summary</h6>
    <div class="table-responsive">
        <table class="table ebap-table align-middle">
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="text-end">Recorded Income</th>
                    <th class="text-end">Recorded Expenses</th>
                    <th class="text-end">Net Cash Flow</th>
                    <th class="text-end">Expense Transactions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthlyTrend as $m): ?>
                    <tr>
                        <td class="fw-semibold text-dark"><?= e($m['label']) ?></td>
                        <td class="text-end font-monospace text-success">+<?= formatCurrency($m['income']) ?></td>
                        <td class="text-end font-monospace text-danger"><?= formatCurrency($m['expense']) ?></td>
                        <td class="text-end font-monospace fw-bold <?= $m['net_savings'] >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= ($m['net_savings'] >= 0 ? '+' : '') . formatCurrency($m['net_savings']) ?>
                        </td>
                        <td class="text-end font-monospace text-muted"><?= $m['tx_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const symbol = '<?= getCurrencySymbol($currency) ?>';

    // 1. Daily Trend
    const dailyLabels = <?= json_encode(array_column($dailyTrend, 'formatted_date')) ?>;
    const dailyAmounts = <?= json_encode(array_column($dailyTrend, 'amount')) ?>;
    EBAPCharts.renderDailyTrend('dailyTrendFullChart', dailyLabels, dailyAmounts, symbol);

    // 2. Multi-Month Grouped Bar
    const mLabels = <?= json_encode(array_column($monthlyTrend, 'label')) ?>;
    const mIncome = <?= json_encode(array_column($monthlyTrend, 'income')) ?>;
    const mExpense = <?= json_encode(array_column($monthlyTrend, 'expense')) ?>;
    EBAPCharts.renderIncomeVsExpense('multiMonthChart', mLabels, mIncome, mExpense, symbol);

    // 3. Payment Methods Doughnut
    const pmLabels = <?= json_encode(array_column($paymentMethods, 'payment_method')) ?>;
    const pmAmounts = <?= json_encode(array_column($paymentMethods, 'total_amount')) ?>;
    EBAPCharts.renderCategoryDoughnut('paymentMethodsChart', pmLabels, pmAmounts, null, symbol);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
