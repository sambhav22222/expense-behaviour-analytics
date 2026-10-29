<?php
declare(strict_types=1);

/**
 * Financial Goals & Target Tracking Workspace
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/GoalService.php';

requireAuth();

$userId = getAuthUserId();
$currency = getAuthCurrency();
$goalService = new GoalService();

$goals = $goalService->getGoals($userId);

$pageTitle = 'Financial Goals';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Financial Goals & Milestones</h4>
        <p class="text-secondary fs-7 mb-0">Set realistic savings targets, category spending reductions, and track achievement velocity.</p>
    </div>
    <div>
        <a href="<?= url('goals/add.php') ?>" class="btn btn-sm btn-ebap shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Create Financial Goal
        </a>
    </div>
</div>

<?php if (empty($goals)): ?>
    <div class="ebap-card p-5 text-center bg-white mb-4">
        <i class="bi bi-bullseye fs-1 text-muted d-block mb-2"></i>
        <h5 class="fw-bold text-dark mb-1">No Active Financial Goals</h5>
        <p class="text-secondary fs-7 mb-3 mx-auto" style="max-width: 480px;">
            Establish goals such as building an Emergency Fund, reducing dining out expenses by 20%, or saving for an asset.
        </p>
        <a href="<?= url('goals/add.php') ?>" class="btn btn-ebap">Create Your First Goal</a>
    </div>
<?php else: ?>
    <div class="row g-4 mb-4">
        <?php foreach ($goals as $g): ?>
            <div class="col-md-6 col-lg-4">
                <div class="ebap-card p-4 bg-white h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-light text-secondary border font-monospace mb-1.5" style="font-size: 0.72rem;">
                                <?= ucfirst(str_replace('_', ' ', $g['goal_type'])) ?>
                            </span>
                            <h5 class="fw-bold text-dark mb-0 fs-6"><?= e($g['goal_name']) ?></h5>
                            <?php if (!empty($g['description'])): ?>
                                <small class="text-muted d-block text-truncate" style="max-width: 240px;"><?= e($g['description']) ?></small>
                            <?php endif; ?>
                        </div>
                        <span class="badge <?= $g['status'] === 'achieved' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' ?>">
                            <?= $g['progress_pct'] ?>%
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="my-3">
                        <div class="ebap-progress" style="height: 10px;">
                            <div class="progress-bar <?= $g['status'] === 'achieved' ? 'bg-success' : 'bg-primary' ?>" style="width: <?= $g['progress_pct'] ?>%"></div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3 fs-7">
                        <div class="col-6">
                            <div class="p-2 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">CURRENT PROGRESS</small>
                                <span class="fw-bold text-dark font-monospace"><?= formatCurrency($g['current_progress']) ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 bg-light rounded-3 border">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">TARGET AMOUNT</small>
                                <span class="fw-bold text-dark font-monospace"><?= formatCurrency($g['target_amount']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 fs-7">
                        <span class="text-muted">Target Date:</span>
                        <span class="font-monospace fw-semibold <?= $g['is_overdue'] ? 'text-danger' : 'text-secondary' ?>">
                            <?= formatDate($g['target_date']) ?>
                            (<?= $g['days_remaining'] >= 0 ? $g['days_remaining'] . ' days left' : 'Overdue' ?>)
                        </span>
                    </div>

                    <!-- Action Area -->
                    <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top fs-7">
                        <!-- Add Contribution Trigger Button -->
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#contribModal<?= $g['id'] ?>">
                            <i class="bi bi-plus-circle me-1"></i> Add Funds
                        </button>

                        <form method="POST" action="<?= url('goals/delete.php') ?>" class="d-inline" data-confirm="Are you sure you want to delete this goal?">
                            <?= getCsrfInput() ?>
                            <input type="hidden" name="goal_id" value="<?= $g['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete Goal">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Contribution Modal -->
                <div class="modal fade" id="contribModal<?= $g['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <form method="POST" action="<?= url('goals/contribute.php') ?>">
                                <?= getCsrfInput() ?>
                                <input type="hidden" name="goal_id" value="<?= $g['id'] ?>">

                                <div class="modal-header border-bottom">
                                    <h6 class="modal-title fw-bold text-dark">Add Contribution: <?= e($g['goal_name']) ?></h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="mb-3">
                                        <label class="form-label fs-7 fw-semibold text-secondary">Contribution Amount (<?= getCurrencySymbol() ?>) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted font-monospace"><?= getCurrencySymbol() ?></span>
                                            <input type="number" step="0.01" min="1" class="form-control font-monospace fw-semibold" name="amount" placeholder="0.00" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fs-7 fw-semibold text-secondary">Date</label>
                                        <input type="date" class="form-control" name="contribution_date" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fs-7 fw-semibold text-secondary">Description / Note</label>
                                        <input type="text" class="form-control" name="description" placeholder="e.g. Monthly salary savings transfer">
                                    </div>
                                </div>
                                <div class="modal-footer border-top bg-light">
                                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-sm btn-ebap">Save Contribution</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
