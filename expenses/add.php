<?php
declare(strict_types=1);

/**
 * Add New Expense Record
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/ExpenseService.php';
require_once __DIR__ . '/../services/CategoryService.php';

requireAuth();

$userId = getAuthUserId();
$expenseService = new ExpenseService();
$categoryService = new CategoryService();
$categories = $categoryService->getExpenseCategories($userId, true);

$errors = [];
$data = [
    'title' => '',
    'amount' => '',
    'transaction_date' => date('Y-m-d'),
    'category_id' => '',
    'payment_method' => 'UPI',
    'merchant' => '',
    'description' => '',
    'is_recurring' => 0
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'amount' => trim($_POST['amount'] ?? ''),
        'transaction_date' => trim($_POST['transaction_date'] ?? ''),
        'category_id' => $_POST['category_id'] ?? '',
        'payment_method' => $_POST['payment_method'] ?? 'UPI',
        'merchant' => trim($_POST['merchant'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'is_recurring' => !empty($_POST['is_recurring']) ? 1 : 0
    ];

    $result = $expenseService->createExpense($userId, $data);

    if ($result['success']) {
        setFlash('success', 'Expense recorded successfully.');
        header('Location: ' . url('expenses/index.php'));
        exit;
    } else {
        $errors = $result['errors'];
    }
}

$pageTitle = 'Add Expense';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8" style="max-width: 720px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Add New Expense</h4>
                <p class="text-secondary fs-7 mb-0">Record a new outflow transaction to your personal account.</p>
            </div>
            <a href="<?= url('expenses/index.php') ?>" class="btn btn-sm btn-ebap-outline">
                &larr; Back to Expenses
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

            <form method="POST" action="<?= url('expenses/add.php') ?>">
                <?= getCsrfInput() ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label for="title" class="form-label fs-7 fw-semibold text-secondary">Expense Title / Item <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= e($data['title']) ?>" placeholder="e.g. Grocery shopping, Metro recharge..." required autofocus>
                    </div>
                    <div class="col-md-4">
                        <label for="amount" class="form-label fs-7 fw-semibold text-secondary">Amount (<?= getCurrencySymbol() ?>) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted font-monospace"><?= getCurrencySymbol() ?></span>
                            <input type="number" step="0.01" min="0.01" class="form-control font-monospace fw-semibold" id="amount" name="amount" value="<?= e($data['amount']) ?>" placeholder="0.00" required>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="transaction_date" class="form-label fs-7 fw-semibold text-secondary">Transaction Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="transaction_date" name="transaction_date" value="<?= e($data['transaction_date']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="category_id" class="form-label fs-7 fw-semibold text-secondary">Spending Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($data['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['category_name']) ?> (<?= $cat['is_essential'] ? 'Essential' : 'Discretionary' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="payment_method" class="form-label fs-7 fw-semibold text-secondary">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <?php foreach (['UPI', 'Credit Card', 'Debit Card', 'Net Banking', 'Cash', 'Cheque', 'Other'] as $pm): ?>
                                <option value="<?= $pm ?>" <?= ($data['payment_method'] === $pm) ? 'selected' : '' ?>><?= $pm ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="merchant" class="form-label fs-7 fw-semibold text-secondary">Merchant / Payee / Store (Optional)</label>
                        <input type="text" class="form-control" id="merchant" name="merchant" value="<?= e($data['merchant']) ?>" placeholder="e.g. Swiggy, Amazon, Metro Corp...">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fs-7 fw-semibold text-secondary">Notes / Description (Optional)</label>
                    <textarea class="form-control" id="description" name="description" rows="2" placeholder="Additional details, item breakdown, or notes..."><?= e($data['description']) ?></textarea>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="is_recurring" name="is_recurring" value="1" <?= $data['is_recurring'] ? 'checked' : '' ?>>
                    <label class="form-check-label fs-7 text-secondary" for="is_recurring">
                        This is a recurring monthly bill or subscription
                    </label>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="<?= url('expenses/index.php') ?>" class="btn btn-ebap-outline">Cancel</a>
                    <button type="submit" class="btn btn-ebap px-4">Save Expense Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
