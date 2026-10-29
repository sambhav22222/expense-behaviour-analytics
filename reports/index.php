<?php
declare(strict_types=1);

/**
 * Report Generation & Print/Export Workspace
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/ReportService.php';

requireAuth();

$userId = getAuthUserId();
$currency = getAuthCurrency();

$reportType = $_GET['type'] ?? 'comprehensive_monthly';
$period = $_GET['period'] ?? 'this_month';
$customStart = $_GET['start_date'] ?? null;
$customEnd = $_GET['end_date'] ?? null;

$range = getDateRangeForPeriod($period, $customStart, $customEnd);
$reportService = new ReportService();
$reportData = $reportService->generateReportData($userId, $reportType, $range['start'], $range['end'], $range['days']);

$pageTitle = 'Financial Reports & Exports';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Bar & Controls -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
    <div>
        <h4 class="fw-bold text-dark mb-1">Financial Reports & Exports</h4>
        <p class="text-secondary fs-7 mb-0">Generate verifiable summary reports for academic presentation, personal records, or CSV export.</p>
    </div>

    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-light border shadow-sm" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print / PDF
        </button>
        <a href="<?= url('reports/export.php?type=expenses&start_date=' . $range['start'] . '&end_date=' . $range['end']) ?>" class="btn btn-sm btn-ebap shadow-sm">
            <i class="bi bi-download me-1"></i> Export Transactions CSV
        </a>
    </div>
</div>

<!-- Report Selection Filter Bar -->
<div class="ebap-card bg-white p-3.5 mb-4 shadow-sm no-print">
    <form method="GET" action="<?= url('reports/index.php') ?>" class="row g-2.5 align-items-end">
        <div class="col-md-5">
            <label class="form-label fs-7 fw-semibold text-secondary mb-1">Report Type</label>
            <select name="type" class="form-select form-select-sm">
                <option value="comprehensive_monthly" <?= $reportType === 'comprehensive_monthly' ? 'selected' : '' ?>>1. Comprehensive Monthly Financial Report</option>
                <option value="income_vs_expense" <?= $reportType === 'income_vs_expense' ? 'selected' : '' ?>>2. Income vs. Expense Cash Flow Report</option>
                <option value="category_breakdown" <?= $reportType === 'category_breakdown' ? 'selected' : '' ?>>3. Category-wise Spending Breakdown Report</option>
                <option value="budget_performance" <?= $reportType === 'budget_performance' ? 'selected' : '' ?>>4. Budget Performance & Variance Analysis</option>
                <option value="recurring_expenses" <?= $reportType === 'recurring_expenses' ? 'selected' : '' ?>>5. Recurring Subscriptions & Fixed Costs</option>
                <option value="goals_progress" <?= $reportType === 'goals_progress' ? 'selected' : '' ?>>6. Financial Goals & Target Milestone Progress</option>
                <option value="anomalies_review" <?= $reportType === 'anomalies_review' ? 'selected' : '' ?>>7. Spending Anomaly Detection & Review Log</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label fs-7 fw-semibold text-secondary mb-1">Time Period</label>
            <select name="period" class="form-select form-select-sm">
                <option value="this_week" <?= $period === 'this_week' ? 'selected' : '' ?>>This Week</option>
                <option value="this_month" <?= $period === 'this_month' ? 'selected' : '' ?>>This Month</option>
                <option value="previous_month" <?= $period === 'previous_month' ? 'selected' : '' ?>>Previous Month</option>
                <option value="last_3_months" <?= $period === 'last_3_months' ? 'selected' : '' ?>>Last 3 Months</option>
                <option value="this_year" <?= $period === 'this_year' ? 'selected' : '' ?>>This Year</option>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-ebap w-100">
                <i class="bi bi-funnel me-1"></i> Generate
            </button>
        </div>
    </form>
</div>

<!-- Printable Report Canvas -->
<div class="ebap-card bg-white p-4 p-md-5 mb-4 shadow-sm" id="printable-report">
    <!-- Formal Report Header Banner -->
    <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
        <div>
            <h4 class="fw-bold text-dark mb-0"><?= e($reportData['title']) ?></h4>
            <span class="text-muted fs-7">
                User: <strong><?= e($_SESSION['user']['full_name']) ?></strong> (<?= e($_SESSION['user']['email']) ?>) | Period: <strong><?= e($range['label']) ?></strong>
            </span>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-primary border font-monospace">EBAP v1.0</span>
            <small class="text-muted d-block mt-1 font-monospace" style="font-size: 0.72rem;">Generated: <?= date('Y-m-d H:i') ?></small>
        </div>
    </div>

    <!-- Switch Report Contents Based on Type -->
    <?php if ($reportType === 'comprehensive_monthly'): ?>
        <?php $sum = $reportData['summary']; ?>
        <!-- Summary Numbers -->
        <div class="row g-3 mb-4">
            <div class="col-sm-3">
                <div class="p-3 bg-light rounded-3 border">
                    <small class="text-muted d-block" style="font-size: 0.7rem;">TOTAL INCOME</small>
                    <span class="fw-bold text-success fs-5"><?= formatCurrency($sum['total_income']) ?></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="p-3 bg-light rounded-3 border">
                    <small class="text-muted d-block" style="font-size: 0.7rem;">TOTAL EXPENSES</small>
                    <span class="fw-bold text-danger fs-5"><?= formatCurrency($sum['total_expenses']) ?></span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="p-3 bg-light rounded-3 border">
                    <small class="text-muted d-block" style="font-size: 0.7rem;">NET CASH FLOW</small>
                    <span class="fw-bold <?= $sum['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' ?> fs-5">
                        <?= ($sum['net_cash_flow'] >= 0 ? '+' : '') . formatCurrency($sum['net_cash_flow']) ?>
                    </span>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="p-3 bg-light rounded-3 border">
                    <small class="text-muted d-block" style="font-size: 0.7rem;">AVG DAILY SPEND</small>
                    <span class="fw-bold text-dark fs-5"><?= formatCurrency($sum['avg_daily_spend']) ?></span>
                </div>
            </div>
        </div>

        <h6 class="fw-bold text-dark mb-2 fs-6">Category Breakdown Table</h6>
        <div class="table-responsive mb-4">
            <table class="table ebap-table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Type</th>
                        <th class="text-end">Transactions</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Share %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['categories'] as $c): ?>
                        <tr>
                            <td><?= e($c['category_name']) ?></td>
                            <td><?= $c['is_essential'] ? 'Essential' : 'Discretionary' ?></td>
                            <td class="text-end font-monospace"><?= $c['tx_count'] ?></td>
                            <td class="text-end font-monospace fw-semibold"><?= formatCurrency($c['total_amount']) ?></td>
                            <td class="text-end font-monospace"><?= $c['share_pct'] ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($reportType === 'budget_performance'): ?>
        <h6 class="fw-bold text-dark mb-3 fs-6">Configured Budgets vs Actual Expenditure</h6>
        <div class="table-responsive">
            <table class="table ebap-table">
                <thead>
                    <tr>
                        <th>Budget Name</th>
                        <th>Category Scope</th>
                        <th class="text-end">Limit</th>
                        <th class="text-end">Actual Spent</th>
                        <th class="text-end">Remaining</th>
                        <th class="text-end">Utilization %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['budgets'] as $b): ?>
                        <tr>
                            <td class="fw-semibold text-dark"><?= e($b['budget_name']) ?></td>
                            <td><?= !empty($b['category_name']) ? e($b['category_name']) : 'Overall Monthly' ?></td>
                            <td class="text-end font-monospace"><?= formatCurrency($b['budget_amount']) ?></td>
                            <td class="text-end font-monospace text-danger"><?= formatCurrency($b['actual_spent']) ?></td>
                            <td class="text-end font-monospace <?= $b['remaining_amount'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= formatCurrency($b['remaining_amount']) ?>
                            </td>
                            <td class="text-end font-monospace fw-bold"><?= $b['utilization_pct'] ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($reportType === 'recurring_expenses'): ?>
        <h6 class="fw-bold text-dark mb-3 fs-6">Subscriptions & Recurring Fixed Obligations</h6>
        <div class="table-responsive">
            <table class="table ebap-table">
                <thead>
                    <tr>
                        <th>Subscription</th>
                        <th>Category</th>
                        <th>Cycle</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Normalized Monthly</th>
                        <th>Next Due</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['items'] as $item): ?>
                        <tr>
                            <td class="fw-semibold text-dark"><?= e($item['title']) ?></td>
                            <td><?= e($item['category_name']) ?></td>
                            <td><?= ucfirst($item['frequency']) ?></td>
                            <td class="text-end font-monospace"><?= formatCurrency($item['amount']) ?></td>
                            <td class="text-end font-monospace fw-bold text-primary"><?= formatCurrency($item['monthly_normalized']) ?>/mo</td>
                            <td class="font-monospace text-muted"><?= formatDate($item['next_due_date']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php elseif ($reportType === 'anomalies_review'): ?>
        <h6 class="fw-bold text-dark mb-3 fs-6">Spending Anomaly Detection Log</h6>
        <div class="table-responsive">
            <table class="table ebap-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Detection Rule</th>
                        <th class="text-end">Baseline</th>
                        <th class="text-end">Observed</th>
                        <th>Explanation</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData['anomalies'] as $a): ?>
                        <tr>
                            <td class="font-monospace"><?= formatDate($a['transaction_date'] ?? $a['created_at']) ?></td>
                            <td><?= e($a['detection_method']) ?></td>
                            <td class="text-end font-monospace"><?= formatCurrency($a['baseline_value']) ?></td>
                            <td class="text-end font-monospace fw-bold text-danger"><?= formatCurrency($a['observed_value']) ?></td>
                            <td class="fs-7 text-secondary"><?= e($a['explanation']) ?></td>
                            <td><?= ucfirst($a['review_status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <p class="text-muted">Report generated with verified data.</p>
    <?php endif; ?>

    <!-- Academic Disclaimer Footer in Printable Report -->
    <div class="mt-5 pt-3 border-top text-center text-muted fs-7">
        <p class="mb-0">
            <strong>Expense Behaviour Analytical Platform (EBAP)</strong> &bull; Developed for Academic BCA Project Evaluation &bull; Strictly Educational & Personal Analytics
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
