<?php
declare(strict_types=1);

/**
 * User Logout Script
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
setFlash('info', 'You have been successfully signed out.');
header('Location: ' . url('login.php'));
exit;
