# Test Cases & QA Verification Report

**Expense Behaviour Analytical Platform (EBAP)**

This document details the functional and security test cases executed against the platform. All 57 automated assertions have been run and verified via `tests/run_tests.php`.

---

## 1. Summary of Execution

- **Test Suite Runner**: `tests/run_tests.php`
- **Execution Date**: 2026-10-29
- **PHP Version**: PHP 8.3.33 (ZTS Visual C++ 2019 x64)
- **Database Engine**: MariaDB 10.4.32 / MySQL 8.0 Compatible (InnoDB Engine)
- **Total Assertions**: 57
- **Passed**: 57 (100%)
- **Failed**: 0 (0%)

---

## 2. Test Cases Breakdown

### Category 1: Authentication & Session Security
| ID | Test Case | Expected Outcome | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-01 | User Registration | Inserts user record with bcrypt password hash and default settings | New user ID created, password hashed with `PASSWORD_DEFAULT` | **PASS** |
| TC-02 | Login with Valid Password | Verifies password via `password_verify` and initiates session | Returns `success: true` and regenerates session ID | **PASS** |
| TC-03 | Login with Invalid Password | Rejects incorrect password and logs failed attempt | Returns `success: false` with error message | **PASS** |
| TC-04 | Password Change | Verifies old password and updates hash | Password updated successfully and verified on subsequent login | **PASS** |

### Category 2: Category Hierarchy & Ownership
| ID | Test Case | Expected Outcome | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-05 | System Default Categories | Retrieves categories where `user_id IS NULL` | All 12 default categories loaded | **PASS** |
| TC-06 | Custom Category Creation | Allows user to add custom category with icon and color | Inserted into `expense_categories` with active status | **PASS** |
| TC-07 | Duplicate Category Check | Prevents duplicate category name for the same user | Duplicate rejected with validation error | **PASS** |

### Category 3: Expense CRUD & Data Validation
| ID | Test Case | Expected Outcome | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-08 | Create Valid Expense | Inserts record with valid amount, date, and category | Inserted with proper decimal formatting | **PASS** |
| TC-09 | Reject Negative Amount | Rejects amounts $\le 0$ | Validation error: "Amount must be a positive number" | **PASS** |
| TC-10 | Reject Invalid Date Format | Rejects non-ISO dates | Validation error: "Transaction date must be in YYYY-MM-DD format" | **PASS** |
| TC-11 | Update Expense | Modifies record belonging to authenticated user | Record updated in database | **PASS** |
| TC-12 | Delete Expense | Removes record from database | Record deleted and verify query returns null | **PASS** |

### Category 4: Income Management & Cash Flow
| ID | Test Case | Expected Outcome | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-13 | Create Income Record | Inserts earnings record with positive decimal amount | Record created successfully | **PASS** |
| TC-14 | Fetch Income by ID | Retrieves income record with category join | Correct record returned | **PASS** |
| TC-15 | Delete Income Record | Removes record safely | Record deleted | **PASS** |

### Category 5: Budget Utilization & Warning Thresholds
| ID | Test Case | Expected Outcome | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-16 | Budget Spent Calculation | Sums actual transactions in budget date range | Calculated exact spending (₹8,500.00) | **PASS** |
| TC-17 | Budget Remaining Calculation | Limit minus actual spending | Calculated remaining balance (₹1,500.00) | **PASS** |
| TC-18 | Utilization Percentage | `(Spent / Limit) * 100` | Calculated 85.0% | **PASS** |
| TC-19 | Warning Status Trigger | Utilization $\ge$ threshold (80%) triggers warning level | Status set to `warning` | **PASS** |

### Category 6: Recurring Subscriptions & Normalization
| ID | Test Case | Expected Outcome | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-20 | Quarterly Normalization | ₹1,500 quarterly $\rightarrow$ ₹500/month | Monthly normalized cost is ₹500.00 | **PASS** |
| TC-21 | 1-Click Payment Logging | Logs completed expense and rolls forward next due date | Expense created, weekly next due date rolled forward by 7 days | **PASS** |

### Category 7: Financial Goals & Contributions
| ID | Test Case | Expected Outcome | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-22 | Record Goal Contribution | Logs contribution in `goal_contributions` and updates progress | Progress increased from ₹10,000 to ₹25,000 | **PASS** |
| TC-23 | Goal Progress Percentage | `(Current / Target) * 100` | Target ₹50,000, Progress ₹25,000 $\rightarrow$ exactly 50% | **PASS** |

### Category 8: Mathematical & Analytics Engine
| ID | Test Case | Expected Outcome | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-24 | Net Cash Flow Calculation | `Total Income - Total Expenses` | Income ₹10,000 - Expenses ₹15,000 = -₹5,000 | **PASS** |
| TC-25 | Max & Min Transaction | Identifies extreme spending outliers | Max: ₹8,500.00, Min: ₹100.00 | **PASS** |
| TC-26 | Daily Average Calculation | Total Expenses / Calendar Days | Computed without division by zero errors | **PASS** |

### Category 9: Behavioural Insights & Anomaly Detection
| ID | Test Case | Expected Outcome | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-27 | Insight Generation | Generates dynamic insights with mathematical evidence | 2+ insights created with explicit calculation strings | **PASS** |
| TC-28 | Anomaly Detection | Flags transactions exceeding category baseline | Outliers identified and recorded in `anomaly_reviews` | **PASS** |
| TC-29 | Update Review Status | Allows user to verify an anomaly | Status updated to `reviewed` | **PASS** |

### Category 10: CSV Sanitization & Security Isolation
| ID | Test Case | Expected Outcome | Actual Result | Status |
| :--- | :--- | :--- | :--- | :--- |
| TC-30 | Formula Injection Defense | Prefixes `=, +, -, @` with single quote (`'`) | Formula expressions neutralized | **PASS** |
| TC-31 | CSV Row Validation | Imports valid rows and logs error for negative row | 2 rows imported, 1 negative row flagged in error log | **PASS** |
| TC-32 | IDOR / User Isolation | User A cannot read or delete User B records | Query strictly scoped by `user_id = :uid`, returned null | **PASS** |
| TC-33 | CSRF Verification | Validates 64-char token; rejects null or forged token | Genuine token verified; forged token rejected | **PASS** |
