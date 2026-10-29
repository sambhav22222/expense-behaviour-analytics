<?php
declare(strict_types=1);

/**
 * Page 15: Admin & Privacy-Preserving System Management
 * Expense Behaviour Analytical Platform (EBAP)
 *
 * Strictly enforces user financial privacy: displays only non-sensitive aggregate
 * system statistics, PHP/MySQL health checks, system default categories, and audit logs.
 * Never exposes individual users' private transaction descriptions, income, or expenses.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/CategoryService.php';

requireAuth();

$userId = getAuthUserId();
$pdo = getDBConnection();
$categoryService = new CategoryService($pdo);

// 1. Non-sensitive Aggregate Usage Statistics (Count only, zero financial values or descriptions exposed)
$stats = [
    'total_users' => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'active_users' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE account_status = 'active'")->fetchColumn(),
    'system_expense_cats' => (int)$pdo->query("SELECT COUNT(*) FROM expense_categories WHERE user_id IS NULL")->fetchColumn(),
    'system_income_cats' => (int)$pdo->query("SELECT COUNT(*) FROM income_categories WHERE user_id IS NULL")->fetchColumn(),
    'total_audit_events' => (int)$pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn(),
    'total_import_jobs' => (int)$pdo->query("SELECT COUNT(*) FROM import_jobs")->fetchColumn(),
];

// 2. System Health & Environment Diagnostics
$mysqlVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
$systemHealth = [
    'php_version' => PHP_VERSION,
    'mysql_version' => $mysqlVersion,
    'pdo_driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
    'session_active' => (session_status() === PHP_SESSION_ACTIVE),
    'storage_writable' => is_writable(STORAGE_PATH),
    'uploads_writable' => is_writable(UPLOADS_PATH),
    'timezone' => date_default_timezone_get()
];

// 3. System Default Categories (user_id IS NULL)
$defaultExpenseCats = $pdo->query("SELECT * FROM expense_categories WHERE user_id IS NULL ORDER BY id ASC")->fetchAll();

// 4. Recent Audit Logs (Scoped to current user's security events for privacy)
$auditStmt = $pdo->prepare("
    SELECT id, action, entity_type, entity_id, ip_address, created_at
    FROM audit_logs
    WHERE user_id = :uid
    ORDER BY created_at DESC
    LIMIT 15
");
$auditStmt->execute([':uid' => $userId]);
$recentAudits = $auditStmt->fetchAll();

$pageTitle = 'System Health & Administration';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">System Health & Governance</h4>
        <p class="text-secondary fs-7 mb-0">Privacy-preserving system diagnostics, aggregate platform metrics, and security audit trail.</p>
    </div>
    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
        <i class="bi bi-check-circle-fill me-1"></i> All Core Services Operational
    </span>
</div>

<!-- Strict Privacy Policy Banner -->
<div class="alert alert-primary border border-primary-subtle bg-primary-subtle rounded-3 p-3.5 mb-4">
    <div class="d-flex align-items-start gap-2.5 fs-7 text-dark">
        <i class="bi bi-shield-lock-fill text-primary fs-5 mt-0.5"></i>
        <div>
            <strong>Zero-Knowledge Financial Privacy Enforcement:</strong>
            <p class="mb-0 text-secondary mt-1">
                In accordance with EBAP security standards, system administration views strictly prohibit access to individual users' transaction descriptions, income amounts, expenses, or personal financial reports. Only non-sensitive aggregate counts and technical health metrics are surfaced here.
            </p>
        </div>
    </div>
</div>

<!-- Aggregate Non-Sensitive Statistics -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-label">Registered Accounts</span>
            <div class="stat-value text-dark"><?= $stats['total_users'] ?></div>
            <div class="stat-sub"><?= $stats['active_users'] ?> active user(s)</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-label">Default Categories</span>
            <div class="stat-value text-primary"><?= $stats['system_expense_cats'] + $stats['system_income_cats'] ?></div>
            <div class="stat-sub"><?= $stats['system_expense_cats'] ?> expense / <?= $stats['system_income_cats'] ?> income</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-label">Audit Log Events</span>
            <div class="stat-value text-dark"><?= $stats['total_audit_events'] ?></div>
            <div class="stat-sub">Security & mutation events tracked</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <span class="stat-label">CSV Import Jobs</span>
            <div class="stat-value text-success"><?= $stats['total_import_jobs'] ?></div>
            <div class="stat-sub">Completed batch imports</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Environment & System Health Diagnostics -->
    <div class="col-lg-5">
        <div class="ebap-card bg-white p-4 h-100 shadow-sm">
            <h6 class="fw-bold text-dark mb-3 fs-6"><i class="bi bi-cpu me-2 text-primary"></i>Runtime & Database Health</h6>
            <ul class="list-group list-group-flush fs-7">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                    <span class="text-secondary">PHP Runtime Version</span>
                    <span class="badge bg-light text-dark border font-monospace"><?= e($systemHealth['php_version']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                    <span class="text-secondary">Database Server (InnoDB)</span>
                    <span class="badge bg-light text-dark border font-monospace"><?= e($systemHealth['mysql_version']) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                    <span class="text-secondary">PDO Prepared Statements</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Native (Emulation Off)</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                    <span class="text-secondary">Session Protection</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">HttpOnly &bull; SameSite=Lax</span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                    <span class="text-secondary">Storage Directory Writable</span>
                    <span class="badge <?= $systemHealth['storage_writable'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                        <?= $systemHealth['storage_writable'] ? 'Writable' : 'Read-Only' ?>
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                    <span class="text-secondary">Uploads Directory Writable</span>
                    <span class="badge <?= $systemHealth['uploads_writable'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                        <?= $systemHealth['uploads_writable'] ? 'Writable' : 'Read-Only' ?>
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Security Audit Log -->
    <div class="col-lg-7">
        <div class="ebap-card bg-white p-4 h-100 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold text-dark mb-0 fs-6"><i class="bi bi-journal-check me-2 text-primary"></i>Security Audit Trail</h6>
                    <small class="text-muted">Recent authentication and record mutation events (No private amounts logged)</small>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 310px; overflow-y: auto;">
                <table class="table ebap-table align-middle">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Action</th>
                            <th>Entity</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAudits as $log): ?>
                            <tr>
                                <td class="font-monospace text-muted fs-7"><?= e($log['created_at']) ?></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= e($log['action']) ?></span></td>
                                <td class="text-secondary fs-7"><?= e($log['entity_type']) ?> <?= $log['entity_id'] ? '#' . (int)$log['entity_id'] : '' ?></td>
                                <td class="font-monospace text-muted fs-7"><?= e($log['ip_address'] ?? '127.0.0.1') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- System Default Categories Table -->
<div class="ebap-card bg-white p-4 mb-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold text-dark mb-0 fs-6">Global System Default Categories</h6>
            <small class="text-muted">Standardized classifications available across all user workspaces</small>
        </div>
        <a href="<?= url('settings/categories.php') ?>" class="btn btn-sm btn-ebap-outline">Manage Custom Categories &rarr;</a>
    </div>

    <div class="row g-2">
        <?php foreach ($defaultExpenseCats as $cat): ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div class="p-2.5 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                    <span class="category-badge" style="background-color: <?= e($cat['category_color']) ?>18; color: <?= e($cat['category_color']) ?>;">
                        <i class="bi <?= e($cat['category_icon']) ?> me-1.5"></i> <?= e($cat['category_name']) ?>
                    </span>
                    <small class="text-muted" style="font-size: 0.7rem;"><?= $cat['is_essential'] ? 'Essential' : 'Disc.' ?></small>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
