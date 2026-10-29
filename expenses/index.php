<?php
declare(strict_types=1);

/**
 * Expense Management - Transaction List & Filter Workspace
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/ExpenseService.php';
require_once __DIR__ . '/../services/CategoryService.php';

requireAuth();

$userId = getAuthUserId();
$expenseService = new ExpenseService();
$categoryService = new CategoryService();

// Filter parameters
$filters = [
    'search' => trim($_GET['search'] ?? ''),
    'category_id' => $_GET['category_id'] ?? '',
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'payment_method' => $_GET['payment_method'] ?? '',
    'min_amount' => $_GET['min_amount'] ?? '',
    'max_amount' => $_GET['max_amount'] ?? '',
    'sort' => $_GET['sort'] ?? 'date',
    'order' => $_GET['order'] ?? 'desc'
];

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$result = $expenseService->getFilteredExpenses($userId, $filters, $page, $perPage);
$categories = $categoryService->getExpenseCategories($userId, false);

$pageTitle = 'Expense Transactions';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header & Quick Actions -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Expense Management</h4>
        <p class="text-secondary fs-7 mb-0">Record, filter, search, and manage all your expense transactions.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('reports/export.php?type=expenses&' . http_build_query($filters)) ?>" class="btn btn-sm btn-ebap-outline shadow-sm">
            <i class="bi bi-download me-1"></i> Export Filtered CSV
        </a>
        <a href="<?= url('expenses/add.php') ?>" class="btn btn-sm btn-ebap shadow-sm">
            <i class="bi bi-plus-lg me-1"></i> Add New Expense
        </a>
    </div>
</div>

<!-- Multi-Filter Card -->
<div class="ebap-card bg-white p-3.5 mb-4 shadow-sm">
    <form method="GET" action="<?= url('expenses/index.php') ?>" class="row g-2.5 align-items-end">
        <div class="col-md-3">
            <label class="form-label fs-7 fw-semibold text-secondary mb-1">Search Keywords</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Title, merchant, notes..." value="<?= e($filters['search']) ?>">
            </div>
        </div>

        <div class="col-md-2">
            <label class="form-label fs-7 fw-semibold text-secondary mb-1">Category</label>
            <select name="category_id" class="form-select form-select-sm">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($filters['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                        <?= e($cat['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label fs-7 fw-semibold text-secondary mb-1">From Date</label>
            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($filters['start_date']) ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label fs-7 fw-semibold text-secondary mb-1">To Date</label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($filters['end_date']) ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label fs-7 fw-semibold text-secondary mb-1">Payment Method</label>
            <select name="payment_method" class="form-select form-select-sm">
                <option value="">All Methods</option>
                <?php foreach (['UPI', 'Credit Card', 'Debit Card', 'Net Banking', 'Cash', 'Other'] as $pm): ?>
                    <option value="<?= $pm ?>" <?= ($filters['payment_method'] === $pm) ? 'selected' : '' ?>><?= $pm ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-ebap w-100" title="Apply Filter">
                <i class="bi bi-filter"></i>
            </button>
            <a href="<?= url('expenses/index.php') ?>" class="btn btn-sm btn-light border" title="Clear Filters">
                <i class="bi bi-arrow-counterclockwise"></i>
            </a>
        </div>
    </form>
</div>

<!-- Results Summary Pill Bar -->
<div class="d-flex justify-content-between align-items-center mb-3 fs-7 text-secondary">
    <div>
        Showing <strong class="text-dark"><?= count($result['records']) ?></strong> of <strong class="text-dark"><?= $result['total_records'] ?></strong> record(s)
    </div>
    <div>
        Filtered Total: <strong class="text-danger fs-6"><?= formatCurrency($result['total_amount']) ?></strong>
    </div>
</div>

<!-- Transactions Table -->
<div class="ebap-table-container mb-4">
    <?php if (empty($result['records'])): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
            <h6 class="fw-semibold text-dark">No expense records match your filter criteria.</h6>
            <p class="text-muted fs-7 mb-3">Try adjusting your filters or record a new transaction.</p>
            <a href="<?= url('expenses/add.php') ?>" class="btn btn-sm btn-ebap">Add New Expense</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table ebap-table align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Title & Description</th>
                        <th>Category</th>
                        <th>Payment</th>
                        <th>Merchant / Payee</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['records'] as $r): ?>
                        <tr>
                            <td class="text-muted text-nowrap font-monospace fs-7">
                                <?= formatDate($r['transaction_date']) ?>
                            </td>
                            <td>
                                <strong class="text-dark d-block"><?= e($r['title']) ?></strong>
                                <?php if (!empty($r['description'])): ?>
                                    <small class="text-muted text-truncate d-block" style="max-width: 250px;"><?= e($r['description']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="category-badge" style="background-color: <?= e($r['category_color']) ?>18; color: <?= e($r['category_color']) ?>;">
                                    <i class="bi <?= e($r['category_icon']) ?> me-1"></i> <?= e($r['category_name']) ?>
                                </span>
                                <?php if (!empty($r['is_recurring'])): ?>
                                    <span class="badge bg-light text-primary border ms-1" title="Recurring Expense"><i class="bi bi-arrow-repeat"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($r['payment_method']) ?></span>
                            </td>
                            <td class="text-secondary fs-7">
                                <?= !empty($r['merchant']) ? e($r['merchant']) : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td class="text-end fw-bold text-dark font-monospace">
                                <?= formatCurrency($r['amount']) ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('expenses/edit.php?id=' . $r['id']) ?>" class="btn btn-light border" title="Edit Expense">
                                        <i class="bi bi-pencil text-secondary"></i>
                                    </a>
                                    <form method="POST" action="<?= url('expenses/delete.php') ?>" class="d-inline" data-confirm="Are you sure you want to permanently delete this expense?">
                                        <?= getCsrfInput() ?>
                                        <input type="hidden" name="expense_id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-light border text-danger" title="Delete Expense">
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

        <!-- Pagination Controls -->
        <?php if ($result['total_pages'] > 1): ?>
            <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light">
                <small class="text-muted">Page <?= $result['current_page'] ?> of <?= $result['total_pages'] ?></small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= url('expenses/index.php?' . http_build_query(array_merge($filters, ['page' => $page - 1]))) ?>">&laquo; Previous</a>
                        </li>
                        <?php for ($p = 1; $p <= $result['total_pages']; $p++): ?>
                            <?php if ($p == $page || $p == 1 || $p == $result['total_pages'] || abs($p - $page) <= 2): ?>
                                <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('expenses/index.php?' . http_build_query(array_merge($filters, ['page' => $p]))) ?>"><?= $p ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $result['total_pages']) ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= url('expenses/index.php?' . http_build_query(array_merge($filters, ['page' => $page + 1]))) ?>">Next &raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
