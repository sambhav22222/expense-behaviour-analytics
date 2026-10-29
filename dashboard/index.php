<?php
declare(strict_types=1);

/**
 * Main Analytics Dashboard
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/AnalyticsService.php';
require_once __DIR__ . '/../services/BudgetService.php';
require_once __DIR__ . '/../services/ExpenseService.php';
require_once __DIR__ . '/../services/AnomalyDetectionService.php';

requireAuth();

$userId = getAuthUserId();
$user = getAuthUser();
$currency = getAuthCurrency();

// Date Range Filter Handling
$period = $_GET['period'] ?? 'this_month';
$customStart = $_GET['start_date'] ?? null;
$customEnd = $_GET['end_date'] ?? null;

$range = getDateRangeForPeriod($period, $customStart, $customEnd);
$startDate = $range['start'];
$endDate = $range['end'];
$daysInPeriod = $range['days'];

// Initialize services
$analytics = new AnalyticsService();
$budgetService = new BudgetService();
$expenseService = new ExpenseService();
$anomalyService = new AnomalyDetectionService();

// Run real-time detection on the current period transactions
$anomalyService->scanAndDetectAnomalies($userId, $startDate, $endDate);
$pendingAnomalies = $anomalyService->getAnomalies($userId, 'pending');

// Fetch real metrics from MySQL
$summary = $analytics->getSummaryMetrics($userId, $startDate, $endDate, $daysInPeriod);
$categoriesData = $analytics->getCategoryBreakdown($userId, $startDate, $endDate);
$dailyTrend = $analytics->getDailyTrend($userId, $startDate, $endDate);
$monthlyTrend = $analytics->getMonthlyTrend($userId, 6);
$paymentMethods = $analytics->getPaymentMethodDistribution($userId, $startDate, $endDate);
$comparison = $analytics->compareWithPreviousPeriod($userId, $startDate, $endDate);
$activeBudgets = $budgetService->getActiveBudgetsWithUtilization($userId, $endDate);

// Fetch latest recent transactions
$recentExpenses = $expenseService->getFilteredExpenses($userId, [], 1, 6)['records'];

$pageTitle = 'Dashboard';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Dashboard Top Header Bar with Date Period Selector -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Welcome back, <?= e(explode(' ', $user['full_name'] ?? 'User')[0]) ?>!</h4>
        <p class="text-secondary fs-7 mb-0">Here is your spending analysis for <strong class="text-dark"><?= e($range['label']) ?></strong></p>
    </div>

    <!-- Date Range Filter Form -->
    <form method="GET" action="<?= url('dashboard/index.php') ?>" class="d-flex flex-wrap align-items-center gap-2 date-filter-bar">
        <select name="period" id="filter-period" class="form-select form-select-sm shadow-sm" style="min-width: 160px;">
            <option value="this_week" <?= $period === 'this_week' ? 'selected' : '' ?>>This Week</option>
            <option value="this_month" <?= $period === 'this_month' ? 'selected' : '' ?>>This Month</option>
            <option value="previous_month" <?= $period === 'previous_month' ? 'selected' : '' ?>>Previous Month</option>
            <option value="last_3_months" <?= $period === 'last_3_months' ? 'selected' : '' ?>>Last 3 Months</option>
            <option value="this_year" <?= $period === 'this_year' ? 'selected' : '' ?>>This Year</option>
            <option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Range...</option>
        </select>

        <div id="custom-date-container" class="d-flex align-items-center gap-2 <?= $period === 'custom' ? '' : 'd-none' ?>">
            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
            <span class="text-muted fs-7">to</span>
            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
            <button type="submit" class="btn btn-sm btn-ebap">Apply</button>
        </div>

        <div class="d-flex gap-1.5">
            <a href="<?= url('expenses/add.php') ?>" class="btn btn-sm btn-ebap shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Add Expense
            </a>
            <a href="<?= url('income/add.php') ?>" class="btn btn-sm btn-ebap-outline shadow-sm">
                <i class="bi bi-wallet2 me-1"></i> Add Income
            </a>
        </div>
    </form>
</div>

<!-- Pending Anomaly Alert Notification Banner -->
<?php if (!empty($pendingAnomalies)): ?>
<div class="alert alert-warning border border-warning-subtle shadow-sm rounded-3 py-2.5 px-3 mb-4 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2 fs-7">
        <i class="bi bi-shield-exclamation text-warning fs-5"></i>
        <span><strong><?= count($pendingAnomalies) ?> spending outlier(s)</strong> detected during this period warranting your review.</span>
    </div>
    <a href="<?= url('analytics/anomalies.php') ?>" class="btn btn-sm btn-warning text-dark fw-semibold px-3 py-1">Review Outliers</a>
</div>
<?php endif; ?>

<!-- Empty Database Onboarding State -->
<?php if ($summary['transaction_count'] === 0 && $summary['income_count'] === 0): ?>
<div class="ebap-card p-5 text-center bg-white mb-4">
    <div class="rounded-circle bg-primary-subtle text-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
        <i class="bi bi-stars fs-2"></i>
    </div>
    <h4 class="fw-bold text-dark mb-2">No Transactions Recorded Yet</h4>
    <p class="text-secondary mx-auto mb-4" style="max-width: 520px; font-size: 0.9rem;">
        Your analytical workspace is ready. Once you record your first income or expense, EBAP will automatically calculate spending velocity, category distributions, and behavioural patterns.
    </p>
    <div class="d-flex justify-content-center gap-3">
        <a href="<?= url('expenses/add.php') ?>" class="btn btn-ebap px-4 py-2">
            <i class="bi bi-receipt me-1.5"></i> Log First Expense
        </a>
        <a href="<?= url('import/index.php') ?>" class="btn btn-ebap-outline px-4 py-2">
            <i class="bi bi-cloud-arrow-up me-1.5"></i> Import CSV Transactions
        </a>
    </div>
</div>
<?php endif; ?>

<!-- 8 Financial Summary Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Expenses -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Total Expenses</span>
                    <div class="stat-value text-danger"><?= formatCurrency($summary['total_expenses']) ?></div>
                    <div class="stat-sub">
                        Across <?= $summary['transaction_count'] ?> transaction(s)
                    </div>
                </div>
                <div class="stat-icon bg-danger-subtle text-danger">
                    <i class="bi bi-arrow-up-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Income -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Total Income</span>
                    <div class="stat-value text-success"><?= formatCurrency($summary['total_income']) ?></div>
                    <div class="stat-sub">
                        Across <?= $summary['income_count'] ?> source credit(s)
                    </div>
                </div>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-arrow-down-left"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Net Cash Flow -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Net Cash Flow</span>
                    <div class="stat-value <?= $summary['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= ($summary['net_cash_flow'] >= 0 ? '+' : '') . formatCurrency($summary['net_cash_flow']) ?>
                    </div>
                    <div class="stat-sub">
                        <?= $summary['net_cash_flow'] >= 0 ? 'Surplus recorded' : 'Deficit recorded' ?>
                    </div>
                </div>
                <div class="stat-icon <?= $summary['net_cash_flow'] >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                    <i class="bi bi-wallet2"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Remaining Budget -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Remaining Budget</span>
                    <div class="stat-value <?= ($summary['remaining_budget'] ?? 0) >= 0 ? 'text-primary' : 'text-danger' ?>">
                        <?= $summary['remaining_budget'] !== null ? formatCurrency($summary['remaining_budget']) : 'No Limit Set' ?>
                    </div>
                    <div class="stat-sub">
                        <?= $summary['budget_utilization_pct'] !== null ? $summary['budget_utilization_pct'] . '% used' : 'Overall limit' ?>
                    </div>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-calculator"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 5: Average Daily Spending -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Avg Daily Spending</span>
                    <div class="stat-value text-dark"><?= formatCurrency($summary['avg_daily_spend']) ?></div>
                    <div class="stat-sub">Across <?= $daysInPeriod ?> calendar day(s)</div>
                </div>
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-calendar-event"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 6: Highest Spending Category -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Top Category</span>
                    <div class="stat-value text-dark text-truncate" style="max-width: 180px; font-size: 1.35rem;">
                        <?= e($summary['highest_category']['name'] ?? 'N/A') ?>
                    </div>
                    <div class="stat-sub">
                        <?= !empty($summary['highest_category']) ? formatCurrency($summary['highest_category']['amount']) . ' (' . $summary['highest_category']['share_pct'] . '%)' : 'No records' ?>
                    </div>
                </div>
                <div class="stat-icon bg-warning-subtle text-warning">
                    <i class="bi bi-pie-chart"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 7: Average Transaction -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Avg Transaction</span>
                    <div class="stat-value text-dark"><?= formatCurrency($summary['avg_tx_amount']) ?></div>
                    <div class="stat-sub">Median: <?= formatCurrency($summary['median_tx_amount']) ?></div>
                </div>
                <div class="stat-icon bg-secondary-subtle text-secondary">
                    <i class="bi bi-receipt"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 8: Recurring Share -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="stat-label">Recurring Share</span>
                    <div class="stat-value text-dark"><?= $summary['recurring_share_pct'] ?>%</div>
                    <div class="stat-sub"><?= formatCurrency($summary['recurring_expenses_total']) ?> total recurring</div>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Dashboard Visualizations Row -->
<div class="row g-4 mb-4">
    <!-- Daily Spending Trend Line Chart -->
    <div class="col-lg-8">
        <div class="ebap-card p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0 fs-6">Daily Spending Trend</h6>
                    <small class="text-muted">Day-by-day expenditure pattern across the period</small>
                </div>
                <span class="badge bg-light text-secondary border font-monospace"><?= $startDate ?> &rarr; <?= $endDate ?></span>
            </div>
            <div style="height: 280px;">
                <canvas id="dailyTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Category Distribution Doughnut Chart -->
    <div class="col-lg-4">
        <div class="ebap-card p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0 fs-6">Category Breakdown</h6>
                    <small class="text-muted">Proportional spending share</small>
                </div>
            </div>
            <div style="height: 240px; position: relative;">
                <canvas id="categoryDoughnutChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Second Visualization Row: Monthly Comparison & Budget Utilization -->
<div class="row g-4 mb-4">
    <!-- 6-Month Income vs Expense Trend -->
    <div class="col-lg-6">
        <div class="ebap-card p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0 fs-6">Monthly Income vs. Expense Trend</h6>
                    <small class="text-muted">Cash flow momentum over past 6 months</small>
                </div>
            </div>
            <div style="height: 250px;">
                <canvas id="monthlyIncomeExpenseChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Budget Performance -->
    <div class="col-lg-6">
        <div class="ebap-card p-4 h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0 fs-6">Active Budget Utilization</h6>
                    <small class="text-muted">Real-time spending vs configured spending limits</small>
                </div>
                <a href="<?= url('budgets/index.php') ?>" class="text-primary text-decoration-none fs-7 fw-semibold">Manage &rarr;</a>
            </div>

            <?php if (empty($activeBudgets)): ?>
                <div class="text-center py-4 text-muted fs-7">
                    <i class="bi bi-calculator fs-2 text-secondary d-block mb-2"></i>
                    No active budgets configured for this date range.
                    <div class="mt-2">
                        <a href="<?= url('budgets/add.php') ?>" class="btn btn-sm btn-ebap">Create Monthly Budget</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($activeBudgets as $b): ?>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1 fs-7">
                                <span class="fw-semibold text-dark">
                                    <?= e($b['budget_name']) ?>
                                    <small class="text-muted fw-normal">(<?= e($b['category_name'] ?? 'Overall') ?>)</small>
                                </span>
                                <span class="badge bg-<?= $b['status_level'] ?>-subtle text-<?= $b['status_level'] ?> border border-<?= $b['status_level'] ?>-subtle">
                                    <?= $b['utilization_pct'] ?>% used
                                </span>
                            </div>
                            <div class="ebap-progress">
                                <div class="progress-bar bg-<?= $b['status_level'] ?>" style="width: <?= min(100, $b['utilization_pct']) ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.75rem;">
                                <span class="text-muted">Spent: <?= formatCurrency($b['actual_spent']) ?></span>
                                <span class="text-muted">Limit: <?= formatCurrency($b['budget_amount']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Transactions Table & Quick Actions -->
<div class="ebap-card bg-white p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold text-dark mb-0 fs-6">Recent Recorded Transactions</h6>
            <small class="text-muted">Latest expense entries logged to your account</small>
        </div>
        <a href="<?= url('expenses/index.php') ?>" class="btn btn-sm btn-ebap-outline">
            View All Transactions &rarr;
        </a>
    </div>

    <?php if (empty($recentExpenses)): ?>
        <p class="text-muted text-center py-4 mb-0 fs-7">No transactions recorded yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ebap-table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Title / Merchant</th>
                        <th>Category</th>
                        <th>Payment Method</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentExpenses as $rx): ?>
                        <tr>
                            <td class="text-muted text-nowrap fs-7 font-monospace"><?= formatDate($rx['transaction_date']) ?></td>
                            <td>
                                <strong class="text-dark d-block"><?= e($rx['title']) ?></strong>
                                <?php if (!empty($rx['merchant'])): ?>
                                    <small class="text-muted"><i class="bi bi-shop me-1"></i><?= e($rx['merchant']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="category-badge" style="background-color: <?= e($rx['category_color']) ?>18; color: <?= e($rx['category_color']) ?>;">
                                    <i class="bi <?= e($rx['category_icon']) ?> me-1"></i> <?= e($rx['category_name']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($rx['payment_method']) ?></span>
                            </td>
                            <td class="text-end fw-bold text-dark font-monospace">
                                <?= formatCurrency($rx['amount']) ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= url('expenses/edit.php?id=' . $rx['id']) ?>" class="btn btn-sm btn-light border p-1 px-2" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Chart Initialization Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const symbol = '<?= getCurrencySymbol($currency) ?>';

    // 1. Daily Trend Line Chart
    const dailyLabels = <?= json_encode(array_column($dailyTrend, 'formatted_date')) ?>;
    const dailyAmounts = <?= json_encode(array_column($dailyTrend, 'amount')) ?>;
    EBAPCharts.renderDailyTrend('dailyTrendChart', dailyLabels, dailyAmounts, symbol);

    // 2. Category Doughnut Chart
    const catLabels = <?= json_encode(array_column($categoriesData['categories'], 'category_name')) ?>;
    const catAmounts = <?= json_encode(array_column($categoriesData['categories'], 'total_amount')) ?>;
    const catColors = <?= json_encode(array_column($categoriesData['categories'], 'category_color')) ?>;
    EBAPCharts.renderCategoryDoughnut('categoryDoughnutChart', catLabels, catAmounts, catColors, symbol);

    // 3. Monthly Income vs Expense Grouped Bar Chart
    const mLabels = <?= json_encode(array_column($monthlyTrend, 'label')) ?>;
    const mIncome = <?= json_encode(array_column($monthlyTrend, 'income')) ?>;
    const mExpense = <?= json_encode(array_column($monthlyTrend, 'expense')) ?>;
    EBAPCharts.renderIncomeVsExpense('monthlyIncomeExpenseChart', mLabels, mIncome, mExpense, symbol);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
