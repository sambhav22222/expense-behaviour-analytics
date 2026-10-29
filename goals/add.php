<?php
declare(strict_types=1);

/**
 * Create Financial Goal
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/GoalService.php';
require_once __DIR__ . '/../services/CategoryService.php';

requireAuth();

$userId = getAuthUserId();
$goalService = new GoalService();
$categoryService = new CategoryService();
$categories = $categoryService->getExpenseCategories($userId, true);

$errors = [];
$data = [
    'goal_name' => '',
    'goal_type' => 'savings',
    'category_id' => '',
    'target_amount' => '',
    'baseline_amount' => '',
    'current_progress' => '0',
    'start_date' => date('Y-m-d'),
    'target_date' => date('Y-m-d', strtotime('+6 months')),
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $data = [
        'goal_name' => trim($_POST['goal_name'] ?? ''),
        'goal_type' => $_POST['goal_type'] ?? 'savings',
        'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
        'target_amount' => trim($_POST['target_amount'] ?? ''),
        'baseline_amount' => trim($_POST['baseline_amount'] ?? '0'),
        'current_progress' => trim($_POST['current_progress'] ?? '0'),
        'start_date' => trim($_POST['start_date'] ?? ''),
        'target_date' => trim($_POST['target_date'] ?? ''),
        'description' => trim($_POST['description'] ?? '')
    ];

    $result = $goalService->createGoal($userId, $data);

    if ($result['success']) {
        setFlash('success', 'Financial goal created successfully.');
        header('Location: ' . url('goals/index.php'));
        exit;
    } else {
        $errors = $result['errors'];
    }
}

$pageTitle = 'Create Goal';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8" style="max-width: 720px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Create Financial Goal</h4>
                <p class="text-secondary fs-7 mb-0">Set up targets for savings, debt repayment, or expense reduction.</p>
            </div>
            <a href="<?= url('goals/index.php') ?>" class="btn btn-sm btn-ebap-outline">
                &larr; Back to Goals
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

            <form method="POST" action="<?= url('goals/add.php') ?>">
                <?= getCsrfInput() ?>

                <div class="mb-3">
                    <label for="goal_name" class="form-label fs-7 fw-semibold text-secondary">Goal Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="goal_name" name="goal_name" value="<?= e($data['goal_name']) ?>" placeholder="e.g. Emergency Fund (6 Months), Reduce Restaurant Dining..." required autofocus>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="goal_type" class="form-label fs-7 fw-semibold text-secondary">Goal Type</label>
                        <select class="form-select" id="goal_type" name="goal_type">
                            <option value="savings" <?= ($data['goal_type'] === 'savings') ? 'selected' : '' ?>>Savings Target (Asset or Fund)</option>
                            <option value="spending_reduction" <?= ($data['goal_type'] === 'spending_reduction') ? 'selected' : '' ?>>Spending Reduction (Cut Costs)</option>
                            <option value="category_cap" <?= ($data['goal_type'] === 'category_cap') ? 'selected' : '' ?>>Category Cap Limit</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="category_id" class="form-label fs-7 fw-semibold text-secondary">Relevant Category (Optional)</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">— Not Category Specific —</option>
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
                        <label for="target_amount" class="form-label fs-7 fw-semibold text-secondary">Target Amount (<?= getCurrencySymbol() ?>) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted font-monospace"><?= getCurrencySymbol() ?></span>
                            <input type="number" step="0.01" min="1" class="form-control font-monospace fw-semibold" id="target_amount" name="target_amount" value="<?= e($data['target_amount']) ?>" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="current_progress" class="form-label fs-7 fw-semibold text-secondary">Initial Saved Progress (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted font-monospace"><?= getCurrencySymbol() ?></span>
                            <input type="number" step="0.01" min="0" class="form-control font-monospace" id="current_progress" name="current_progress" value="<?= e($data['current_progress']) ?>" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label fs-7 fw-semibold text-secondary">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= e($data['start_date']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="target_date" class="form-label fs-7 fw-semibold text-secondary">Target Completion Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="target_date" name="target_date" value="<?= e($data['target_date']) ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label fs-7 fw-semibold text-secondary">Description / Motivation</label>
                    <textarea class="form-control" id="description" name="description" rows="2" placeholder="Why this goal matters, action plan to save..."><?= e($data['description']) ?></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                    <a href="<?= url('goals/index.php') ?>" class="btn btn-ebap-outline">Cancel</a>
                    <button type="submit" class="btn btn-ebap px-4">Create Goal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
