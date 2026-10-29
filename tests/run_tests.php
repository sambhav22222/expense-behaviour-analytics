<?php
declare(strict_types=1);

/**
 * Automated Test Suite & QA Verification Runner
 * Expense Behaviour Analytical Platform (EBAP)
 * 
 * Executes functional, mathematical, and security verification tests.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/CategoryService.php';
require_once __DIR__ . '/../services/ExpenseService.php';
require_once __DIR__ . '/../services/IncomeService.php';
require_once __DIR__ . '/../services/BudgetService.php';
require_once __DIR__ . '/../services/RecurringExpenseService.php';
require_once __DIR__ . '/../services/GoalService.php';
require_once __DIR__ . '/../services/AnalyticsService.php';
require_once __DIR__ . '/../services/BehaviourInsightService.php';
require_once __DIR__ . '/../services/AnomalyDetectionService.php';
require_once __DIR__ . '/../services/ImportService.php';

class EBAPTestSuite {
    private PDO $db;
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];
    private ?int $testUserId = null;

    public function __construct() {
        $this->db = getDBConnection();
    }

    private function assert(string $testName, bool $condition, string $failureDetails = ''): void {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$testName}\n";
        } else {
            $this->failed++;
            $this->failures[] = "{$testName}: {$failureDetails}";
            echo "  [FAIL] {$testName} - {$failureDetails}\n";
        }
    }

    public function runAll(): void {
        echo "======================================================================\n";
        echo "EBAP AUTOMATED TEST SUITE EXECUTION\n";
        echo "Environment: PHP " . PHP_VERSION . " | MySQL " . $this->db->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
        echo "======================================================================\n\n";

        $this->setupTestUser();

        $this->runAuthTests();
        $this->runCategoryTests();
        $this->runExpenseCRUDTests();
        $this->runIncomeCRUDTests();
        $this->runBudgetCalculationTests();
        $this->runRecurringNormalizationTests();
        $this->runGoalProgressTests();
        $this->runAnalyticsAndMathTests();
        $this->runBehaviourInsightTests();
        $this->runAnomalyDetectionTests();
        $this->runCsvImportAndSanitizationTests();
        $this->runSecurityAndAuthorizationTests();

        $this->cleanupTestUser();

        echo "\n======================================================================\n";
        echo "TEST SUMMARY:\n";
        echo "Total Tests Run: " . ($this->passed + $this->failed) . "\n";
        echo "Passed: " . $this->passed . "\n";
        echo "Failed: " . $this->failed . "\n";
        if ($this->failed === 0) {
            echo "Status: ALL TESTS PASSED SUCCESSFULLY! (100% Pass Rate)\n";
        } else {
            echo "Status: SOME TESTS FAILED.\n";
            foreach ($this->failures as $f) {
                echo "  - {$f}\n";
            }
        }
        echo "======================================================================\n";
    }

    private function setupTestUser(): void {
        // Clean any old test runner user
        $email = 'test_runner_' . time() . '@ebap.local';
        $auth = new AuthService($this->db);
        $res = $auth->register('Automated Test Runner', $email, 'TestPass@123', 'TestPass@123', 'INR');
        if (!$res['success']) {
            die("Could not create test user: " . json_encode($res['errors']));
        }
        $this->testUserId = $res['user_id'];
    }

    private function cleanupTestUser(): void {
        if ($this->testUserId) {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $this->testUserId]);
        }
    }

    private function runAuthTests(): void {
        echo "--- 1. Authentication & Session Security Tests ---\n";
        $auth = new AuthService($this->db);

        // Test login with valid credentials
        $stmt = $this->db->prepare("SELECT email FROM users WHERE id = :id");
        $stmt->execute([':id' => $this->testUserId]);
        $email = $stmt->fetchColumn();

        $loginRes = $auth->authenticate($email, 'TestPass@123', '127.0.0.1');
        $this->assert('Authenticate with valid password', $loginRes['success'] === true);

        // Test login with invalid password
        $badLogin = $auth->authenticate($email, 'WrongPass123', '127.0.0.1');
        $this->assert('Reject invalid password', $badLogin['success'] === false);

        // Test password change
        $pwChange = $auth->changePassword($this->testUserId, 'TestPass@123', 'NewPass@456', 'NewPass@456');
        $this->assert('Change password successfully', $pwChange['success'] === true);

        $pwVerify = $auth->authenticate($email, 'NewPass@456', '127.0.0.1');
        $this->assert('Login with new changed password', $pwVerify['success'] === true);
    }

    private function runCategoryTests(): void {
        echo "\n--- 2. Category Hierarchy Tests ---\n";
        $catService = new CategoryService($this->db);

        $categories = $catService->getExpenseCategories($this->testUserId);
        $this->assert('Load default system categories', count($categories) >= 10);

        // Create custom user category
        $createRes = $catService->createExpenseCategory($this->testUserId, 'Pet Care & Veterinary', 'bi-heart', '#ec4899', false);
        $this->assert('Create custom expense category', $createRes['success'] === true && $createRes['id'] > 0);

        // Prevent duplicate category name for same user
        $dupRes = $catService->createExpenseCategory($this->testUserId, 'Pet Care & Veterinary', 'bi-heart', '#ec4899', false);
        $this->assert('Reject duplicate category name', $dupRes['success'] === false);
    }

    private function runExpenseCRUDTests(): void {
        echo "\n--- 3. Expense CRUD & Validation Tests ---\n";
        $expService = new ExpenseService($this->db);

        // Valid expense
        $expRes = $expService->createExpense($this->testUserId, [
            'title' => 'Weekly Supermarket Supplies',
            'amount' => '3250.75',
            'transaction_date' => '2026-10-15',
            'category_id' => 1, // Food & Groceries
            'payment_method' => 'Credit Card',
            'merchant' => 'HyperMarket',
            'description' => 'Household grocery run',
            'is_recurring' => 0
        ]);
        $this->assert('Create valid expense', $expRes['success'] === true);
        $expId = $expRes['id'];

        // Retrieve and check ownership
        $record = $expService->getExpenseById($this->testUserId, $expId);
        $this->assert('Read created expense by ID', $record !== null && (float)$record['amount'] === 3250.75);

        // Validation: reject negative amount
        $badAmount = $expService->createExpense($this->testUserId, [
            'title' => 'Invalid Negative Amount',
            'amount' => '-500',
            'transaction_date' => '2026-10-15',
            'category_id' => 1
        ]);
        $this->assert('Reject negative transaction amount', $badAmount['success'] === false);

        // Validation: reject invalid date
        $badDate = $expService->createExpense($this->testUserId, [
            'title' => 'Invalid Date Format',
            'amount' => '100',
            'transaction_date' => '15-10-2026', // non-ISO
            'category_id' => 1
        ]);
        $this->assert('Reject non-ISO transaction date', $badDate['success'] === false);

        // Update
        $updRes = $expService->updateExpense($this->testUserId, $expId, [
            'title' => 'Updated Supermarket Supplies',
            'amount' => '3400.00',
            'transaction_date' => '2026-10-15',
            'category_id' => 1,
            'payment_method' => 'UPI',
            'merchant' => 'HyperMarket Plus',
            'description' => 'Updated note'
        ]);
        $this->assert('Update expense record', $updRes['success'] === true);

        // Delete
        $delRes = $expService->deleteExpense($this->testUserId, $expId);
        $this->assert('Delete expense record', $delRes === true);
        $this->assert('Expense no longer exists', $expService->getExpenseById($this->testUserId, $expId) === null);
    }

    private function runIncomeCRUDTests(): void {
        echo "\n--- 4. Income CRUD & Net Cash Flow Tests ---\n";
        $incService = new IncomeService($this->db);

        $incRes = $incService->createIncome($this->testUserId, [
            'title' => 'Freelance App Development',
            'amount' => '45000.00',
            'received_date' => '2026-10-01',
            'category_id' => 2,
            'payment_method' => 'Bank Transfer',
            'payer' => 'Client Global',
            'description' => 'Milestone 1 payout'
        ]);
        $this->assert('Create valid income record', $incRes['success'] === true);
        $incId = $incRes['id'];

        $fetched = $incService->getIncomeById($this->testUserId, $incId);
        $this->assert('Fetch income by ID', $fetched !== null && (float)$fetched['amount'] === 45000.00);

        // Delete
        $delInc = $incService->deleteIncome($this->testUserId, $incId);
        $this->assert('Delete income record', $delInc === true);
    }

    private function runBudgetCalculationTests(): void {
        echo "\n--- 5. Budget Utilization & Warning Calculation Tests ---\n";
        $bgtService = new BudgetService($this->db);
        $expService = new ExpenseService($this->db);

        // Create budget of 10,000 with 80% warning threshold
        $bgtRes = $bgtService->createBudget($this->testUserId, [
            'budget_name' => 'Monthly Food Budget Test',
            'category_id' => 1,
            'budget_amount' => '10000.00',
            'period_type' => 'monthly',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'warning_threshold' => 80
        ]);
        $this->assert('Create category budget', $bgtRes['success'] === true);
        $budgetId = $bgtRes['id'];

        // Add 8,500 spending in category 1 (85% utilization)
        $expService->createExpense($this->testUserId, [
            'title' => 'Bulk Groceries',
            'amount' => '8500.00',
            'transaction_date' => '2026-10-10',
            'category_id' => 1
        ]);

        $activeBudgets = $bgtService->getActiveBudgetsWithUtilization($this->testUserId, '2026-10-15');
        $testBudget = null;
        foreach ($activeBudgets as $b) {
            if ($b['id'] == $budgetId) {
                $testBudget = $b;
                break;
            }
        }

        $this->assert('Budget found in active period', $testBudget !== null);
        if ($testBudget) {
            $this->assert('Budget spent calculation (8500.00)', (float)$testBudget['actual_spent'] === 8500.00);
            $this->assert('Budget remaining calculation (1500.00)', (float)$testBudget['remaining_amount'] === 1500.00);
            $this->assert('Budget utilization percentage (85%)', (float)$testBudget['utilization_pct'] === 85.0);
            $this->assert('Budget status level triggers warning (>= 80%)', $testBudget['status_level'] === 'warning');
        }

        // Clean up budget
        $bgtService->deleteBudget($this->testUserId, $budgetId);
    }

    private function runRecurringNormalizationTests(): void {
        echo "\n--- 6. Recurring Normalization & Auto-Logging Tests ---\n";
        $recService = new RecurringExpenseService($this->db);

        // Create weekly subscription: ₹500/week
        $recWeekly = $recService->createRecurring($this->testUserId, [
            'title' => 'Weekly Yoga Classes',
            'amount' => '500.00',
            'category_id' => 10,
            'frequency' => 'weekly',
            'start_date' => '2026-10-01',
            'next_due_date' => '2026-10-08',
            'payment_method' => 'UPI'
        ]);
        $this->assert('Create weekly recurring expense', $recWeekly['success'] === true);

        // Create quarterly subscription: ₹1,500/quarter
        $recQuarterly = $recService->createRecurring($this->testUserId, [
            'title' => 'Quarterly Magazine Digest',
            'amount' => '1500.00',
            'category_id' => 5,
            'frequency' => 'quarterly',
            'start_date' => '2026-10-01',
            'next_due_date' => '2027-01-01',
            'payment_method' => 'Credit Card'
        ]);
        $this->assert('Create quarterly recurring expense', $recQuarterly['success'] === true);

        $allRec = $recService->getAllRecurring($this->testUserId);
        $this->assert('Active subscriptions count >= 2', $allRec['active_count'] >= 2);

        // Check quarterly normalization: 1500 / 3 = 500/mo
        $qFound = null;
        foreach ($allRec['items'] as $item) {
            if ($item['id'] == $recQuarterly['id']) {
                $qFound = $item;
                break;
            }
        }
        $this->assert('Quarterly normalized monthly cost is 500.00', $qFound !== null && (float)$qFound['monthly_normalized'] === 500.00);

        // Test 1-Click "Log Payment As Expense"
        $logRes = $recService->logPaymentAsExpense($this->testUserId, $recWeekly['id'], '2026-10-08');
        $this->assert('1-Click log recurring payment as expense', $logRes['success'] === true);
        $this->assert('Advances weekly next due date by 7 days to 2026-10-15', $logRes['new_due_date'] === '2026-10-15');
    }

    private function runGoalProgressTests(): void {
        echo "\n--- 7. Financial Goal & Contribution Tests ---\n";
        $goalService = new GoalService($this->db);

        $gRes = $goalService->createGoal($this->testUserId, [
            'goal_name' => 'Emergency Fund Test',
            'goal_type' => 'savings',
            'target_amount' => '50000.00',
            'current_progress' => '10000.00',
            'start_date' => '2026-10-01',
            'target_date' => '2027-03-31',
            'description' => 'Test safety net'
        ]);
        $this->assert('Create savings goal', $gRes['success'] === true);
        $goalId = $gRes['id'];

        // Add contribution of 15,000 -> total 25,000 (50%)
        $contribRes = $goalService->addContribution($this->testUserId, $goalId, 15000.00, '2026-10-12', 'Deposit from bonus');
        $this->assert('Record goal contribution', $contribRes['success'] === true);
        $this->assert('New progress is 25000.00', (float)$contribRes['new_progress'] === 25000.00);

        $goals = $goalService->getGoals($this->testUserId);
        $curGoal = null;
        foreach ($goals as $g) {
            if ($g['id'] == $goalId) {
                $curGoal = $g;
                break;
            }
        }
        $this->assert('Goal progress percentage calculation is 50%', $curGoal !== null && (float)$curGoal['progress_pct'] === 50.0);

        $goalService->deleteGoal($this->testUserId, $goalId);
    }

    private function runAnalyticsAndMathTests(): void {
        echo "\n--- 8. Mathematical & Analytics Engine Tests ---\n";
        $analytics = new AnalyticsService($this->db);
        $expService = new ExpenseService($this->db);
        $incService = new IncomeService($this->db);

        // Log sample set of known expenses in October 2026
        // Transactions: ₹100, ₹200, ₹300, ₹400, ₹5000 (outlier)
        // Count: 5. Sum: 6000. Median: 300. Mean: 1200. Max: 5000. Min: 100.
        $amounts = [100.00, 200.00, 300.00, 400.00, 5000.00];
        foreach ($amounts as $amt) {
            $expService->createExpense($this->testUserId, [
                'title' => 'Math Test Tx ' . $amt,
                'amount' => (string)$amt,
                'transaction_date' => '2026-10-20',
                'category_id' => 1
            ]);
        }

        // Add 10,000 income
        $incService->createIncome($this->testUserId, [
            'title' => 'Math Test Income',
            'amount' => '10000.00',
            'received_date' => '2026-10-01',
            'category_id' => 1
        ]);

        $summary = $analytics->getSummaryMetrics($this->testUserId, '2026-10-01', '2026-10-31', 31);

        $this->assert('Total expenses sum is 15000.00 (8500 budget + 500 recurring + 6000 math)', (float)$summary['total_expenses'] === 15000.00);
        $this->assert('Net Cash Flow calculation (10000 - 15000 = -5000)', (float)$summary['net_cash_flow'] === -5000.00);
        $this->assert('Max transaction calculation is 8500.00', (float)$summary['max_tx_amount'] === 8500.00);
        $this->assert('Min transaction calculation is 100.00', (float)$summary['min_tx_amount'] === 100.00);
        $this->assert('Daily average spend calculation > 0 without division by zero', (float)$summary['avg_daily_spend'] > 0);
    }

    private function runBehaviourInsightTests(): void {
        echo "\n--- 9. Behavioural Insights Rules Engine Tests ---\n";
        $analytics = new AnalyticsService($this->db);
        $insightEngine = new BehaviourInsightService($analytics);

        $insights = $insightEngine->generateInsights($this->testUserId, '2026-10-01', '2026-10-31', 31);
        $this->assert('Generated at least 2 behavioural insights', count($insights) >= 2);

        $hasEvidence = true;
        foreach ($insights as $in) {
            if (empty($in['calculation']) || empty($in['title'])) {
                $hasEvidence = false;
                break;
            }
        }
        $this->assert('All insights contain transparent mathematical calculation evidence', $hasEvidence);
    }

    private function runAnomalyDetectionTests(): void {
        echo "\n--- 10. Spending Anomaly Detection Engine Tests ---\n";
        $anomalyService = new AnomalyDetectionService($this->db);

        $detectedCount = $anomalyService->scanAndDetectAnomalies($this->testUserId, '2026-10-01', '2026-10-31');
        $this->assert('Detected mathematical anomaly events', $detectedCount > 0);

        $anomalies = $anomalyService->getAnomalies($this->testUserId);
        $this->assert('Retrieved anomaly records for user', count($anomalies) > 0);

        if (!empty($anomalies)) {
            $first = $anomalies[0];
            $upd = $anomalyService->updateAnomalyStatus($this->testUserId, (int)$first['id'], 'reviewed');
            $this->assert('Update anomaly review status to reviewed', $upd === true);
        }
    }

    private function runCsvImportAndSanitizationTests(): void {
        echo "\n--- 11. CSV Import & Formula Injection Sanitization Tests ---\n";
        // Test CSV formula injection sanitization
        $formulaAttack1 = "=cmd|' /C calc'!A0";
        $sanitized1 = sanitizeCsvCell($formulaAttack1);
        $this->assert('Prefix malicious leading "=" with single quote', str_starts_with($sanitized1, "'="));

        $formulaAttack2 = "+12345+SUM(A1:A10)";
        $sanitized2 = sanitizeCsvCell($formulaAttack2);
        $this->assert('Prefix malicious leading "+" with single quote', str_starts_with($sanitized2, "'+"));

        $formulaAttack3 = "-@dangerous_func()";
        $sanitized3 = sanitizeCsvCell($formulaAttack3);
        $this->assert('Prefix malicious leading "-" with single quote', str_starts_with($sanitized3, "'-"));

        $normalText = "Organic Fresh Milk";
        $this->assert('Leave benign text untouched', sanitizeCsvCell($normalText) === $normalText);

        // Test CSV Import Service with mock file
        $tempCsv = STORAGE_PATH . '/test_import_' . time() . '.csv';
        $csvContent = "Title,Amount,Date,Category,Payment Method,Merchant,Notes\n" .
                      "Imported Espresso,180.00,2026-10-22,Restaurants & Cafes,UPI,Bistro,Morning coffee\n" .
                      "Imported Train Ticket,240.00,2026-10-23,Transportation,UPI,IRCTC,Travel pass\n" .
                      "Bad Row Negative,-500.00,2026-10-24,Shopping,Cash,Bad,Should fail\n";
        file_put_contents($tempCsv, $csvContent);

        $importService = new ImportService($this->db);
        $preview = $importService->previewCsv($tempCsv);
        $this->assert('Preview CSV headers and rows successfully', $preview['success'] === true && count($preview['preview_rows']) >= 3);

        $importReport = $importService->importExpenses($this->testUserId, $tempCsv);
        $this->assert('Import 2 valid rows', ($importReport['imported_count'] ?? 0) === 2);
        $this->assert('Catch and log 1 invalid row error', ($importReport['failed_count'] ?? 0) === 1);

        @unlink($tempCsv);
    }

    private function runSecurityAndAuthorizationTests(): void {
        echo "\n--- 12. Security & User Isolation Tests ---\n";
        // User 2: Aarav (ID 1 from seed data)
        $expService = new ExpenseService($this->db);
        $budgetService = new BudgetService($this->db);

        // Attempting to read another user's expense (e.g. ID 1 belongs to user 1, our test user is $this->testUserId)
        $unauthorizedExpense = $expService->getExpenseById($this->testUserId, 1);
        $this->assert('Strict isolation: User cannot read another user expense', $unauthorizedExpense === null);

        // Attempting to delete another user's expense
        $unauthorizedDelete = $expService->deleteExpense($this->testUserId, 1);
        $this->assert('Strict isolation: User cannot delete another user expense', $unauthorizedDelete === false);

        // Attempting to modify another user's budget
        $unauthorizedBudget = $budgetService->updateBudget($this->testUserId, 1, [
            'budget_name' => 'Hacked Budget Name',
            'budget_amount' => '999999',
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31'
        ]);
        $this->assert('Strict isolation: User cannot modify another user budget', $unauthorizedBudget['success'] === false);

        // CSRF Token Validation
        $token = generateCsrfToken();
        $this->assert('Generate cryptographically secure CSRF token', !empty($token) && strlen($token) === 64);
        $this->assert('Verify valid CSRF token', verifyCsrfToken($token) === true);
        $this->assert('Reject forged/tampered CSRF token', verifyCsrfToken('forged_fake_token_value') === false);
        $this->assert('Reject null CSRF token', verifyCsrfToken(null) === false);
    }
}

// Execute Test Suite
$suite = new EBAPTestSuite();
$suite->runAll();
