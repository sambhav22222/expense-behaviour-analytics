<?php
declare(strict_types=1);

/**
 * Sidebar Navigation Partial
 * Expense Behaviour Analytical Platform (EBAP)
 */

$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
function isSidebarActive(string $pattern, string $currentScript): bool {
    return str_contains($currentScript, $pattern);
}
?>
<aside id="ebap-sidebar">
    <!-- Brand Logo Header -->
    <div class="brand-header">
        <a href="<?= url('dashboard/index.php') ?>" class="text-decoration-none d-flex align-items-center gap-2.5">
            <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
                <i class="bi bi-graph-up-arrow fs-5"></i>
            </div>
            <div>
                <span class="fw-bold fs-6 text-dark tracking-tight d-block leading-none">EBAP</span>
                <span class="text-muted" style="font-size: 0.68rem; font-weight: 500;">Expense Analytics</span>
            </div>
        </a>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-nav">
        <!-- Section: Overview -->
        <div class="nav-section-title">Overview</div>
        <a href="<?= url('dashboard/index.php') ?>" class="sidebar-link <?= isSidebarActive('/dashboard/', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <!-- Section: Transactions -->
        <div class="nav-section-title">Transactions</div>
        <a href="<?= url('expenses/index.php') ?>" class="sidebar-link <?= isSidebarActive('/expenses/', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-receipt"></i> Expenses
        </a>
        <a href="<?= url('income/index.php') ?>" class="sidebar-link <?= isSidebarActive('/income/', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i> Income
        </a>

        <!-- Section: Behaviour Analytics -->
        <div class="nav-section-title">Behaviour Analytics</div>
        <a href="<?= url('analytics/index.php') ?>" class="sidebar-link <?= (isSidebarActive('/analytics/index.php', $currentScript) || isSidebarActive('/analytics/behaviour.php', $currentScript)) ? 'active' : '' ?>">
            <i class="bi bi-pie-chart"></i> Behaviour Analytics
        </a>
        <a href="<?= url('analytics/categories.php') ?>" class="sidebar-link <?= isSidebarActive('/analytics/categories.php', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-tags"></i> Category Breakdown
        </a>
        <a href="<?= url('analytics/trends.php') ?>" class="sidebar-link <?= isSidebarActive('/analytics/trends.php', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> Spending Trends
        </a>
        <a href="<?= url('analytics/anomalies.php') ?>" class="sidebar-link <?= isSidebarActive('/analytics/anomalies.php', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-shield-exclamation"></i> Anomaly Detection
        </a>
        <a href="<?= url('insights/index.php') ?>" class="sidebar-link <?= isSidebarActive('/insights/', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-lightbulb"></i> Habit Insights
        </a>

        <!-- Section: Planning -->
        <div class="nav-section-title">Planning & Goals</div>
        <a href="<?= url('budgets/index.php') ?>" class="sidebar-link <?= isSidebarActive('/budgets/', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-calculator"></i> Budgets
        </a>
        <a href="<?= url('recurring/index.php') ?>" class="sidebar-link <?= isSidebarActive('/recurring/', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-arrow-repeat"></i> Subscriptions
        </a>
        <a href="<?= url('goals/index.php') ?>" class="sidebar-link <?= isSidebarActive('/goals/', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-bullseye"></i> Financial Goals
        </a>

        <!-- Section: Data & Tools -->
        <div class="nav-section-title">Data & Reports</div>
        <a href="<?= url('reports/index.php') ?>" class="sidebar-link <?= isSidebarActive('/reports/', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-spreadsheet"></i> Reports & Exports
        </a>
        <a href="<?= url('import/index.php') ?>" class="sidebar-link <?= isSidebarActive('/import/', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-cloud-arrow-up"></i> Import CSV
        </a>
        <a href="<?= url('settings/index.php') ?>" class="sidebar-link <?= isSidebarActive('/settings/', $currentScript) ? 'active' : '' ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
    </div>

    <!-- Sidebar Bottom Footer -->
    <div class="p-3 border-top bg-light">
        <div class="d-flex align-items-center justify-content-between">
            <span class="text-muted" style="font-size: 0.72rem;">Academic Edition v1.0</span>
            <a href="<?= url('logout.php') ?>" class="text-danger text-decoration-none" title="Sign Out">
                <i class="bi bi-box-arrow-right fs-6"></i>
            </a>
        </div>
    </div>
</aside>
