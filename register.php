<?php
declare(strict_types=1);

/**
 * User Registration Page
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/services/AuthService.php';

requireGuest();

$authService = new AuthService();
$errors = [];
$fullName = '';
$email = '';
$currency = 'INR';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $currency = trim($_POST['preferred_currency'] ?? 'INR');

    $result = $authService->register($fullName, $email, $password, $confirmPassword, $currency);

    if ($result['success']) {
        // Automatically sign in the newly registered user
        loginUser($result['user']);
        setFlash('success', 'Account created successfully! Welcome to EBAP.');
        header('Location: ' . url('dashboard/index.php'));
        exit;
    } else {
        $errors = $result['errors'];
    }
}

$pageTitle = 'Create Account';
$isAppPage = false;
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-vh-100 d-flex flex-column justify-content-center py-5" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
    <div class="container" style="max-width: 520px;">
        <!-- Brand Header -->
        <div class="text-center mb-4">
            <a href="<?= url('index.php') ?>" class="text-decoration-none d-inline-flex align-items-center gap-2 mb-2">
                <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 42px; height: 42px;">
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
                <span class="fw-bold fs-4 text-dark">EBAP</span>
            </a>
            <h4 class="fw-bold text-dark mb-1">Create your account</h4>
            <p class="text-secondary fs-7">Start tracking expenses and discovering behavioral patterns</p>
        </div>

        <!-- Registration Card -->
        <div class="ebap-card p-4 p-sm-5 bg-white shadow">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger py-2.5 px-3 rounded-3 mb-4 fs-7">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= url('register.php') ?>" novalidate>
                <?= getCsrfInput() ?>

                <div class="mb-3">
                    <label for="full_name" class="form-label fs-7 fw-semibold text-secondary">Full Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="full_name" name="full_name" value="<?= e($fullName) ?>" placeholder="e.g. John Doe" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fs-7 fw-semibold text-secondary">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" value="<?= e($email) ?>" placeholder="name@example.com" required>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-6">
                        <label for="password" class="form-label fs-7 fw-semibold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="At least 8 chars" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label for="confirm_password" class="form-label fs-7 fw-semibold text-secondary">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" class="form-control border-start-0 ps-0" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="preferred_currency" class="form-label fs-7 fw-semibold text-secondary">Preferred Display Currency</label>
                    <select class="form-select" id="preferred_currency" name="preferred_currency">
                        <option value="INR" <?= $currency === 'INR' ? 'selected' : '' ?>>Indian Rupee (₹ INR)</option>
                        <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>US Dollar ($ USD)</option>
                        <option value="EUR" <?= $currency === 'EUR' ? 'selected' : '' ?>>Euro (€ EUR)</option>
                        <option value="GBP" <?= $currency === 'GBP' ? 'selected' : '' ?>>British Pound (£ GBP)</option>
                        <option value="JPY" <?= $currency === 'JPY' ? 'selected' : '' ?>>Japanese Yen (¥ JPY)</option>
                    </select>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-ebap py-2.5 fs-7 fw-semibold">
                        Complete Registration
                    </button>
                </div>
            </form>
        </div>

        <p class="text-center text-secondary mt-4 fs-7">
            Already have an account? <a href="<?= url('login.php') ?>" class="text-primary fw-semibold text-decoration-none">Sign in here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
