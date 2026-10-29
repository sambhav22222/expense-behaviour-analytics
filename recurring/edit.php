<?php
declare(strict_types=1);

/**
 * Edit Recurring Subscription
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/RecurringExpenseService.php';
require_once __DIR__ . '/../services/CategoryService.php';

requireAuth();

$userId = getAuthUserId();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$recService = new RecurringExpenseService();
$categoryService = new CategoryService();

$existing = $recService->getRecurringById($userId, $id);
if (!$existing) {
    setFlash('error', 'Subscription not found or access denied.');
    header('Location: ' . url('recurring/index.php'));
    exit;
}

$categories = $categoryService->getExpenseCategories($userId, false);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'amount' => trim($_POST['amount'] ?? ''),
        'category_id' => $_POST['category_id'] ?? '',
        'frequency' => $_POST['frequency'] ?? 'monthly',
        'start_date' => trim($_POST['start_date'] ?? ''),
        'next_due_date' => trim($_POST['next_due_date'] ?? ''),
        'end_date' => trim($_POST['end_date'] ?? ''),
        'payment_method' => $_POST['payment_method'] ?? 'UPI',
        'notes' => trim($_POST['notes'] ?? ''),
        'status' => $_POST['status'] ?? 'active'
    ];

    $result = $recService->updateRecurring($userId, $id, $data);

    if ($result['success']) {
        setFlash('success', 'Subscription updated successfully.');
        header('Location: ' . url('recurring/index.php'));
        exit;
    } else {
        $errors = $result['errors'];
        $existing = array_merge($existing, $data);
    }
}

$pageTitle = 'Edit Subscription';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8" style="max-width: 720px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Edit Subscription #<?= $id ?></h4>
                <p class="text-secondary fs-7 mb-0">Modify billing amount, frequency, or next renewal date.</p>
            </div>
            <a href="<?= url('recurring/index.php') ?>" class="btn btn-sm btn-ebap-outline">
                &larr; Back to Subscriptions
            </a>
        </div>

        <div class="ebap-card bg-white p-4 p-md-5 shadow-sm">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger py-2.5 px-3 rounded-3 mb-4 fs-7">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('recurring/edit.php?id=' . $id) ?>">
                <?= getCsrfInput() ?>
                <input type="hidden" name="id" value="<?= $id ?>">

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label for="title" class="form-label fs-7 fw-semibold text-secondary">Subscription Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= e($existing['title']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="amount" class="form-label fs-7 fw-semibold text-secondary">Amount (<?= getCurrencySymbol() ?>) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted font-monospace"><?= getCurrencySymbol() ?></span>
                            <input type="number" step="0.01" min="1" class="form-control font-monospace fw-semibold" id="amount" name="amount" value="<?= e($existing['amount']) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label fs-7 fw-semibold text-secondary">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($existing['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="frequency" class="form-label fs-7 fw-semibold text-secondary">Billing Frequency</label>
                        <select class="form-select" id="frequency" name="frequency">
                            <option value="weekly" <?= ($existing['frequency'] === 'weekly') ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($existing['frequency'] === 'monthly') ? 'selected' : '' ?>>Monthly</option>
                            <option value="quarterly" <?= ($existing['frequency'] === 'quarterly') ? 'selected' : '' ?>>Quarterly</option>
                            <option value="yearly" <?= ($existing['frequency'] === 'yearly') ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label fs-7 fw-semibold text-secondary">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($existing['start_date']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="next_due_date" class="form-label fs-7 fw-semibold text-secondary">Next Due Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="next_due_date" name="next_due_date" value="<?= e($existing['next_due_date']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label fs-7 fw-semibold text-secondary">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= ($existing['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                            <option value="paused" <?= ($existing['status'] === 'paused') ? 'selected' : '' ?>>Paused</option>
                            <option value="cancelled" <?= ($existing['status'] === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="notes" class="form-label fs-7 fw-semibold text-secondary">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"><?= e($existing['notes'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="<?= url('recurring/index.php') ?>" class="btn btn-ebap-outline">Cancel</a>
                    <button type="submit" class="btn btn-ebap px-4">Update Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
