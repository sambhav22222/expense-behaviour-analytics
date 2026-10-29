<?php
declare(strict_types=1);

/**
 * Expense Behaviour Analytical Workspace (EBAP Central Module)
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/AnalyticsService.php';
require_once __DIR__ . '/../services/BehaviourInsightService.php';

requireAuth();

$userId = getAuthUserId();
$currency = getAuthCurrency();

// Date Range
$period = $_GET['period'] ?? 'this_month';
$customStart = $_GET['start_date'] ?? null;
$customEnd = $_GET['end_date'] ?? null;

$range = getDateRangeForPeriod($period, $customStart, $customEnd);
$startDate = $range['start'];
$endDate = $range['end'];
$daysInPeriod = $range['days'];

$analytics = new AnalyticsService();
$insightEngine = new BehaviourInsightService($analytics);

// Fetch analytical computations
$summary = $analytics->getSummaryMetrics($userId, $startDate, $endDate, $daysInPeriod);
$categoriesData = $analytics->getCategoryBreakdown($userId, $startDate, $endDate);
$essentialVsDisc = $analytics->getEssentialVsDiscretionary($userId, $startDate, $endDate);
$weekdayVsWeekend = $analytics->getWeekdayVsWeekend($userId, $startDate, $endDate);
$comparison = $analytics->compareWithPreviousPeriod($userId, $startDate, $endDate);
$insights = $insightEngine->generateInsights($userId, $startDate, $endDate, $daysInPeriod);

// Spending Concentration: Top 3 Categories Share
$top3Share = 0.0;
$top3Categories = array_slice($categoriesData['categories'], 0, 3);
if ($summary['total_expenses'] > 0) {
    $top3Amt = array_sum(array_column($top3Categories, 'total_amount'));
    $top3Share = round(($top3Amt / $summary['total_expenses']) * 100, 1);
}

// Income-to-Expense Ratio
$incomeToExpenseRatio = ($summary['total_expenses'] > 0)
    ? round($summary['total_income'] / $summary['total_expenses'], 2)
    : ($summary['total_income'] > 0 ? 999.0 : 0.0);

$pageTitle = 'Expense Behaviour Analytics';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Module Header & Date Period Filter -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Expense Behaviour Analytics</h4>
        <p class="text-secondary fs-7 mb-0">Transforming raw transaction records into understandable behavioural spending patterns.</p>
    </div>

    <form method="GET" action="<?= url('analytics/index.php') ?>" class="d-flex flex-wrap align-items-center gap-2 date-filter-bar">
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
    </form>
</div>

<!-- Key Behavioural Metrics Grid -->
<div class="row g-3 mb-4">
    <!-- Mean vs Median -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-label">Average vs. Median</span>
            <div class="stat-value text-dark"><?= formatCurrency($summary['avg_tx_amount']) ?></div>
            <div class="stat-sub">
                Median: <strong><?= formatCurrency($summary['median_tx_amount']) ?></strong>
                <?php if ($summary['median_tx_amount'] > 0 && ($summary['avg_tx_amount'] / $summary['median_tx_amount'] > 1.4)): ?>
                    <span class="badge bg-warning-subtle text-warning border ms-1">High Skew</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Spending Concentration (Top 3 %) -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-label">Top 3 Concentration</span>
            <div class="stat-value <?= $top3Share > 70 ? 'text-danger' : 'text-primary' ?>"><?= $top3Share ?>%</div>
            <div class="stat-sub">
                Share of top 3 spending categories
            </div>
        </div>
    </div>

    <!-- Essential vs Discretionary Ratio -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-label">Essential Spending</span>
            <div class="stat-value text-success"><?= $essentialVsDisc['essential_pct'] ?>%</div>
            <div class="stat-sub">
                Discretionary: <strong><?= $essentialVsDisc['discretionary_pct'] ?>%</strong>
            </div>
        </div>
    </div>

    <!-- Weekend Clustering Share -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-label">Weekend Spending Bias</span>
            <div class="stat-value <?= $weekdayVsWeekend['weekend_pct'] > 38 ? 'text-warning' : 'text-dark' ?>">
                <?= $weekdayVsWeekend['weekend_pct'] ?>%
            </div>
            <div class="stat-sub">
                <?= $weekdayVsWeekend['weekend_count'] ?> weekend transactions
            </div>
        </div>
    </div>
</div>

<!-- Behavioural Insights & Actionable Observations -->
<div class="ebap-card bg-white p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold text-dark mb-0 fs-6">Generated Behavioural Insights</h5>
            <small class="text-muted">Calculated using deterministic mathematical rules from your recorded transactions</small>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
            <?= count($insights) ?> Insight(s) Computed
        </span>
    </div>

    <div class="row g-3">
        <?php foreach ($insights as $in): ?>
            <div class="col-md-6">
                <div class="insight-card insight-<?= e($in['type']) ?> h-100 mb-0">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-<?= e($in['type']) ?>-subtle text-<?= e($in['type']) ?> border border-<?= e($in['type']) ?>-subtle px-2 py-1">
                            <i class="bi <?= e($in['icon']) ?> me-1"></i> <?= e($in['badge']) ?>
                        </span>
                        <small class="text-muted font-monospace" style="font-size: 0.72rem;">Confidence: <?= e($in['confidence']) ?></small>
                    </div>
                    <h6 class="fw-bold text-dark mb-1"><?= e($in['title']) ?></h6>
                    <p class="text-secondary fs-7 mb-2"><?= e($in['description']) ?></p>

                    <!-- Exact Mathematical Calculation Transparency Box -->
                    <div class="insight-calc-box">
                        <small class="text-muted d-block fw-semibold" style="font-size: 0.7rem;">CALCULATION EVIDENCE:</small>
                        <code><?= e($in['calculation']) ?></code>
                    </div>

                    <?php if (!empty($in['suggested_action'])): ?>
                        <div class="mt-2 pt-2 border-top text-dark fs-7 d-flex align-items-start gap-1.5">
                            <i class="bi bi-lightbulb text-warning mt-0.5"></i>
                            <div><strong>Suggested Action:</strong> <span class="text-secondary"><?= e($in['suggested_action']) ?></span></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Detailed Behavioural Breakdowns -->
<div class="row g-4 mb-4">
    <!-- Essential vs Discretionary Quality Breakdown -->
    <div class="col-lg-6">
        <div class="ebap-card p-4 h-100 bg-white">
            <h6 class="fw-bold text-dark mb-1 fs-6">Essential vs. Discretionary Spending</h6>
            <p class="text-muted fs-7 mb-3">Survival needs (Groceries, Housing, Utilities, Transport, Medical) vs Lifestyle choices</p>
            
            <div class="row g-3 align-items-center">
                <div class="col-sm-6 text-center">
                    <div style="height: 180px;">
                        <canvas id="essentialDiscChart"></canvas>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-2.5 bg-light rounded-3 mb-2 border">
                        <small class="text-muted d-block" style="font-size: 0.72rem;">ESSENTIAL NEEDS</small>
                        <span class="fw-bold text-success fs-6"><?= formatCurrency($essentialVsDisc['essential_amount']) ?></span>
                        <span class="badge bg-success-subtle text-success ms-1"><?= $essentialVsDisc['essential_pct'] ?>%</span>
                        <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;"><?= $essentialVsDisc['essential_count'] ?> transaction(s)</small>
                    </div>
                    <div class="p-2.5 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.72rem;">DISCRETIONARY LIFESTYLE</small>
                        <span class="fw-bold text-warning fs-6"><?= formatCurrency($essentialVsDisc['discretionary_amount']) ?></span>
                        <span class="badge bg-warning-subtle text-warning ms-1"><?= $essentialVsDisc['discretionary_pct'] ?>%</span>
                        <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;"><?= $essentialVsDisc['discretionary_count'] ?> transaction(s)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekday vs Weekend Behavioral Pattern -->
    <div class="col-lg-6">
        <div class="ebap-card p-4 h-100 bg-white">
            <h6 class="fw-bold text-dark mb-1 fs-6">Weekday vs. Weekend Spending Timing</h6>
            <p class="text-muted fs-7 mb-3">Identifies if leisure and outings cause weekend spending acceleration</p>

            <div class="row g-3 align-items-center">
                <div class="col-sm-6 text-center">
                    <div style="height: 180px;">
                        <canvas id="weekdayWeekendChart"></canvas>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-2.5 bg-light rounded-3 mb-2 border">
                        <small class="text-muted d-block" style="font-size: 0.72rem;">WEEKDAYS (MON - FRI)</small>
                        <span class="fw-bold text-primary fs-6"><?= formatCurrency($weekdayVsWeekend['weekday_amount']) ?></span>
                        <span class="badge bg-primary-subtle text-primary ms-1"><?= $weekdayVsWeekend['weekday_pct'] ?>%</span>
                        <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;"><?= $weekdayVsWeekend['weekday_count'] ?> transactions across 5 days/wk</small>
                    </div>
                    <div class="p-2.5 bg-light rounded-3 border">
                        <small class="text-muted d-block" style="font-size: 0.72rem;">WEEKENDS (SAT - SUN)</small>
                        <span class="fw-bold text-secondary fs-6"><?= formatCurrency($weekdayVsWeekend['weekend_amount']) ?></span>
                        <span class="badge bg-secondary-subtle text-secondary ms-1"><?= $weekdayVsWeekend['weekend_pct'] ?>%</span>
                        <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;"><?= $weekdayVsWeekend['weekend_count'] ?> transactions across 2 days/wk</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category Ranking Table -->
<div class="ebap-card bg-white p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold text-dark mb-0 fs-6">Spending Category Breakdown & Share of Wallet</h6>
            <small class="text-muted">Ranked from highest to lowest expenditure</small>
        </div>
        <a href="<?= url('analytics/categories.php') ?>" class="btn btn-sm btn-ebap-outline">Detailed View &rarr;</a>
    </div>

    <div class="table-responsive">
        <table class="table ebap-table align-middle">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Transactions</th>
                    <th class="text-end">Avg Per Tx</th>
                    <th class="text-end">Total Spent</th>
                    <th class="text-end" style="width: 180px;">Share of Wallet</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categoriesData['categories'] as $cat): ?>
                    <tr>
                        <td>
                            <span class="category-badge" style="background-color: <?= e($cat['category_color']) ?>18; color: <?= e($cat['category_color']) ?>;">
                                <i class="bi <?= e($cat['category_icon']) ?> me-1"></i> <?= e($cat['category_name']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $cat['is_essential'] ? 'bg-success-subtle text-success' : 'bg-light text-secondary border' ?>">
                                <?= $cat['is_essential'] ? 'Essential' : 'Discretionary' ?>
                            </span>
                        </td>
                        <td class="font-monospace text-muted"><?= $cat['tx_count'] ?></td>
                        <td class="text-end font-monospace"><?= formatCurrency($cat['avg_amount']) ?></td>
                        <td class="text-end fw-bold font-monospace text-dark"><?= formatCurrency($cat['total_amount']) ?></td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <span class="font-monospace fs-7 fw-semibold"><?= $cat['share_pct'] ?>%</span>
                                <div class="ebap-progress" style="width: 80px;">
                                    <div class="progress-bar" style="width: <?= $cat['share_pct'] ?>%; background-color: <?= e($cat['category_color']) ?>;"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Charts Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const symbol = '<?= getCurrencySymbol($currency) ?>';

    // Essential vs Discretionary Doughnut
    const ctxEss = document.getElementById('essentialDiscChart');
    if (ctxEss) {
        new Chart(ctxEss, {
            type: 'doughnut',
            data: {
                labels: ['Essential Needs', 'Discretionary Lifestyle'],
                datasets: [{
                    data: [<?= $essentialVsDisc['essential_amount'] ?>, <?= $essentialVsDisc['discretionary_amount'] ?>],
                    backgroundColor: ['#10b981', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { legend: { display: false } }
            }
        });
    }

    // Weekday vs Weekend Doughnut
    const ctxWk = document.getElementById('weekdayWeekendChart');
    if (ctxWk) {
        new Chart(ctxWk, {
            type: 'doughnut',
            data: {
                labels: ['Weekdays (Mon-Fri)', 'Weekends (Sat-Sun)'],
                datasets: [{
                    data: [<?= $weekdayVsWeekend['weekday_amount'] ?>, <?= $weekdayVsWeekend['weekend_amount'] ?>],
                    backgroundColor: ['#4f46e5', '#06b6d4'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
