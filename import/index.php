<?php
declare(strict_types=1);

/**
 * CSV Import Workspace
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/ImportService.php';

requireAuth();

$userId = getAuthUserId();
$importService = new ImportService();
$preview = null;
$importReport = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $action = $_POST['action'] ?? 'preview';

    if ($action === 'preview' && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'File upload failed. Please try again.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $error = 'Invalid file type. Only standard .csv files are supported.';
            } else {
                // Move temporarily to storage
                $tempPath = STORAGE_PATH . '/import_' . $userId . '_' . time() . '.csv';
                if (move_uploaded_file($file['tmp_name'], $tempPath)) {
                    $preview = $importService->previewCsv($tempPath);
                    if ($preview['success']) {
                        $_SESSION['pending_import_file'] = $tempPath;
                    } else {
                        $error = $preview['error'];
                        @unlink($tempPath);
                    }
                } else {
                    $error = 'Failed to process uploaded file.';
                }
            }
        }
    } elseif ($action === 'confirm') {
        $tempPath = $_SESSION['pending_import_file'] ?? null;
        if (!$tempPath || !file_exists($tempPath)) {
            $error = 'Import session expired or file not found. Please re-upload.';
        } else {
            $importReport = $importService->importExpenses($userId, $tempPath);
            @unlink($tempPath);
            unset($_SESSION['pending_import_file']);

            if ($importReport['success']) {
                setFlash('success', "Import successful: {$importReport['imported_count']} transactions imported into your account.");
            }
        }
    }
}

$pageTitle = 'Import CSV Transactions';
$isAppPage = true;
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h4 class="fw-bold text-dark mb-1">Data Import (CSV)</h4>
        <p class="text-secondary fs-7 mb-0">Import transaction records safely with preview validation, header checking, and duplicate safeguards.</p>
    </div>
    <div>
        <a href="<?= url('import/template.php') ?>" class="btn btn-sm btn-ebap-outline shadow-sm">
            <i class="bi bi-file-earmark-arrow-down me-1"></i> Download Sample CSV Template
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2.5 px-3 rounded-3 mb-4 fs-7">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($error) ?>
    </div>
<?php endif; ?>

<!-- Step 1: Upload Card -->
<?php if (!$preview && !$importReport): ?>
    <div class="row justify-content-center">
        <div class="col-lg-8" style="max-width: 680px;">
            <div class="ebap-card bg-white p-4 p-md-5 shadow-sm text-center">
                <div class="rounded-circle bg-primary-subtle text-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                    <i class="bi bi-cloud-arrow-up fs-3"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">Upload Expense CSV</h5>
                <p class="text-secondary fs-7 mb-4 mx-auto" style="max-width: 440px;">
                    Ensure your CSV includes columns for <strong>Title</strong>, <strong>Amount</strong>, and <strong>Date</strong>. You will preview the data before any records are saved.
                </p>

                <form method="POST" action="<?= url('import/index.php') ?>" enctype="multipart/form-data">
                    <?= getCsrfInput() ?>
                    <input type="hidden" name="action" value="preview">

                    <div class="mb-4">
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>

                    <button type="submit" class="btn btn-ebap px-4 py-2">
                        <i class="bi bi-eye me-1.5"></i> Upload & Preview Data
                    </button>
                </form>
            </div>
        </div>
    </div>

<!-- Step 2: Preview Card -->
<?php elseif ($preview && $preview['success']): ?>
    <div class="ebap-card bg-white p-4 mb-4 shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-0 fs-6">CSV Preview & Header Verification</h5>
                <small class="text-muted">Review the detected columns and sample rows below before confirming database write.</small>
            </div>
            <span class="badge bg-primary-subtle text-primary border font-monospace">~<?= $preview['estimated_rows'] ?> Total Rows Found</span>
        </div>

        <div class="table-responsive mb-4">
            <table class="table ebap-table align-middle">
                <thead>
                    <tr>
                        <?php foreach ($preview['headers'] as $h): ?>
                            <th><?= e($h) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview['preview_rows'] as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td class="fs-7 text-truncate" style="max-width: 200px;"><?= e($cell) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form method="POST" action="<?= url('import/index.php') ?>" class="d-flex justify-content-between align-items-center pt-3 border-top">
            <?= getCsrfInput() ?>
            <input type="hidden" name="action" value="confirm">

            <a href="<?= url('import/index.php') ?>" class="btn btn-sm btn-ebap-outline">Cancel / Re-upload</a>
            <button type="submit" class="btn btn-ebap px-4">
                <i class="bi bi-check2-circle me-1.5"></i> Confirm & Import Into Database
            </button>
        </form>
    </div>

<!-- Step 3: Import Report -->
<?php elseif ($importReport): ?>
    <div class="ebap-card bg-white p-4 mb-4 shadow-sm">
        <h5 class="fw-bold text-dark mb-3">Import Execution Report</h5>

        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="p-3 bg-success-subtle border border-success-subtle rounded-3">
                    <small class="text-success fw-bold d-block" style="font-size: 0.72rem;">SUCCESSFULLY IMPORTED</small>
                    <span class="fs-4 fw-bold text-success font-monospace"><?= $importReport['imported_count'] ?? 0 ?> Records</span>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="p-3 bg-danger-subtle border border-danger-subtle rounded-3">
                    <small class="text-danger fw-bold d-block" style="font-size: 0.72rem;">SKIPPED / INVALID ROWS</small>
                    <span class="fs-4 fw-bold text-danger font-monospace"><?= $importReport['failed_count'] ?? 0 ?> Records</span>
                </div>
            </div>
        </div>

        <?php if (!empty($importReport['errors'])): ?>
            <h6 class="fw-bold text-danger fs-7 mb-2">Row-Level Error Details:</h6>
            <div class="alert alert-light border p-3 rounded-3 font-monospace fs-7" style="max-height: 200px; overflow-y: auto;">
                <ul class="mb-0 ps-3">
                    <?php foreach ($importReport['errors'] as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
            <a href="<?= url('import/index.php') ?>" class="btn btn-sm btn-ebap-outline">Import Another File</a>
            <a href="<?= url('expenses/index.php') ?>" class="btn btn-sm btn-ebap">View Transactions &rarr;</a>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
