<?php
declare(strict_types=1);

/**
 * User Login Page
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
$email = $_GET['email'] ?? '';

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    $result = $authService->authenticate($email, $password, $ip);

    if ($result['success']) {
        setFlash('success', 'Welcome back, ' . $result['user']['full_name'] . '!');
        $redirectUrl = $_SESSION['redirect_after_login'] ?? url('dashboard/index.php');
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        $errors[] = $result['error'];
    }
}

$pageTitle = 'Sign In';
$isAppPage = false;
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-vh-100 d-flex flex-column justify-content-center py-5" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
    <div class="container" style="max-width: 480px;">
        <!-- Brand Header -->
        <div class="text-center mb-4">
            <a href="<?= url('index.php') ?>" class="text-decoration-none d-inline-flex align-items-center gap-2 mb-2">
                <div class="rounded-3 bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 42px; height: 42px;">
                    <i class="bi bi-graph-up-arrow fs-4"></i>
                </div>
                <span class="fw-bold fs-4 text-dark">EBAP</span>
            </a>
            <h4 class="fw-bold text-dark mb-1">Welcome back</h4>
            <p class="text-secondary fs-7">Sign in to your Expense Behaviour Analytical account</p>
        </div>

        <!-- Login Card -->
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

            <form method="POST" action="<?= url('login.php') ?>" novalidate>
                <?= getCsrfInput() ?>

                <div class="mb-3">
                    <label for="email" class="form-label fs-7 fw-semibold text-secondary">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" value="<?= e($email) ?>" placeholder="name@example.com" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="password" class="form-label fs-7 fw-semibold text-secondary mb-0">Password</label>
                    </div>
                    <div class="input-group mt-1">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-ebap py-2.5 fs-7 fw-semibold">
                        Sign In to Dashboard
                    </button>
                </div>
            </form>

            <!-- Quick Demo Accounts (Viva & Evaluation Convenience) -->
            <div class="mt-4 pt-3 border-top">
                <span class="d-block text-center text-muted mb-2.5" style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Quick Demo Credentials</span>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary text-start py-2 px-3 rounded-3" onclick="fillCredentials('demo@ebap.local', 'Demo@123')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="d-block text-dark fs-7">Aarav Sharma (Demo User)</strong>
                                <small class="text-muted" style="font-size: 0.75rem;">demo@ebap.local | Demo@123 (Seeded 3-Month Data)</small>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Fill</span>
                        </div>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary text-start py-2 px-3 rounded-3" onclick="fillCredentials('student@ebap.local', 'Student@123')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="d-block text-dark fs-7">Priya Patel (Student Evaluator)</strong>
                                <small class="text-muted" style="font-size: 0.75rem;">student@ebap.local | Student@123 (Clean/Empty State)</small>
                            </div>
                            <span class="badge bg-secondary-subtle text-secondary border">Fill</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <p class="text-center text-secondary mt-4 fs-7">
            Don't have an account yet? <a href="<?= url('register.php') ?>" class="text-primary fw-semibold text-decoration-none">Create an account</a>
        </p>
    </div>
</div>

<script>
function fillCredentials(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
