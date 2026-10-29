<?php
declare(strict_types=1);

/**
 * CSRF Protection Module
 * Expense Behaviour Analytical Platform (EBAP)
 */

if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken(): string {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > 7200) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('getCsrfToken')) {
    function getCsrfToken(): string {
        return generateCsrfToken();
    }
}

if (!function_exists('getCsrfInput')) {
    function getCsrfInput(): string {
        $token = htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken(?string $token): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('requireCsrfToken')) {
    function requireCsrfToken(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!verifyCsrfToken($token)) {
                http_response_code(403);
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'Invalid or expired CSRF token. Please refresh the page and try again.']);
                } else {
                    die("
                        <div style='font-family: system-ui, sans-serif; max-width: 500px; margin: 60px auto; padding: 24px; border: 1px solid #f87171; border-radius: 8px; background-color: #fef2f2; color: #991b1b;'>
                            <h3 style='margin-top:0;'>403 - Security Verification Failed</h3>
                            <p>Invalid or expired CSRF security token. Please return to the previous page, refresh, and resubmit.</p>
                            <a href='javascript:history.back()' style='display:inline-block; margin-top:12px; color:#2563eb;'>&larr; Go Back</a>
                        </div>
                    ");
                }
                exit;
            }
        }
    }
}
