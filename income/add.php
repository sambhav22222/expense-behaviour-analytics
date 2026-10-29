<?php
declare(strict_types=1);

/**
 * Add Income Record
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/IncomeService.php';
require_once __DIR__ . '/../services/CategoryService.php';

requireAuth();

$userId = getAuthUserId();
$incomeService = new IncomeService();
$categoryService = new CategoryService();
$categories = $categoryService->getIncomeCategories($userId, true);

$errors = [];
$data = [
    'title' => '',
    'amount' => '',
    'received_date' => date('Y-m-d'),
    'category_id' => '',
    'payment_method' => 'Bank Transfer',
    'payer' => '',
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'amount' => trim($_POST['amount'] ?? ''),
        'received_date' => trim($_POST['received_date'] ?? ''),
        'category_id' => $_POST['category_id'] ?? '',
        'payment_method' => $_POST['payment_method'] ?? 'Bank Transfer',
        'payer' => trim($_POST['payer'] ?? ''),
        'description' => trim($_POST['description'] ?? '')
    ];

    $result = $incomeService->createIncome($userId, $data);

    if ($result['success']) {
        setFlash('success', 'Income record successfully logged.');
        header('Location: ' . url('income/index.php'));
        exit;
    } else {
        $errors = $result['errors'];
    }
}

$pageTitle = 'Add Income';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8" style="max-width: 720px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Add Income Record</h4>
                <p class="text-secondary fs-7 mb-0">Record salary, client payments, freelance fees, or investments.</p>
            </div>
            <a href="<?= url('income/index.php') ?>" class="btn btn-sm btn-ebap-outline">
                &larr; Back to Income
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

            <form method="POST" action="<?= url('income/add.php') ?>">
                <?= getCsrfInput() ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label for="title" class="form-label fs-7 fw-semibold text-secondary">Income Source / Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?= e($data['title']) ?>" placeholder="e.g. October Base Salary, Web Consultation..." required autofocus>
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
                        <label for="received_date" class="form-label fs-7 fw-semibold text-secondary">Date Received <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="received_date" name="received_date" value="<?= e($data['received_date']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="category_id" class="form-label fs-7 fw-semibold text-secondary">Source Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($data['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="payment_method" class="form-label fs-7 fw-semibold text-secondary">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <?php foreach (['Bank Transfer', 'UPI', 'Cash', 'Cheque', 'Other'] as $pm): ?>
                                <option value="<?= $pm ?>" <?= ($data['payment_method'] === $pm) ? 'selected' : '' ?>><?= $pm ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="payer" class="form-label fs-7 fw-semibold text-secondary">Payer / Employer / Client</label>
                        <input type="text" class="form-control" id="payer" name="payer" value="<?= e($data['payer']) ?>" placeholder="e.g. Acme Corp, Upwork...">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label fs-7 fw-semibold text-secondary">Notes / Description (Optional)</label>
                    <textarea class="form-control" id="description" name="description" rows="2" placeholder="Details regarding this payment..."><?= e($data['description']) ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="<?= url('income/index.php') ?>" class="btn btn-ebap-outline">Cancel</a>
                    <button type="submit" class="btn btn-ebap px-4">Save Income Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
