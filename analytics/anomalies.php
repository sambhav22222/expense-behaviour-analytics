<?php
declare(strict_types=1);

/**
 * Spending Anomaly Detection & Review Workspace
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/AnomalyDetectionService.php';

requireAuth();

$userId = getAuthUserId();
$currency = getAuthCurrency();
$anomalyService = new AnomalyDetectionService();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireCsrfToken();
    $action = $_POST['action'];

    if ($action === 'update_status') {
        $anomalyId = (int)($_POST['anomaly_id'] ?? 0);
        $status = $_POST['status'] ?? 'reviewed';
        if ($anomalyService->updateAnomalyStatus($userId, $anomalyId, $status)) {
            setFlash('success', 'Anomaly status updated to ' . ucfirst($status) . '.');
        } else {
            setFlash('error', 'Failed to update anomaly review status.');
        }
    } elseif ($action === 'scan') {
        // Run full scan over past 90 days
        $start = date('Y-m-d', strtotime('-90 days'));
        $end = date('Y-m-d');
        $found = $anomalyService->scanAndDetectAnomalies($userId, $start, $end);
        setFlash('info', "Scan complete: {$found} new outlier event(s) logged for review.");
    }

    header('Location: ' . url('analytics/anomalies.php'));
    exit;
}

$statusFilter = $_GET['status'] ?? null;
$anomalies = $anomalyService->getAnomalies($userId, $statusFilter);

$pageTitle = 'Spending Anomaly Detection';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Spending Anomaly Detection</h4>
        <p class="text-secondary fs-7 mb-0">Deterministic, rule-based detection of statistical deviations from your historical spending baseline.</p>
    </div>

    <form method="POST" action="<?= url('analytics/anomalies.php') ?>" class="d-flex gap-2">
        <?= getCsrfInput() ?>
        <input type="hidden" name="action" value="scan">
        <button type="submit" class="btn btn-sm btn-ebap shadow-sm">
            <i class="bi bi-arrow-repeat me-1"></i> Run Anomaly Detection Scan
        </button>
    </form>
</div>

<!-- Important Limitation & Transparency Notice -->
<div class="alert alert-info border border-info-subtle bg-info-subtle shadow-sm rounded-3 p-3.5 mb-4">
    <div class="d-flex align-items-start gap-2.5 fs-7 text-dark">
        <i class="bi bi-info-circle-fill text-info fs-5 mt-0.5"></i>
        <div>
            <strong>Deterministic Detection Rules & Academic Limitation:</strong>
            <p class="mb-0 text-secondary mt-1">
                EBAP evaluates your transactions against historical category medians, rolling daily averages, and rapid duplicate intervals. 
                Flagging a transaction signifies a <em>mathematical outlier</em> relative to your own past habits. It does <strong>not</strong> imply banking fraud, unauthorized access, or credit scoring issues. Records are never deleted or modified automatically.
            </p>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-pills mb-3 gap-2">
    <li class="nav-item">
        <a class="nav-link btn-sm <?= empty($statusFilter) ? 'active' : 'bg-light text-secondary border' ?>" href="<?= url('analytics/anomalies.php') ?>">
            All Events (<?= count($anomalies) ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link btn-sm <?= $statusFilter === 'pending' ? 'active' : 'bg-light text-secondary border' ?>" href="<?= url('analytics/anomalies.php?status=pending') ?>">
            Pending Review
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link btn-sm <?= $statusFilter === 'reviewed' ? 'active' : 'bg-light text-secondary border' ?>" href="<?= url('analytics/anomalies.php?status=reviewed') ?>">
            Reviewed & Verified
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link btn-sm <?= $statusFilter === 'false_positive' ? 'active' : 'bg-light text-secondary border' ?>" href="<?= url('analytics/anomalies.php?status=false_positive') ?>">
            Marked Normal
        </a>
    </li>
</ul>

<!-- Anomalies Table / Card Container -->
<div class="ebap-table-container mb-4">
    <?php if (empty($anomalies)): ?>
        <div class="text-center py-5">
            <i class="bi bi-shield-check fs-1 text-success d-block mb-2"></i>
            <h6 class="fw-semibold text-dark">No Anomalies Detected in Filtered View</h6>
            <p class="text-muted fs-7 mb-0">Your spending records conform closely with your historical category medians and typical transaction sizes.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ebap-table align-middle">
                <thead>
                    <tr>
                        <th>Date & Transaction</th>
                        <th>Category</th>
                        <th>Detection Rule</th>
                        <th class="text-end">Baseline</th>
                        <th class="text-end">Observed</th>
                        <th>Mathematical Explanation</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($anomalies as $a): ?>
                        <tr>
                            <td>
                                <strong class="text-dark d-block"><?= e($a['expense_title'] ?? 'Category Event') ?></strong>
                                <small class="text-muted font-monospace fs-7"><?= formatDate($a['transaction_date'] ?? $a['created_at']) ?></small>
                            </td>
                            <td>
                                <?php if (!empty($a['category_name'])): ?>
                                    <span class="category-badge" style="background-color: <?= e($a['category_color']) ?>18; color: <?= e($a['category_color']) ?>;">
                                        <i class="bi <?= e($a['category_icon']) ?> me-1"></i> <?= e($a['category_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle text-wrap" style="max-width: 150px;">
                                    <?= e($a['detection_method']) ?>
                                </span>
                            </td>
                            <td class="text-end font-monospace text-muted fs-7">
                                <?= formatCurrency($a['baseline_value']) ?>
                            </td>
                            <td class="text-end font-monospace fw-bold text-danger">
                                <?= formatCurrency($a['observed_value']) ?>
                            </td>
                            <td style="max-width: 320px;" class="fs-7 text-secondary">
                                <?= e($a['explanation']) ?>
                            </td>
                            <td>
                                <?php
                                $badgeClass = match ($a['review_status']) {
                                    'pending' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                    'reviewed' => 'bg-success-subtle text-success border border-success-subtle',
                                    'false_positive' => 'bg-secondary-subtle text-secondary border',
                                    default => 'bg-light text-dark border'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?> font-monospace">
                                    <?= ucfirst(str_replace('_', ' ', $a['review_status'])) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Review
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm fs-7">
                                        <li>
                                            <form method="POST" action="<?= url('analytics/anomalies.php') ?>">
                                                <?= getCsrfInput() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="anomaly_id" value="<?= $a['id'] ?>">
                                                <input type="hidden" name="status" value="reviewed">
                                                <button type="submit" class="dropdown-item"><i class="bi bi-check-circle text-success me-2"></i>Mark as Verified</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="<?= url('analytics/anomalies.php') ?>">
                                                <?= getCsrfInput() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="anomaly_id" value="<?= $a['id'] ?>">
                                                <input type="hidden" name="status" value="false_positive">
                                                <button type="submit" class="dropdown-item"><i class="bi bi-x-circle text-secondary me-2"></i>Mark Expected / Normal</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="<?= url('analytics/anomalies.php') ?>">
                                                <?= getCsrfInput() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="anomaly_id" value="<?= $a['id'] ?>">
                                                <input type="hidden" name="status" value="dismissed">
                                                <button type="submit" class="dropdown-item text-muted"><i class="bi bi-slash-circle me-2"></i>Dismiss</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
