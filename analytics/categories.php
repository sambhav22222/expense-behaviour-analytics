<?php
declare(strict_types=1);

/**
 * Category Breakdown & Distribution Analytics
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
$customStart = $_GET['start_date'] ?? null;
$customEnd = $_GET['end_date'] ?? null;

$range = getDateRangeForPeriod($period, $customStart, $customEnd);
$analytics = new AnalyticsService();
$categoriesData = $analytics->getCategoryBreakdown($userId, $range['start'], $range['end']);

$pageTitle = 'Category Breakdown Analytics';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Category Spending Distribution</h4>
        <p class="text-secondary fs-7 mb-0">Detailed breakdown of expense distribution across spending categories for <?= e($range['label']) ?>.</p>
    </div>

    <form method="GET" action="<?= url('analytics/categories.php') ?>" class="d-flex align-items-center gap-2">
        <select name="period" onchange="this.form.submit()" class="form-select form-select-sm">
            <option value="this_month" <?= $period === 'this_month' ? 'selected' : '' ?>>This Month</option>
            <option value="previous_month" <?= $period === 'previous_month' ? 'selected' : '' ?>>Previous Month</option>
            <option value="last_3_months" <?= $period === 'last_3_months' ? 'selected' : '' ?>>Last 3 Months</option>
            <option value="this_year" <?= $period === 'this_year' ? 'selected' : '' ?>>This Year</option>
        </select>
    </form>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="ebap-card p-4 bg-white h-100">
            <h6 class="fw-bold text-dark mb-3 fs-6">Category Proportion</h6>
            <div style="height: 320px;">
                <canvas id="catChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="ebap-card p-4 bg-white h-100">
            <h6 class="fw-bold text-dark mb-3 fs-6">Category Breakdown Summary</h6>
            <div class="table-responsive">
                <table class="table ebap-table align-middle">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Transactions</th>
                            <th class="text-end">Average Tx</th>
                            <th class="text-end">Total Spent</th>
                            <th class="text-end">Share %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categoriesData['categories'] as $c): ?>
                            <tr>
                                <td>
                                    <span class="category-badge" style="background-color: <?= e($c['category_color']) ?>18; color: <?= e($c['category_color']) ?>;">
                                        <i class="bi <?= e($c['category_icon']) ?> me-1"></i> <?= e($c['category_name']) ?>
                                    </span>
                                </td>
                                <td class="font-monospace text-muted"><?= $c['tx_count'] ?></td>
                                <td class="text-end font-monospace"><?= formatCurrency($c['avg_amount']) ?></td>
                                <td class="text-end fw-bold font-monospace text-dark"><?= formatCurrency($c['total_amount']) ?></td>
                                <td class="text-end font-monospace fw-semibold"><?= $c['share_pct'] ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = <?= json_encode(array_column($categoriesData['categories'], 'category_name')) ?>;
    const amounts = <?= json_encode(array_column($categoriesData['categories'], 'total_amount')) ?>;
    const colors = <?= json_encode(array_column($categoriesData['categories'], 'category_color')) ?>;
    EBAPCharts.renderCategoryDoughnut('catChart', labels, amounts, colors, '<?= getCurrencySymbol($currency) ?>');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
