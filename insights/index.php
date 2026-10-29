<?php
declare(strict_types=1);

/**
 * Dedicated Behavioural Insights & Recommendations Workspace
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

$period = $_GET['period'] ?? 'this_month';
$range = getDateRangeForPeriod($period);

$analytics = new AnalyticsService();
$insightEngine = new BehaviourInsightService($analytics);
$insights = $insightEngine->generateInsights($userId, $range['start'], $range['end'], $range['days']);

$pageTitle = 'Behavioural Insights & Habits';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Behavioural Insights & Recommendations</h4>
        <p class="text-secondary fs-7 mb-0">Discover spending habits, category momentum, and actionable adjustments derived from your records.</p>
    </div>

    <form method="GET" action="<?= url('insights/index.php') ?>" class="d-flex align-items-center gap-2">
        <select name="period" onchange="this.form.submit()" class="form-select form-select-sm">
            <option value="this_week" <?= $period === 'this_week' ? 'selected' : '' ?>>This Week</option>
            <option value="this_month" <?= $period === 'this_month' ? 'selected' : '' ?>>This Month</option>
            <option value="previous_month" <?= $period === 'previous_month' ? 'selected' : '' ?>>Previous Month</option>
            <option value="last_3_months" <?= $period === 'last_3_months' ? 'selected' : '' ?>>Last 3 Months</option>
            <option value="this_year" <?= $period === 'this_year' ? 'selected' : '' ?>>This Year</option>
        </select>
    </form>
</div>

<div class="row g-4">
    <?php foreach ($insights as $in): ?>
        <div class="col-md-6 col-lg-6">
            <div class="ebap-card p-4 h-100 bg-white border-start border-4 border-<?= e($in['type']) ?>">
                <div class="d-flex justify-content-between align-items-start mb-2.5">
                    <span class="badge bg-<?= e($in['type']) ?>-subtle text-<?= e($in['type']) ?> border border-<?= e($in['type']) ?>-subtle px-2.5 py-1">
                        <i class="bi <?= e($in['icon']) ?> me-1"></i> <?= e($in['badge']) ?>
                    </span>
                    <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.72rem;">
                        Confidence: <?= e($in['confidence']) ?>
                    </span>
                </div>

                <h5 class="fw-bold text-dark mb-2 fs-6"><?= e($in['title']) ?></h5>
                <p class="text-secondary fs-7 mb-3"><?= e($in['description']) ?></p>

                <!-- Mathematical evidence card -->
                <div class="insight-calc-box mb-3">
                    <small class="text-muted d-block fw-semibold" style="font-size: 0.7rem;">MATHEMATICAL DERIVATION:</small>
                    <code><?= e($in['calculation']) ?></code>
                </div>

                <?php if (!empty($in['suggested_action'])): ?>
                    <div class="p-2.5 bg-light rounded-3 border d-flex align-items-start gap-2">
                        <i class="bi bi-lightbulb-fill text-warning mt-0.5 fs-6"></i>
                        <div class="fs-7">
                            <strong class="text-dark">Suggested Action:</strong>
                            <span class="text-secondary d-block"><?= e($in['suggested_action']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
