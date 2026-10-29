<?php
declare(strict_types=1);

/**
 * Application Configuration
 * Expense Behaviour Analytical Platform (EBAP)
 */

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Environment Detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
            (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));

// Detect base URL whether accessed via root or subdirectory
$baseSubdir = '/expense-behaviour-analytics';
if (strpos($scriptDir, '/expense-behaviour-analytics') !== false) {
    $scriptDir = substr($scriptDir, 0, strpos($scriptDir, '/expense-behaviour-analytics') + strlen('/expense-behaviour-analytics'));
}

define('APP_NAME', 'Expense Behaviour Analytical Platform');
define('APP_SHORT_NAME', 'EBAP');
define('APP_VERSION', '1.0.0');
define('APP_URL', rtrim($protocol . $host . $scriptDir, '/'));
define('ROOT_PATH', dirname(__DIR__));
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Default Localization
define('DEFAULT_CURRENCY', 'INR');
define('DEFAULT_TIMEZONE', 'Asia/Kolkata');

// Currency Mapping
$currencySymbols = [
    'INR' => '₹',
    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
    'JPY' => '¥',
    'CAD' => 'CA$',
    'AUD' => 'A$'
];

// Security settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);
define('SESSION_TIMEOUT_SECONDS', 7200); // 2 hours
