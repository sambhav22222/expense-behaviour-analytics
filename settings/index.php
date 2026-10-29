<?php
declare(strict_types=1);

/**
 * User Settings & Account Security Workspace
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/AuthService.php';

requireAuth();

$userId = getAuthUserId();
$user = getAuthUser();
$authService = new AuthService();

$profileErrors = [];
$passwordErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $currency = trim($_POST['preferred_currency'] ?? 'INR');
        $timezone = trim($_POST['timezone'] ?? 'Asia/Kolkata');

        $res = $authService->updateProfile($userId, $fullName, $currency, $timezone);
        if ($res['success']) {
            setFlash('success', 'Profile and currency preferences updated successfully.');
            header('Location: ' . url('settings/index.php'));
            exit;
        } else {
            $profileErrors[] = $res['error'];
        }
    } elseif ($formType === 'password') {
        $currPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confPass = $_POST['confirm_new_password'] ?? '';

        $res = $authService->changePassword($userId, $currPass, $newPass, $confPass);
        if ($res['success']) {
            setFlash('success', 'Password successfully updated.');
            header('Location: ' . url('settings/index.php'));
            exit;
        } else {
            $passwordErrors[] = $res['error'];
        }
    } elseif ($formType === 'deactivate') {
        $confirmPass = $_POST['deactivate_password'] ?? '';
        $res = $authService->deactivateAccount($userId, $confirmPass);
        if ($res['success']) {
            logoutUser();
            setFlash('info', 'Your account has been deactivated.');
            header('Location: ' . url('login.php'));
            exit;
        } else {
            $passwordErrors[] = $res['error'];
        }
    }
}

$pageTitle = 'Account Settings';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8" style="max-width: 800px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Account & Preferences</h4>
                <p class="text-secondary fs-7 mb-0">Manage display currency, localization, categories, and account security.</p>
            </div>
            <a href="<?= url('settings/categories.php') ?>" class="btn btn-sm btn-ebap-outline shadow-sm">
                <i class="bi bi-tags me-1"></i> Manage Custom Categories
            </a>
        </div>

        <!-- Section 1: Profile & Preferences -->
        <div class="ebap-card bg-white p-4 p-md-5 mb-4 shadow-sm">
            <h5 class="fw-bold text-dark mb-1 fs-6">Profile & Currency Localization</h5>
            <p class="text-muted fs-7 mb-4">Settings applied to all financial dashboard computations.</p>

            <?php if (!empty($profileErrors)): ?>
                <div class="alert alert-danger py-2.5 px-3 rounded-3 mb-3 fs-7">
                    <?= e($profileErrors[0]) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('settings/index.php') ?>">
                <?= getCsrfInput() ?>
                <input type="hidden" name="form_type" value="profile">

                <div class="mb-3">
                    <label class="form-label fs-7 fw-semibold text-secondary">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fs-7 fw-semibold text-secondary">Email Address</label>
                    <input type="email" class="form-control bg-light text-muted" value="<?= e($user['email']) ?>" readonly>
                    <small class="text-muted" style="font-size: 0.72rem;">Primary identity and login credential.</small>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fs-7 fw-semibold text-secondary">Display Currency</label>
                        <select name="preferred_currency" class="form-select">
                            <option value="INR" <?= ($user['preferred_currency'] === 'INR') ? 'selected' : '' ?>>Indian Rupee (₹ INR)</option>
                            <option value="USD" <?= ($user['preferred_currency'] === 'USD') ? 'selected' : '' ?>>US Dollar ($ USD)</option>
                            <option value="EUR" <?= ($user['preferred_currency'] === 'EUR') ? 'selected' : '' ?>>Euro (€ EUR)</option>
                            <option value="GBP" <?= ($user['preferred_currency'] === 'GBP') ? 'selected' : '' ?>>British Pound (£ GBP)</option>
                            <option value="JPY" <?= ($user['preferred_currency'] === 'JPY') ? 'selected' : '' ?>>Japanese Yen (¥ JPY)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-7 fw-semibold text-secondary">Timezone</label>
                        <input type="text" name="timezone" class="form-control" value="<?= e($user['timezone'] ?? 'Asia/Kolkata') ?>" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-ebap px-4">Save Preferences</button>
                </div>
            </form>
        </div>

        <!-- Section 2: Security & Password Change -->
        <div class="ebap-card bg-white p-4 p-md-5 mb-4 shadow-sm">
            <h5 class="fw-bold text-dark mb-1 fs-6">Change Password</h5>
            <p class="text-muted fs-7 mb-4">Protect your personal financial records with a strong password.</p>

            <?php if (!empty($passwordErrors)): ?>
                <div class="alert alert-danger py-2.5 px-3 rounded-3 mb-3 fs-7">
                    <?= e($passwordErrors[0]) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('settings/index.php') ?>">
                <?= getCsrfInput() ?>
                <input type="hidden" name="form_type" value="password">

                <div class="mb-3">
                    <label class="form-label fs-7 fw-semibold text-secondary">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fs-7 fw-semibold text-secondary">New Password</label>
                        <input type="password" name="new_password" class="form-control" minlength="8" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-7 fw-semibold text-secondary">Confirm New Password</label>
                        <input type="password" name="confirm_new_password" class="form-control" minlength="8" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-ebap px-4">Update Password</button>
                </div>
            </form>
        </div>

        <!-- Section 3: Personal Data Export & Account Governance -->
        <div class="ebap-card bg-white p-4 p-md-5 mb-4 shadow-sm">
            <h5 class="fw-bold text-dark mb-1 fs-6">Data Export & Account Governance</h5>
            <p class="text-muted fs-7 mb-3">Download your complete transaction history or deactivate your account.</p>

            <div class="d-flex flex-wrap gap-2 pb-4 mb-4 border-bottom">
                <a href="<?= url('reports/export.php?type=expenses') ?>" class="btn btn-sm btn-ebap-outline">
                    <i class="bi bi-download me-1"></i> Export All Expenses (CSV)
                </a>
                <a href="<?= url('reports/export.php?type=income') ?>" class="btn btn-sm btn-ebap-outline">
                    <i class="bi bi-download me-1"></i> Export All Income (CSV)
                </a>
                <a href="<?= url('reports/index.php') ?>" class="btn btn-sm btn-ebap-outline">
                    <i class="bi bi-printer me-1"></i> Printable Financial Report
                </a>
            </div>

            <h6 class="fw-bold text-danger fs-7 mb-1">Deactivate Account</h6>
            <p class="text-secondary fs-7 mb-3">Deactivating your account suspends login access while preserving historical audit integrity. Confirm with your current password to proceed.</p>

            <form method="POST" action="<?= url('settings/index.php') ?>" class="row g-2 align-items-center" data-confirm="Are you sure you want to deactivate your account?">
                <?= getCsrfInput() ?>
                <input type="hidden" name="form_type" value="deactivate">
                <div class="col-sm-7">
                    <input type="password" name="deactivate_password" class="form-control form-control-sm" placeholder="Enter current password to confirm..." required>
                </div>
                <div class="col-sm-5">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                        <i class="bi bi-person-x me-1"></i> Deactivate Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
