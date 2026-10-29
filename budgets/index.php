<?php
declare(strict_types=1);

/**
 * Budget Management & Performance Workspace
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/BudgetService.php';

requireAuth();

$userId = getAuthUserId();
$currency = getAuthCurrency();
$budgetService = new BudgetService();

$activeBudgets = $budgetService->getActiveBudgetsWithUtilization($userId, date('Y-m-d'));

$pageTitle = 'Budget Management';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Budget Management</h4>
        <p class="text-secondary fs-7 mb-0">Establish spending limits, monitor category utilization, and receive threshold alerts.</p>
    </div>
    <div>
        <a href="<?= url('budgets/add.php') ?>" class="btn btn-sm btn-ebap shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Create New Budget
        </a>
    </div>
</div>

<!-- Budgets Utilization Grid -->
<?php if (empty($activeBudgets)): ?>
    <div class="ebap-card p-5 text-center bg-white mb-4">
        <i class="bi bi-calculator fs-1 text-muted d-block mb-2"></i>
        <h5 class="fw-bold text-dark mb-1">No Active Budgets Configured</h5>
        <p class="text-secondary fs-7 mb-3 mx-auto" style="max-width: 480px;">
            Budgets define healthy spending boundaries. You can create an overall monthly limit or set category-specific caps (e.g. Dining, Shopping).
        </p>
        <a href="<?= url('budgets/add.php') ?>" class="btn btn-ebap">Set Your First Budget</a>
    </div>
<?php else: ?>
    <div class="row g-4 mb-4">
        <?php foreach ($activeBudgets as $b): ?>
            <div class="col-md-6 col-lg-4">
                <div class="ebap-card p-4 bg-white h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-light text-secondary border font-monospace mb-1.5" style="font-size: 0.72rem;">
                                <?= ucfirst($b['period_type']) ?>
                            </span>
                            <h5 class="fw-bold text-dark mb-0 fs-6"><?= e($b['budget_name']) ?></h5>
                            <small class="text-muted">
                                <?= !empty($b['category_name']) ? e($b['category_name']) : 'Overall Monthly Budget' ?>
                            </small>
                        </div>
                        <span class="badge bg-<?= $b['status_level'] ?>-subtle text-<?= $b['status_level'] ?> border border-<?= $b['status_level'] ?>-subtle">
                            <?= $b['utilization_pct'] ?>% used
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="my-3">
                        <div class="ebap-progress">
                            <div class="progress-bar bg-<?= $b['status_level'] ?>" style="width: <?= min(100, $b['utilization_pct']) ?>%"></div>
                        </div>
                    </div>

                    <!-- Metric Rows -->
                    <div class="row g-2 mb-3 fs-7">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">SPENT</small>
                                <span class="fw-bold text-dark font-monospace"><?= formatCurrency($b['actual_spent']) ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">LIMIT</small>
                                <span class="fw-bold text-dark font-monospace"><?= formatCurrency($b['budget_amount']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 fs-7">
                        <span class="text-muted">Status:</span>
                        <strong class="text-<?= $b['status_level'] ?>"><?= e($b['status_message']) ?></strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top fs-7">
                        <span class="text-muted" style="font-size: 0.72rem;">
                            <?= formatDate($b['start_date']) ?> &rarr; <?= formatDate($b['end_date']) ?>
                        </span>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= url('budgets/edit.php?id=' . $b['id']) ?>" class="btn btn-light border" title="Edit">
                                <i class="bi bi-pencil text-secondary"></i>
                            </a>
                            <form method="POST" action="<?= url('budgets/delete.php') ?>" class="d-inline" data-confirm="Are you sure you want to delete this budget?">
                                <?= getCsrfInput() ?>
                                <input type="hidden" name="budget_id" value="<?= $b['id'] ?>">
                                <button type="submit" class="btn btn-light border text-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
