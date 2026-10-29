<?php
declare(strict_types=1);

/**
 * Create Budget
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/BudgetService.php';
require_once __DIR__ . '/../services/CategoryService.php';

requireAuth();

$userId = getAuthUserId();
$budgetService = new BudgetService();
$categoryService = new CategoryService();
$categories = $categoryService->getExpenseCategories($userId, true);

$today = new DateTime('now');
$errors = [];
$data = [
    'budget_name' => '',
    'category_id' => '',
    'budget_amount' => '',
    'period_type' => 'monthly',
    'start_date' => $today->modify('first day of this month')->format('Y-m-d'),
    'end_date' => $today->modify('last day of this month')->format('Y-m-d'),
    'warning_threshold' => 80
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $data = [
        'budget_name' => trim($_POST['budget_name'] ?? ''),
        'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
        'budget_amount' => trim($_POST['budget_amount'] ?? ''),
        'period_type' => $_POST['period_type'] ?? 'monthly',
        'start_date' => trim($_POST['start_date'] ?? ''),
        'end_date' => trim($_POST['end_date'] ?? ''),
        'warning_threshold' => (int)($_POST['warning_threshold'] ?? 80)
    ];

    $result = $budgetService->createBudget($userId, $data);

    if ($result['success']) {
        setFlash('success', 'Budget configured successfully.');
        header('Location: ' . url('budgets/index.php'));
        exit;
    } else {
        $errors = $result['errors'];
    }
}

$pageTitle = 'Create Budget';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8" style="max-width: 720px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Create Budget</h4>
                <p class="text-secondary fs-7 mb-0">Set an overall monthly limit or allocate spending for a specific category.</p>
            </div>
            <a href="<?= url('budgets/index.php') ?>" class="btn btn-sm btn-ebap-outline">
                &larr; Back to Budgets
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

            <form method="POST" action="<?= url('budgets/add.php') ?>">
                <?= getCsrfInput() ?>

                <div class="mb-3">
                    <label for="budget_name" class="form-label fs-7 fw-semibold text-secondary">Budget Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="budget_name" name="budget_name" value="<?= e($data['budget_name']) ?>" placeholder="e.g. October Overall Budget, Dining Out Cap..." required autofocus>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label fs-7 fw-semibold text-secondary">Category (Optional)</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">— Overall Total Budget (All Categories) —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($data['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted" style="font-size: 0.72rem;">Leave blank to set an overall monthly ceiling.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="budget_amount" class="form-label fs-7 fw-semibold text-secondary">Spending Limit (<?= getCurrencySymbol() ?>) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted font-monospace"><?= getCurrencySymbol() ?></span>
                            <input type="number" step="0.01" min="1" class="form-control font-monospace fw-semibold" id="budget_amount" name="budget_amount" value="<?= e((string)$data['budget_amount']) ?>" placeholder="0.00" required>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="period_type" class="form-label fs-7 fw-semibold text-secondary">Period Type</label>
                        <select class="form-select" id="period_type" name="period_type">
                            <option value="monthly" <?= ($data['period_type'] === 'monthly') ? 'selected' : '' ?>>Monthly</option>
                            <option value="weekly" <?= ($data['period_type'] === 'weekly') ? 'selected' : '' ?>>Weekly</option>
                            <option value="yearly" <?= ($data['period_type'] === 'yearly') ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="start_date" class="form-label fs-7 fw-semibold text-secondary">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($data['start_date']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label fs-7 fw-semibold text-secondary">End Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= e($data['end_date']) ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="warning_threshold" class="form-label fs-7 fw-semibold text-secondary">Warning Alert Threshold (%)</label>
                    <div class="d-flex align-items-center gap-3">
                        <input type="range" class="form-range" id="warning_threshold" name="warning_threshold" min="50" max="95" step="5" value="<?= $data['warning_threshold'] ?>" oninput="document.getElementById('thresholdVal').innerText = this.value + '%'">
                        <span id="thresholdVal" class="badge bg-warning-subtle text-warning border border-warning-subtle font-monospace fs-7 px-2.5 py-1">
                            <?= $data['warning_threshold'] ?>%
                        </span>
                    </div>
                    <small class="text-muted" style="font-size: 0.72rem;">EBAP will surface a warning badge when spending reaches this percentage.</small>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="<?= url('budgets/index.php') ?>" class="btn btn-ebap-outline">Cancel</a>
                    <button type="submit" class="btn btn-ebap px-4">Save Budget</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
