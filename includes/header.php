<?php
declare(strict_types=1);

/**
 * Global Header Partial
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = $pageTitle ?? 'Dashboard';
$fullTitle = $pageTitle . ' - ' . APP_NAME;
$isAppPage = $isAppPage ?? isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($fullTitle) ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Chart.js 4.4 -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <!-- Custom EBAP CSS -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

<?php if ($isAppPage): ?>
<div id="ebap-layout">
    <!-- Sidebar backdrop for mobile -->
    <div id="ebap-sidebar-backdrop" class="modal-backdrop fade d-lg-none" style="display:none; z-index:1025;"></div>

    <!-- Application Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div id="ebap-main">
        <!-- Top Navigation Bar -->
        <?php require_once __DIR__ . '/navbar.php'; ?>

        <!-- Alerts Flash Messages -->
        <div class="px-4 pt-3">
            <?php require_once __DIR__ . '/alerts.php'; ?>
        </div>

        <div class="content-wrapper">
<?php else: ?>
    <!-- Public / Standalone Navigation -->
    <div class="px-4 pt-3">
        <?php require_once __DIR__ . '/alerts.php'; ?>
    </div>
<?php endif; ?>
