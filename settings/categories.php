<?php
declare(strict_types=1);

/**
 * Custom Category Management Workspace
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/CategoryService.php';

requireAuth();

$userId = getAuthUserId();
$categoryService = new CategoryService();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $type = $_POST['category_type'] ?? 'expense';
    $name = trim($_POST['category_name'] ?? '');
    $icon = trim($_POST['category_icon'] ?? 'bi-tag');
    $color = trim($_POST['category_color'] ?? '#4f46e5');

    if ($type === 'expense') {
        $isEssential = !empty($_POST['is_essential']);
        $res = $categoryService->createExpenseCategory($userId, $name, $icon, $color, $isEssential);
    } else {
        $res = $categoryService->createIncomeCategory($userId, $name, $icon, $color);
    }

    if ($res['success']) {
        setFlash('success', 'Custom category created successfully.');
        header('Location: ' . url('settings/categories.php'));
        exit;
    } else {
        $error = $res['error'];
    }
}

$expenseCategories = $categoryService->getExpenseCategories($userId, false);
$incomeCategories = $categoryService->getIncomeCategories($userId, false);

$pageTitle = 'Category Management';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Category Management</h4>
        <p class="text-secondary fs-7 mb-0">Manage default classifications and define custom categories personalized to your lifestyle.</p>
    </div>
    <a href="<?= url('settings/index.php') ?>" class="btn btn-sm btn-ebap-outline">
        &larr; Back to Settings
    </a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2.5 px-3 rounded-3 mb-4 fs-7">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- Add Custom Category Card -->
    <div class="col-lg-5">
        <div class="ebap-card bg-white p-4 h-100 shadow-sm">
            <h6 class="fw-bold text-dark mb-1 fs-6">Add Custom Category</h6>
            <p class="text-muted fs-7 mb-3">Custom categories are private to your user account.</p>

            <form method="POST" action="<?= url('settings/categories.php') ?>">
                <?= getCsrfInput() ?>

                <div class="mb-3">
                    <label class="form-label fs-7 fw-semibold text-secondary">Category Scope</label>
                    <select name="category_type" id="category_type_select" class="form-select" onchange="toggleEssentialCheckbox(this.value)">
                        <option value="expense">Expense Category</option>
                        <option value="income">Income Source Category</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fs-7 fw-semibold text-secondary">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="category_name" class="form-control" placeholder="e.g. Pet Care, Gaming, Side Hustle..." required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fs-7 fw-semibold text-secondary">Bootstrap Icon</label>
                        <input type="text" name="category_icon" class="form-control font-monospace fs-7" value="bi-tag">
                        <small class="text-muted" style="font-size: 0.7rem;">e.g. bi-heart, bi-laptop</small>
                    </div>
                    <div class="col-6">
                        <label class="form-label fs-7 fw-semibold text-secondary">Theme Color</label>
                        <input type="color" name="category_color" class="form-control form-control-color w-100" value="#4f46e5">
                    </div>
                </div>

                <div id="essential_wrapper" class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="is_essential" name="is_essential" value="1">
                    <label class="form-check-label fs-7 text-secondary" for="is_essential">
                        Classify as Essential Need (survival, healthcare, housing)
                    </label>
                </div>

                <button type="submit" class="btn btn-ebap w-100 py-2">
                    Create Custom Category
                </button>
            </form>
        </div>
    </div>

    <!-- Active Categories List -->
    <div class="col-lg-7">
        <div class="ebap-card bg-white p-4 h-100 shadow-sm">
            <h6 class="fw-bold text-dark mb-1 fs-6">Available Expense Categories</h6>
            <p class="text-muted fs-7 mb-3">System standards and your personal custom categories.</p>

            <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                <table class="table ebap-table align-middle">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Nature</th>
                            <th>Source</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenseCategories as $c): ?>
                            <tr>
                                <td>
                                    <span class="category-badge" style="background-color: <?= e($c['category_color']) ?>18; color: <?= e($c['category_color']) ?>;">
                                        <i class="bi <?= e($c['category_icon']) ?> me-1"></i> <?= e($c['category_name']) ?>
                                    </span>
                                </td>
                                <td class="fs-7 text-muted">
                                    <?= $c['is_essential'] ? 'Essential' : 'Discretionary' ?>
                                </td>
                                <td>
                                    <span class="badge <?= $c['is_system'] ? 'bg-light text-muted border' : 'bg-primary-subtle text-primary border border-primary-subtle' ?>">
                                        <?= $c['is_system'] ? 'System Default' : 'Custom User' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function toggleEssentialCheckbox(type) {
    const wrapper = document.getElementById('essential_wrapper');
    if (type === 'income') {
        wrapper.classList.add('d-none');
    } else {
        wrapper.classList.remove('d-none');
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
