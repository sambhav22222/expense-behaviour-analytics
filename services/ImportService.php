<?php
declare(strict_types=1);

/**
 * CSV Import & Validation Service
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/CategoryService.php';

class ImportService {
    private PDO $db;
    private CategoryService $categories;

    public function __construct(?PDO $db = null, ?CategoryService $categories = null) {
        $this->db = $db ?? getDBConnection();
        $this->categories = $categories ?? new CategoryService($this->db);
    }

    /**
     * Preview an uploaded CSV file (first 5 rows) and validate headers
     */
    public function previewCsv(string $filePath): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['success' => false, 'error' => 'Uploaded file is not readable.'];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Could not open CSV file.'];
        }

        // Strip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle, 2048, ',');
        if (!$headers) {
            fclose($handle);
            return ['success' => false, 'error' => 'The uploaded file is empty.'];
        }

        // Clean header names
        $normalizedHeaders = array_map(fn($h) => strtolower(trim((string)$h)), $headers);

        $previewRows = [];
        $rowCount = 0;
        while (($row = fgetcsv($handle, 2048, ',')) !== false && $rowCount < 5) {
            $previewRows[] = $row;
            $rowCount++;
        }

        // Count total remaining rows
        while (fgetcsv($handle, 2048, ',') !== false) {
            $rowCount++;
        }
        fclose($handle);

        return [
            'success' => true,
            'headers' => $headers,
            'normalized_headers' => $normalizedHeaders,
            'preview_rows' => $previewRows,
            'estimated_rows' => $rowCount
        ];
    }

    /**
     * Process and commit CSV rows to the expenses table
     */
    public function importExpenses(int $userId, string $filePath): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['success' => false, 'error' => 'Import file not found.'];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Unable to read import file.'];
        }

        // Strip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle, 2048, ',');
        if (!$headers) {
            fclose($handle);
            return ['success' => false, 'error' => 'Empty CSV file.'];
        }

        // Map column indices
        $normalizedHeaders = array_map(fn($h) => strtolower(trim((string)$h)), $headers);
        $idxTitle = array_search('title', $normalizedHeaders, true);
        if ($idxTitle === false) $idxTitle = array_search('expense title', $normalizedHeaders, true);
        if ($idxTitle === false) $idxTitle = array_search('description', $normalizedHeaders, true);

        $idxAmount = array_search('amount', $normalizedHeaders, true);
        $idxDate = array_search('date', $normalizedHeaders, true);
        if ($idxDate === false) $idxDate = array_search('transaction date', $normalizedHeaders, true);

        $idxCategory = array_search('category', $normalizedHeaders, true);
        $idxPayment = array_search('payment method', $normalizedHeaders, true);
        if ($idxPayment === false) $idxPayment = array_search('payment', $normalizedHeaders, true);

        $idxMerchant = array_search('merchant', $normalizedHeaders, true);
        $idxNotes = array_search('notes', $normalizedHeaders, true);

        if ($idxTitle === false || $idxAmount === false || $idxDate === false) {
            fclose($handle);
            return [
                'success' => false, 
                'error' => 'CSV must include at minimum: "Title", "Amount", and "Date" columns.'
            ];
        }

        // Build category lookup map (lowercase name => id)
        $userCategories = $this->categories->getExpenseCategories($userId, true);
        $categoryMap = [];
        $fallbackCatId = $userCategories[0]['id'] ?? 1;

        foreach ($userCategories as $c) {
            $categoryMap[strtolower(trim($c['category_name']))] = (int)$c['id'];
        }

        $rowNum = 1; // Header is row 1
        $importedCount = 0;
        $errors = [];
        $validRows = [];

        while (($row = fgetcsv($handle, 2048, ',')) !== false) {
            $rowNum++;
            if (empty(array_filter($row))) {
                continue; // Skip blank line
            }

            $title = trim($row[$idxTitle] ?? '');
            $rawAmount = trim($row[$idxAmount] ?? '');
            $rawDate = trim($row[$idxDate] ?? '');
            $categoryName = ($idxCategory !== false && isset($row[$idxCategory])) ? trim($row[$idxCategory]) : '';
            $paymentMethod = ($idxPayment !== false && isset($row[$idxPayment])) ? trim($row[$idxPayment]) : 'UPI';
            $merchant = ($idxMerchant !== false && isset($row[$idxMerchant])) ? trim($row[$idxMerchant]) : null;
            $notes = ($idxNotes !== false && isset($row[$idxNotes])) ? trim($row[$idxNotes]) : null;

            // Row validation
            if (empty($title)) {
                $errors[] = "Row {$rowNum}: Title is empty.";
                continue;
            }

            // Clean amount: check for negative and remove currency symbols/commas
            if (str_starts_with(trim($rawAmount), '-') || (is_numeric($rawAmount) && (float)$rawAmount <= 0)) {
                $errors[] = "Row {$rowNum}: Invalid amount '{$rawAmount}'. Must be a positive number.";
                continue;
            }
            $cleanAmount = preg_replace('/[^0-9.]/', '', $rawAmount);
            if (!is_numeric($cleanAmount) || (float)$cleanAmount <= 0) {
                $errors[] = "Row {$rowNum}: Invalid amount '{$rawAmount}'. Must be a positive number.";
                continue;
            }
            $amount = (float)$cleanAmount;

            // Parse Date
            $parsedDate = $this->parseDate($rawDate);
            if (!$parsedDate) {
                $errors[] = "Row {$rowNum}: Unrecognized date format '{$rawDate}'. Use YYYY-MM-DD or DD/MM/YYYY.";
                continue;
            }

            // Category assignment
            $catKey = strtolower($categoryName);
            $catId = $categoryMap[$catKey] ?? $fallbackCatId;

            // Normalize payment method
            $allowedMethods = ['Cash', 'UPI', 'Credit Card', 'Debit Card', 'Net Banking', 'Cheque', 'Other'];
            $matchedPayment = 'Other';
            foreach ($allowedMethods as $m) {
                if (strcasecmp($m, $paymentMethod) === 0) {
                    $matchedPayment = $m;
                    break;
                }
            }

            $validRows[] = [
                'title' => substr($title, 0, 200),
                'amount' => $amount,
                'transaction_date' => $parsedDate,
                'category_id' => $catId,
                'payment_method' => $matchedPayment,
                'merchant' => $merchant ? substr($merchant, 0, 150) : null,
                'description' => $notes
            ];
        }
        fclose($handle);

        if (empty($validRows)) {
            return [
                'success' => false,
                'error' => 'No valid transactions found in the file.',
                'row_errors' => $errors
            ];
        }

        // Commit valid rows in a transaction
        try {
            $this->db->beginTransaction();

            $insertStmt = $this->db->prepare("
                INSERT INTO expenses (
                    user_id, category_id, title, amount, transaction_date,
                    payment_method, merchant, description, import_reference, created_at
                ) VALUES (
                    :uid, :cid, :title, :amount, :tx_date,
                    :payment, :merchant, :desc, 'CSV_IMPORT', NOW()
                )
            ");

            foreach ($validRows as $r) {
                $insertStmt->execute([
                    ':uid' => $userId,
                    ':cid' => $r['category_id'],
                    ':title' => $r['title'],
                    ':amount' => number_format($r['amount'], 2, '.', ''),
                    ':tx_date' => $r['transaction_date'],
                    ':payment' => $r['payment_method'],
                    ':merchant' => $r['merchant'],
                    ':desc' => $r['description']
                ]);
                $importedCount++;
            }

            // Record import job in import_jobs table
            $jobStmt = $this->db->prepare("
                INSERT INTO import_jobs (
                    user_id, import_type, filename, total_rows, successful_rows, failed_rows, status, created_at, completed_at
                ) VALUES (
                    :uid, 'expenses', :fname, :tot, :succ, :fail, 'completed', NOW(), NOW()
                )
            ");
            $jobStmt->execute([
                ':uid' => $userId,
                ':fname' => basename($filePath),
                ':tot' => $importedCount + count($errors),
                ':succ' => $importedCount,
                ':fail' => count($errors)
            ]);
            $jobId = (int)$this->db->lastInsertId();

            if (!empty($errors)) {
                $errStmt = $this->db->prepare("
                    INSERT INTO import_errors (import_job_id, row_number, error_message, created_at)
                    VALUES (:jid, :rnum, :msg, NOW())
                ");
                foreach ($errors as $idx => $errMsg) {
                    $errStmt->execute([
                        ':jid' => $jobId,
                        ':rnum' => $idx + 1,
                        ':msg' => $errMsg
                    ]);
                }
            }

            $this->db->commit();
            logAudit($userId, 'IMPORT_CSV_EXPENSES', 'expenses', $jobId, "Imported {$importedCount} transactions via CSV.");

            return [
                'success' => true,
                'job_id' => $jobId,
                'imported_count' => $importedCount,
                'failed_count' => count($errors),
                'errors' => $errors
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("CSV import error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Database error occurred during batch import.'];
        }
    }

    /**
     * Flexible date parser supporting multiple international formats
     */
    private function parseDate(string $rawDate): ?string {
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d', 'd M Y', 'd-M-Y'];
        foreach ($formats as $fmt) {
            $d = DateTime::createFromFormat($fmt, $rawDate);
            if ($d && $d->format($fmt) === $rawDate) {
                return $d->format('Y-m-d');
            }
        }
        // Try strtotime fallback
        $ts = strtotime($rawDate);
        if ($ts !== false && $ts > 0) {
            return date('Y-m-d', $ts);
        }
        return null;
    }
}
