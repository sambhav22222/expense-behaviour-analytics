<?php
declare(strict_types=1);

/**
 * View Single Expense Record Details
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/ExpenseService.php';

requireAuth();

$userId = getAuthUserId();
$expenseId = (int)($_GET['id'] ?? 0);

$expenseService = new ExpenseService();
$expense = $expenseService->getExpenseById($userId, $expenseId);

if (!$expense) {
    setFlash('error', 'Expense record not found or access denied.');
    header('Location: ' . url('expenses/index.php'));
    exit;
}

$pageTitle = 'Expense Details #' . $expenseId;
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8" style="max-width: 720px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Expense Transaction #<?= $expense['id'] ?></h4>
                <p class="text-secondary fs-7 mb-0">Complete audit and metadata for this recorded transaction.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= url('expenses/edit.php?id=' . $expense['id']) ?>" class="btn btn-sm btn-ebap">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
                <a href="<?= url('expenses/index.php') ?>" class="btn btn-sm btn-ebap-outline">
                    &larr; Back to List
                </a>
            </div>
        </div>

        <div class="ebap-card bg-white p-4 p-md-5 shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-start pb-4 mb-4 border-bottom">
                <div>
                    <span class="category-badge mb-2" style="background-color: <?= e($expense['category_color']) ?>18; color: <?= e($expense['category_color']) ?>;">
                        <i class="bi <?= e($expense['category_icon']) ?> me-1"></i> <?= e($expense['category_name']) ?>
                    </span>
                    <h4 class="fw-bold text-dark mb-1"><?= e($expense['title']) ?></h4>
                    <span class="text-muted fs-7">
                        <i class="bi bi-calendar3 me-1"></i> <?= formatDate($expense['transaction_date'], 'l, d F Y') ?>
                    </span>
                </div>
                <div class="text-end">
                    <small class="text-muted d-block text-uppercase" style="font-size: 0.72rem; font-weight: 600;">Transaction Amount</small>
                    <span class="fs-3 fw-bold text-danger font-monospace"><?= formatCurrency($expense['amount']) ?></span>
                </div>
            </div>

            <div class="row g-3 mb-4 fs-7">
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <small class="text-muted d-block mb-1" style="font-size: 0.7rem;">PAYMENT METHOD</small>
                        <strong class="text-dark"><i class="bi bi-credit-card me-1.5 text-primary"></i><?= e($expense['payment_method']) ?></strong>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <small class="text-muted d-block mb-1" style="font-size: 0.7rem;">MERCHANT / PAYEE</small>
                        <strong class="text-dark"><i class="bi bi-shop me-1.5 text-secondary"></i><?= !empty($expense['merchant']) ? e($expense['merchant']) : 'Not specified' ?></strong>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <small class="text-muted d-block mb-1" style="font-size: 0.7rem;">SPENDING NATURE</small>
                        <strong class="text-dark"><?= $expense['is_essential'] ? 'Essential Survival Need' : 'Discretionary Lifestyle' ?></strong>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <small class="text-muted d-block mb-1" style="font-size: 0.7rem;">RECURRING INDICATOR</small>
                        <strong class="text-dark"><?= !empty($expense['is_recurring']) ? 'Yes (Recurring Obligation)' : 'No (One-time Expense)' ?></strong>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="fw-bold text-secondary fs-7 text-uppercase mb-2" style="font-size: 0.75rem;">Notes & Description</h6>
                <div class="p-3 bg-light rounded-3 border fs-7 text-secondary">
                    <?= !empty($expense['description']) ? nl2br(e($expense['description'])) : '<em>No additional notes recorded for this transaction.</em>' ?>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 border-top text-muted" style="font-size: 0.75rem;">
                <div>
                    <span class="me-3"><strong>Created:</strong> <?= e($expense['created_at']) ?></span>
                    <span><strong>Last Modified:</strong> <?= e($expense['updated_at']) ?></span>
                </div>
                <form method="POST" action="<?= url('expenses/delete.php') ?>" class="d-inline" data-confirm="Are you sure you want to permanently delete this expense?">
                    <?= getCsrfInput() ?>
                    <input type="hidden" name="expense_id" value="<?= $expense['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash me-1"></i> Delete Record
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
