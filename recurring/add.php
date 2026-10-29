<?php
declare(strict_types=1);

/**
 * Add Recurring Subscription / Expense
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
$recService = new RecurringExpenseService();
$categoryService = new CategoryService();
$categories = $categoryService->getExpenseCategories($userId, true);

$errors = [];
$data = [
    'title' => '',
    'amount' => '',
    'category_id' => '',
    'frequency' => 'monthly',
    'start_date' => date('Y-m-d'),
    'next_due_date' => date('Y-m-d'),
    'end_date' => '',
    'payment_method' => 'UPI',
    'notes' => ''
];

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
        'notes' => trim($_POST['notes'] ?? '')
    ];

    $result = $recService->createRecurring($userId, $data);

    if ($result['success']) {
        setFlash('success', 'Subscription recorded successfully.');
        header('Location: ' . url('recurring/index.php'));
        exit;
    } else {
        $errors = $result['errors'];
    }
}

$pageTitle = 'Add Recurring Subscription';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8" style="max-width: 720px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Add Subscription / Recurring Bill</h4>
                <p class="text-secondary fs-7 mb-0">Record Netflix, broadband, insurance, gym, or apartment rent commitments.</p>
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

            <form method="POST" action="<?= url('recurring/add.php') ?>">
                <?= getCsrfInput() ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label for="title" class="form-label fs-7 fw-semibold text-secondary">Subscription / Service Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= e($data['title']) ?>" placeholder="e.g. Netflix Premium, Fiber Internet, Gym Membership..." required autofocus>
                    </div>
                    <div class="col-md-4">
                        <label for="amount" class="form-label fs-7 fw-semibold text-secondary">Amount (<?= getCurrencySymbol() ?>) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted font-monospace"><?= getCurrencySymbol() ?></span>
                            <input type="number" step="0.01" min="1" class="form-control font-monospace fw-semibold" id="amount" name="amount" value="<?= e((string)$data['amount']) ?>" placeholder="0.00" required>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label fs-7 fw-semibold text-secondary">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($data['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="frequency" class="form-label fs-7 fw-semibold text-secondary">Billing Frequency</label>
                        <select class="form-select" id="frequency" name="frequency">
                            <option value="weekly" <?= ($data['frequency'] === 'weekly') ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($data['frequency'] === 'monthly') ? 'selected' : '' ?>>Monthly</option>
                            <option value="quarterly" <?= ($data['frequency'] === 'quarterly') ? 'selected' : '' ?>>Quarterly (Every 3 months)</option>
                            <option value="yearly" <?= ($data['frequency'] === 'yearly') ? 'selected' : '' ?>>Yearly / Annual</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label fs-7 fw-semibold text-secondary">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($data['start_date']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="next_due_date" class="form-label fs-7 fw-semibold text-secondary">Next Due Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="next_due_date" name="next_due_date" value="<?= e($data['next_due_date']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="payment_method" class="form-label fs-7 fw-semibold text-secondary">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <?php foreach (['UPI', 'Credit Card', 'Debit Card', 'Net Banking', 'Cash', 'Other'] as $pm): ?>
                                <option value="<?= $pm ?>" <?= ($data['payment_method'] === $pm) ? 'selected' : '' ?>><?= $pm ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="notes" class="form-label fs-7 fw-semibold text-secondary">Notes (Optional)</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Plan details, renewal link, or mandate remarks..."><?= e($data['notes']) ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="<?= url('recurring/index.php') ?>" class="btn btn-ebap-outline">Cancel</a>
                    <button type="submit" class="btn btn-ebap px-4">Save Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
