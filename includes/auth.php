<?php
declare(strict_types=1);

/**
 * Authentication & Session Management Module
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/csrf.php';

/**
 * Initialize a hardened, secure PHP session
 */
function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                   (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT_SECONDS,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_name('EBAP_SESSID');
        session_start();

        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS)) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
    }
}

// Start session automatically upon inclusion
startSecureSession();

/**
 * Check if a user is logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user']);
}

/**
 * Ensure user is authenticated, otherwise redirect to login page
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        $_SESSION['redirect_after_login'] = $currentUrl;
        $_SESSION['flash_error'] = 'Please sign in to access your dashboard.';
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/**
 * Ensure user is guest, otherwise redirect to dashboard
 */
function requireGuest(): void {
    if (isLoggedIn()) {
        header('Location: ' . APP_URL . '/dashboard/index.php');
        exit;
    }
}

/**
 * Return current logged in user array
 */
function getAuthUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Return current authenticated user ID
 */
function getAuthUserId(): int {
    if (!isLoggedIn()) {
        throw new RuntimeException("Access violation: User not authenticated.");
    }
    return (int)$_SESSION['user_id'];
}

/**
 * Return user preferred currency code
 */
function getAuthCurrency(): string {
    return $_SESSION['user']['preferred_currency'] ?? DEFAULT_CURRENCY;
}

/**
 * Currency Symbol Helper
 */
function getCurrencySymbol(?string $currency = null): string {
    global $currencySymbols;
    $curr = $currency ?? getAuthCurrency();
    return $currencySymbols[$curr] ?? $curr . ' ';
}

/**
 * Safe currency amount formatting
 */
function formatCurrency(float|int|string|null $amount, ?string $currency = null): string {
    $num = (float)($amount ?? 0);
    $curr = $currency ?? getAuthCurrency();
    $symbol = getCurrencySymbol($curr);
    
    // Format with commas and 2 decimals
    return $symbol . number_format($num, 2);
}

/**
 * Set authenticated user session with session ID regeneration
 */
function loginUser(array $user): void {
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'preferred_currency' => $user['preferred_currency'] ?? 'INR',
        'timezone' => $user['timezone'] ?? 'Asia/Kolkata',
        'account_status' => $user['account_status'] ?? 'active'
    ];
    $_SESSION['last_activity'] = time();

    // Log login in audit
    logAudit((int)$user['id'], 'LOGIN', 'users', (int)$user['id'], 'User logged in successfully.');
}

/**
 * Terminate user session cleanly
 */
function logoutUser(): void {
    if (isLoggedIn()) {
        logAudit(getAuthUserId(), 'LOGOUT', 'users', getAuthUserId(), 'User logged out.');
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Login rate-limiting check
 */
function isRateLimited(string $ip, string $email): bool {
    $pdo = getDBConnection();
    $cutoff = date('Y-m-d H:i:s', time() - (LOGIN_LOCKOUT_MINUTES * 60));

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as failed_count 
        FROM login_attempts 
        WHERE (ip_address = :ip OR email = :email) 
          AND attempted_at >= :cutoff
    ");
    $stmt->execute([':ip' => $ip, ':email' => $email, ':cutoff' => $cutoff]);
    $result = $stmt->fetch();

    return ($result['failed_count'] ?? 0) >= MAX_LOGIN_ATTEMPTS;
}

/**
 * Record a failed login attempt
 */
function recordFailedLogin(string $ip, string $email): void {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, email, attempted_at) VALUES (:ip, :email, NOW())");
    $stmt->execute([':ip' => $ip, ':email' => $email]);
}

/**
 * Clear failed login attempts after successful authentication
 */
function clearLoginAttempts(string $ip, string $email): void {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip OR email = :email");
    $stmt->execute([':ip' => $ip, ':email' => $email]);
}

/**
 * Record an action in the audit log
 */
function logAudit(int $userId, string $action, string $entityType, ?int $entityId = null, ?string $details = null): void {
    try {
        $pdo = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
            VALUES (:uid, :act, :ent_type, :ent_id, :det, :ip, NOW())
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':act' => substr($action, 0, 100),
            ':ent_type' => substr($entityType, 0, 50),
            ':ent_id' => $entityId,
            ':det' => $details,
            ':ip' => substr($ip, 0, 45)
        ]);
    } catch (Exception $e) {
        // Do not break the user flow if audit logging fails
        error_log("Audit log failed: " . $e->getMessage());
    }
}
