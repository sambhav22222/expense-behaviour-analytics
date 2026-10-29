<?php
declare(strict_types=1);

/**
 * Top Navbar Partial
 * Expense Behaviour Analytical Platform (EBAP)
 */

$user = getAuthUser();
$currency = getAuthCurrency();
?>
<header id="ebap-topbar">
    <div class="d-flex align-items-center">
        <!-- Mobile Toggle Button -->
        <button type="button" id="ebap-sidebar-toggle" class="btn btn-sm btn-light d-lg-none me-3 border">
            <i class="bi bi-list fs-5"></i>
        </button>

        <!-- Current Module Context -->
        <div>
            <h5 class="mb-0 fw-semibold fs-6 text-dark"><?= e($pageTitle ?? 'Analytics') ?></h5>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <!-- Currency Badge -->
        <span class="badge bg-light text-primary border px-2.5 py-1.5 fw-medium d-none d-sm-inline-flex align-items-center">
            <i class="bi bi-cash-stack me-1.5 text-primary"></i> <?= e($currency) ?> (<?= e(getCurrencySymbol($currency)) ?>)
        </span>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1.5 px-2.5 rounded-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem; font-weight: 600;">
                    <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                </div>
                <span class="d-none d-md-inline fw-medium fs-7"><?= e(explode(' ', $user['full_name'] ?? 'User')[0]) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="min-width: 210px; border-radius: 10px;">
                <li class="px-3 py-1 mb-1 border-bottom">
                    <p class="mb-0 fw-semibold text-dark fs-7"><?= e($user['full_name'] ?? '') ?></p>
                    <small class="text-muted text-truncate d-block" style="font-size: 0.75rem;"><?= e($user['email'] ?? '') ?></small>
                </li>
                <li><a class="dropdown-menu-item dropdown-item py-1.5 fs-7" href="<?= url('settings/index.php') ?>"><i class="bi bi-person-gear me-2 text-secondary"></i>Account Settings</a></li>
                <li><a class="dropdown-menu-item dropdown-item py-1.5 fs-7" href="<?= url('settings/categories.php') ?>"><i class="bi bi-tags me-2 text-secondary"></i>Manage Categories</a></li>
                <li><a class="dropdown-menu-item dropdown-item py-1.5 fs-7" href="<?= url('reports/index.php') ?>"><i class="bi bi-file-earmark-bar-graph me-2 text-secondary"></i>Export Reports</a></li>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-menu-item dropdown-item py-1.5 fs-7 text-danger" href="<?= url('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
            </ul>
        </div>
    </div>
</header>
