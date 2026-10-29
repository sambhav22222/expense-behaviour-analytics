<?php
declare(strict_types=1);

/**
 * Recurring Expenses & Subscription Management Workspace
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/RecurringExpenseService.php';

requireAuth();

$userId = getAuthUserId();
$currency = getAuthCurrency();
$recService = new RecurringExpenseService();

$recData = $recService->getAllRecurring($userId);
$items = $recData['items'];

$pageTitle = 'Subscriptions & Recurring Bills';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Subscriptions & Recurring Bills</h4>
        <p class="text-secondary fs-7 mb-0">Track fixed commitments, normalize monthly spending, and record payments in one click.</p>
    </div>
    <div>
        <a href="<?= url('recurring/add.php') ?>" class="btn btn-sm btn-ebap shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Recurring Bill
        </a>
    </div>
</div>

<!-- Normalized Overview Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="stat-card">
            <span class="stat-label">Estimated Monthly Load</span>
            <div class="stat-value text-primary"><?= formatCurrency($recData['total_monthly']) ?></div>
            <div class="stat-sub">Normalized across all active intervals</div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-4">
        <div class="stat-card">
            <span class="stat-label">Estimated Annual Cost</span>
            <div class="stat-value text-dark"><?= formatCurrency($recData['total_annual']) ?></div>
            <div class="stat-sub">Projected 12-month commitment</div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-4">
        <div class="stat-card">
            <span class="stat-label">Active Subscriptions</span>
            <div class="stat-value text-dark"><?= $recData['active_count'] ?></div>
            <div class="stat-sub"><?= count($items) ?> total configured</div>
        </div>
    </div>
</div>

<!-- Subscriptions Table -->
<div class="ebap-table-container mb-4">
    <?php if (empty($items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-arrow-repeat fs-1 text-muted d-block mb-2"></i>
            <h6 class="fw-semibold text-dark">No Subscriptions Recorded</h6>
            <p class="text-muted fs-7 mb-3">Add your streaming services, gym pass, broadband, or rent obligations.</p>
            <a href="<?= url('recurring/add.php') ?>" class="btn btn-sm btn-ebap">Add First Subscription</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ebap-table align-middle">
                <thead>
                    <tr>
                        <th>Subscription / Bill</th>
                        <th>Category</th>
                        <th>Billing Cycle</th>
                        <th class="text-end">Billing Amount</th>
                        <th class="text-end">Monthly Cost</th>
                        <th>Next Due Date</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 170px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $r): ?>
                        <tr>
                            <td>
                                <strong class="text-dark d-block"><?= e($r['title']) ?></strong>
                                <?php if (!empty($r['notes'])): ?>
                                    <small class="text-muted text-truncate d-block" style="max-width: 220px;"><?= e($r['notes']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="category-badge" style="background-color: <?= e($r['category_color']) ?>18; color: <?= e($r['category_color']) ?>;">
                                    <i class="bi <?= e($r['category_icon']) ?> me-1"></i> <?= e($r['category_name']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace"><?= ucfirst($r['frequency']) ?></span>
                            </td>
                            <td class="text-end font-monospace fw-semibold text-dark">
                                <?= formatCurrency($r['amount']) ?>
                            </td>
                            <td class="text-end font-monospace text-muted fs-7">
                                <?= formatCurrency($r['monthly_normalized']) ?>/mo
                            </td>
                            <td>
                                <?php
                                $due = new DateTime($r['next_due_date']);
                                $now = new DateTime('today');
                                $isDueSoon = ($due <= $now->modify('+7 days'));
                                ?>
                                <span class="font-monospace fs-7 <?= $isDueSoon ? 'fw-bold text-danger' : 'text-secondary' ?>">
                                    <?= formatDate($r['next_due_date']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $r['status'] === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted border' ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <!-- 1-Click Log As Expense Button -->
                                    <form method="POST" action="<?= url('recurring/log_payment.php') ?>" class="d-inline" data-confirm="Log this bill as a completed expense of <?= formatCurrency($r['amount']) ?> and advance next due date?">
                                        <?= getCsrfInput() ?>
                                        <input type="hidden" name="recurring_id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-light border text-primary" title="Record Expense Payment Now">
                                            <i class="bi bi-check2-circle"></i> Log
                                        </button>
                                    </form>

                                    <a href="<?= url('recurring/edit.php?id=' . $r['id']) ?>" class="btn btn-light border" title="Edit">
                                        <i class="bi bi-pencil text-secondary"></i>
                                    </a>

                                    <form method="POST" action="<?= url('recurring/delete.php') ?>" class="d-inline" data-confirm="Are you sure you want to delete this subscription?">
                                        <?= getCsrfInput() ?>
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-light border text-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
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
