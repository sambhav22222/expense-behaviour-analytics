<?php
declare(strict_types=1);

/**
 * Flash Message & Alert Partial
 * Expense Behaviour Analytical Platform (EBAP)
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$alertTypes = [
    'flash_success' => ['class' => 'alert-success', 'icon' => 'bi-check-circle-fill'],
    'flash_error'   => ['class' => 'alert-danger',  'icon' => 'bi-exclamation-triangle-fill'],
    'flash_warning' => ['class' => 'alert-warning', 'icon' => 'bi-exclamation-circle-fill'],
    'flash_info'    => ['class' => 'alert-info',    'icon' => 'bi-info-circle-fill'],
];

foreach ($alertTypes as $key => $meta):
    if (!empty($_SESSION[$key])):
        $messages = is_array($_SESSION[$key]) ? $_SESSION[$key] : [$_SESSION[$key]];
        unset($_SESSION[$key]);
        foreach ($messages as $msg):
?>
    <div class="alert <?= $meta['class'] ?> alert-dismissible fade show d-flex align-items-center py-2.5 px-3 rounded-3 shadow-sm mb-3" role="alert">
        <i class="bi <?= $meta['icon'] ?> me-2.5 fs-5"></i>
        <div class="flex-grow-1 fs-7"><?= e($msg) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php 
        endforeach;
    endif;
endforeach;

// Also support getFlashes() if used
$flashes = getFlashes();
foreach ($flashes as $type => $messages):
    $meta = match ($type) {
        'success' => ['class' => 'alert-success', 'icon' => 'bi-check-circle-fill'],
        'warning' => ['class' => 'alert-warning', 'icon' => 'bi-exclamation-circle-fill'],
        'info'    => ['class' => 'alert-info',    'icon' => 'bi-info-circle-fill'],
        default   => ['class' => 'alert-danger',  'icon' => 'bi-exclamation-triangle-fill'],
    };
    foreach ($messages as $msg):
?>
    <div class="alert <?= $meta['class'] ?> alert-dismissible fade show d-flex align-items-center py-2.5 px-3 rounded-3 shadow-sm mb-3" role="alert">
        <i class="bi <?= $meta['icon'] ?> me-2.5 fs-5"></i>
        <div class="flex-grow-1 fs-7"><?= e($msg) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php
    endforeach;
endforeach;
?>
