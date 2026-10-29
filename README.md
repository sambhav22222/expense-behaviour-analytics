# Expense Behaviour Analytical Platform (EBAP)

A comprehensive, full-stack personal finance and behavioural analytics web application built with **Core PHP 8.2+**, **MySQL**, **HTML5**, **CSS3**, **Vanilla JavaScript**, **Bootstrap 5**, and **Chart.js**.

Designed for BCA college examination submissions, live project defense demonstrations, and portfolio presentations.

---

## Table of Contents

- [1. Project Overview](#1-project-overview)
- [2. Problem Statement](#2-problem-statement)
- [3. Primary Objectives](#3-primary-objectives)
- [4. Core Features](#4-core-features)
- [5. Technology Stack](#5-technology-stack)
- [6. Project Structure](#6-project-structure)
- [7. Installation & XAMPP Setup Guide](#7-installation--xampp-setup-guide)
- [8. Sample Login Credentials](#8-sample-login-credentials)
- [9. Automated Testing & QA](#9-automated-testing--qa)
- [10. Mathematical & Analytical Logic](#10-mathematical--analytical-logic)
- [11. Security & Privacy Architecture](#11-security--privacy-architecture)
- [12. Publishing to GitHub](#12-publishing-to-github)
- [13. Known Limitations & Future Enhancements](#13-known-limitations--future-enhancements)
- [14. License](#14-license)

---

## 1. Project Overview

The **Expense Behaviour Analytical Platform (EBAP)** is a full-stack financial analysis and educational system. Rather than merely tabulating income and expenses, EBAP transforms transaction records into understandable analytics, explainable habits, and actionable suggestions.

> [!NOTE]
> EBAP is an educational and personal analytics tool. It strictly maintains user privacy and does not claim to provide regulated financial advice, guaranteed savings, or automated banking integration.

---

## 2. Problem Statement

Many people know how much money they spend on paper, but fail to understand:
- Where their money actually goes each month.
- Which categories dominate their expenditures.
- How their habits accelerate during weekends compared to weekdays.
- How much of their spending is consumed by fixed subscriptions.
- What proportion of spending is essential for living vs. discretionary lifestyle choices.

EBAP bridges this gap by applying deterministic mathematical models to user transactions to surface explainable behavioural patterns.

---

## 3. Primary Objectives

- **Expense & Income Tracking**: Complete CRUD operations for daily expenses and income streams.
- **Spending Categorization**: System-wide standard categories plus custom user categories.
- **Budget Monitoring**: Monthly and category-specific budget limits with real-time utilization progress and warning alerts (70%, 85%, 100%).
- **Behavioural Analytics**: Deep analysis of spending quality (essential vs. discretionary), timing habits (weekday vs. weekend), and mean vs. median skew.
- **Spending Anomaly Detection**: Transparent, rule-based detection flagging transactions exceeding historical category medians.
- **Recurring Commitments**: Standardizing weekly, quarterly, and annual subscriptions into monthly cost equivalents.
- **Financial Goal Tracking**: Tracking progress for savings targets and spending reduction goals with contribution logs.
- **Reports & Exporting**: Downloadable and printable reports with CSV formula-injection sanitization.

---

## 4. Core Features

| Module | Description |
| :--- | :--- |
| **Executive Dashboard** | 8 high-level financial summary cards, dynamic date range filter, and 4 interactive Chart.js visualizations. |
| **Expense Management** | Paginated table with keyword search, multi-field filtering, sorting, and decimal-safe editing. |
| **Income Management** | Tracks salary, freelancing, and dividends. Computes Net Cash Flow ($$\text{Income} - \text{Expenses}$$). |
| **Behaviour Analytics** | Wallet share percentage, spending concentration index, essential vs. discretionary breakdown, and weekend spending bias. |
| **Spending Anomaly Engine** | Rule-based outlier engine flagging charges $> 2.5\times$ category median or rapid duplicates within 48 hours. |
| **Budget Manager** | Visual progress bars with dynamic status badges (Green, Yellow, Red) and variance tracking. |
| **Subscriptions & Recurring** | Normalizes all billing cycles and provides 1-click expense logging for paid bills. |
| **Financial Goals** | Target tracking with progress percentages, target dates, and contribution history. |
| **Data Import (CSV)** | Upload CSV statements with preview validation and row-level error reporting. |
| **Reports & Exports** | 7 pre-built report templates optimized for print/PDF (`@media print`) and formula-sanitized CSV exports. |
| **Settings & Categories** | Multi-currency selector (₹ INR default, $ USD, € EUR, £ GBP, ¥ JPY) and custom category manager. |

---

## 5. Technology Stack

- **Frontend**: HTML5, CSS3, Vanilla JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons, Chart.js 4.4.
- **Backend**: Core PHP 8.2+ (Compatible with PHP 8.3), PHP PDO, PHP Sessions, Native Password Hashing (bcrypt).
- **Database**: MySQL 8.0+ / MariaDB 10.4+ (InnoDB Engine, utf8mb4 encoding, foreign keys, and indexes).
- **Environment**: Visual Studio Code, XAMPP (Apache & MySQL), Git.

*Strict Architectural Rule: Zero heavy frameworks (no Laravel, Node.js, React, or build tools) ensuring lightweight execution on standard local servers.*

---

## 6. Project Structure

```
expense-behaviour-analytics/
├── .env.example              # Placeholder environment configuration template
├── .gitignore                # Excludes private logs, uploads, and secrets
├── LICENSE                   # MIT License
├── CONTRIBUTING.md           # Guidelines for contributing
├── README.md                 # Primary project documentation
├── index.php                 # Public landing page with feature showcase
├── login.php                 # Authentication login with quick demo buttons
├── register.php              # User registration with currency preference
├── logout.php                # Session termination handler
│
├── config/
│   ├── app.php               # Application constants, paths, and currencies
│   └── database.php          # Singleton PDO connection factory
│
├── includes/
│   ├── header.php            # Global HTML5 header partial
│   ├── navbar.php            # Top navigation bar
│   ├── sidebar.php           # Responsive collapsible sidebar navigation
│   ├── footer.php            # Footer partial with academic disclaimer
│   ├── auth.php              # Session hardening & auth middleware
│   ├── csrf.php              # Cryptographic CSRF token generation & validation
│   ├── alerts.php            # Flash message renderer
│   └── functions.php         # Date helpers, CSV sanitization, and URL utilities
│
├── services/
│   ├── AuthService.php               # Registration, authentication & profile logic
│   ├── ExpenseService.php            # Expense CRUD, filtering & pagination
│   ├── IncomeService.php             # Income CRUD & filtering
│   ├── CategoryService.php           # System & custom categories
│   ├── BudgetService.php             # Budget utilization & warning calculations
│   ├── RecurringExpenseService.php   # Subscription cycle normalization & 1-click logging
│   ├── GoalService.php               # Financial goals & contribution tracking
│   ├── AnalyticsService.php          # Mathematical analysis, trends, & distributions
│   ├── BehaviourInsightService.php   # Explainable behavioural insight generation
│   ├── AnomalyDetectionService.php   # Rule-based spending outlier detection
│   ├── ReportService.php             # Pre-built printable reports & CSV streaming
│   └── ImportService.php             # CSV parsing, preview, & batch import
│
├── dashboard/
│   └── index.php             # Main executive analytics dashboard
│
├── expenses/
│   ├── index.php             # Filterable transaction list
│   ├── add.php               # Add expense form
│   ├── edit.php              # Edit expense form
│   └── delete.php            # Delete expense handler
│
├── income/
│   ├── index.php             # Income list workspace
│   ├── add.php               # Add income form
│   ├── edit.php              # Edit income form
│   └── delete.php            # Delete income handler
│
├── budgets/
│   ├── index.php             # Budget performance overview
│   ├── add.php               # Create budget form
│   ├── edit.php              # Edit budget form
│   └── delete.php            # Delete budget handler
│
├── recurring/
│   ├── index.php             # Subscriptions workspace
│   ├── add.php               # Add subscription form
│   ├── edit.php              # Edit subscription form
│   ├── delete.php            # Delete subscription handler
│   └── log_payment.php       # 1-Click payment logger
│
├── goals/
│   ├── index.php             # Financial goals workspace
│   ├── add.php               # Create goal form
│   ├── contribute.php        # Add fund contribution handler
│   └── delete.php            # Delete goal handler
│
├── analytics/
│   ├── index.php             # Central behavioural analytics workspace
│   ├── categories.php        # Category breakdown analytics
│   ├── trends.php            # Daily & monthly historical trends
│   └── anomalies.php         # Spending anomaly review workspace
│
├── insights/
│   └── index.php             # Dedicated habit observations workspace
│
├── reports/
│   ├── index.php             # Printable report generator
│   └── export.php            # CSV export endpoint
│
├── import/
│   ├── index.php             # CSV import & preview workspace
│   └── template.php          # Sample CSV template download endpoint
│
├── settings/
│   ├── index.php             # Profile, currency & password settings
│   └── categories.php        # Custom category manager
│
├── api/
│   └── analytics/
│       └── dashboard.php     # Async JSON metrics endpoint
│
├── assets/
│   ├── css/style.css         # Glassmorphism light theme & print styles
│   └── js/
│       ├── app.js            # Sidebar toggle, confirm dialogs, flash alerts
│       └── charts.js         # Chart.js helper library
│
├── database/
│   ├── schema.sql            # DDL schema for 13 normalized tables
│   └── seed.sql              # Realistic 3-month sample demonstration records
│
├── docs/
│   ├── architecture.md       # Technical architecture specification
│   ├── database-design.md    # Schema, ER diagram, & 3NF rationale
│   ├── screenshots.md        # UI/UX design and module walkthrough
│   ├── test-cases.md         # 57 QA test cases and execution results
│   └── viva-questions.md     # Comprehensive viva defense questions & answers
│
└── tests/
    └── run_tests.php         # Automated 57-assertion PHP test runner
```

---

## 7. Installation & XAMPP Setup Guide

### Prerequisites
- Windows 10/11
- [XAMPP for Windows](https://www.apachefriends.org/) (with PHP 8.2+ and MySQL)

### Step 1: Place the Project in XAMPP `htdocs`
Clone or copy the project folder to `C:\xampp\htdocs\expense-behaviour-analytics`:
```bash
cd C:\xampp\htdocs
git clone https://github.com/YOUR_USERNAME/expense-behaviour-analytics.git
```

### Step 2: Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 3: Import the Database
1. Open your web browser and navigate to `http://localhost/phpmyadmin`.
2. Click on the **Import** tab.
3. Import `database/schema.sql` first.
4. Import `database/seed.sql` second.

*Alternatively, using the Windows command prompt:*
```cmd
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\expense-behaviour-analytics\database\schema.sql
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\expense-behaviour-analytics\database\seed.sql
```

### Step 4: Open the Application
Navigate in your web browser to:
```
http://localhost/expense-behaviour-analytics
```

---

## 8. Sample Login Credentials

For demonstration and academic viva evaluation, the seed data includes two pre-configured accounts:

| Role | Email | Password | Data State |
| :--- | :--- | :--- | :--- |
| **Demo User** | `demo@ebap.local` | `Demo@123` | Pre-populated with 3 months of rich income, expenses, budgets, subscriptions, and anomaly flags. |
| **Student Evaluator** | `student@ebap.local` | `Student@123` | Clean / empty state to test first-use onboarding and fresh transaction logging. |

*(The login page features a 1-click button to automatically fill these credentials).*

---

## 9. Automated Testing & QA

EBAP includes a standalone automated test suite (`tests/run_tests.php`) testing 57 functional, mathematical, and security assertions.

To execute the tests from the command prompt:
```bash
php tests/run_tests.php
```

### Test Suite Results
```
======================================================================
EBAP AUTOMATED TEST SUITE EXECUTION
Environment: PHP 8.3.33 | MySQL 10.4.32-MariaDB
======================================================================

--- 1. Authentication & Session Security Tests ---
  [PASS] Authenticate with valid password
  [PASS] Reject invalid password
  [PASS] Change password successfully
  [PASS] Login with new changed password

--- 2. Category Hierarchy Tests ---
  [PASS] Load default system categories
  [PASS] Create custom expense category
  [PASS] Reject duplicate category name

--- 3. Expense CRUD & Validation Tests ---
  [PASS] Create valid expense
  [PASS] Read created expense by ID
  [PASS] Reject negative transaction amount
  [PASS] Reject non-ISO transaction date
  [PASS] Update expense record
  [PASS] Delete expense record
  [PASS] Expense no longer exists

--- 4. Income CRUD & Net Cash Flow Tests ---
  [PASS] Create valid income record
  [PASS] Fetch income by ID
  [PASS] Delete income record

--- 5. Budget Utilization & Warning Calculation Tests ---
  [PASS] Create category budget
  [PASS] Budget found in active period
  [PASS] Budget spent calculation (8500.00)
  [PASS] Budget remaining calculation (1500.00)
  [PASS] Budget utilization percentage (85%)
  [PASS] Budget status level triggers warning (>= 80%)

--- 6. Recurring Normalization & Auto-Logging Tests ---
  [PASS] Create weekly recurring expense
  [PASS] Create quarterly recurring expense
  [PASS] Active subscriptions count >= 2
  [PASS] Quarterly normalized monthly cost is 500.00
  [PASS] 1-Click log recurring payment as expense
  [PASS] Advances weekly next due date by 7 days to 2026-10-15

--- 7. Financial Goal & Contribution Tests ---
  [PASS] Create savings goal
  [PASS] Record goal contribution
  [PASS] New progress is 25000.00
  [PASS] Goal progress percentage calculation is 50%

--- 8. Mathematical & Analytics Engine Tests ---
  [PASS] Total expenses sum is 15000.00
  [PASS] Net Cash Flow calculation (10000 - 15000 = -5000)
  [PASS] Max transaction calculation is 8500.00
  [PASS] Min transaction calculation is 100.00
  [PASS] Daily average spend calculation > 0 without division by zero

--- 9. Behavioural Insights Rules Engine Tests ---
  [PASS] Generated at least 2 behavioural insights
  [PASS] All insights contain transparent mathematical calculation evidence

--- 10. Spending Anomaly Detection Engine Tests ---
  [PASS] Detected mathematical anomaly events
  [PASS] Retrieved anomaly records for user
  [PASS] Update anomaly review status to reviewed

--- 11. CSV Import & Formula Injection Sanitization Tests ---
  [PASS] Prefix malicious leading "=" with single quote
  [PASS] Prefix malicious leading "+" with single quote
  [PASS] Prefix malicious leading "-" with single quote
  [PASS] Leave benign text untouched
  [PASS] Preview CSV headers and rows successfully
  [PASS] Import 2 valid rows
  [PASS] Catch and log 1 invalid row error

--- 12. Security & User Isolation Tests ---
  [PASS] Strict isolation: User cannot read another user expense
  [PASS] Strict isolation: User cannot delete another user expense
  [PASS] Strict isolation: User cannot modify another user budget
  [PASS] Generate cryptographically secure CSRF token
  [PASS] Verify valid CSRF token
  [PASS] Reject forged/tampered CSRF token
  [PASS] Reject null CSRF token

======================================================================
TEST SUMMARY:
Total Tests Run: 57 | Passed: 57 | Failed: 0 (100% Pass Rate)
======================================================================
```

---

## 10. Mathematical & Analytical Logic

All calculations are encapsulated in reusable PHP classes under `services/`:

1. **Net Cash Flow**:
   $$\text{Net Cash Flow} = \text{Total Recorded Income} - \text{Total Recorded Expenses}$$

2. **Average Daily Spending**:
   $$\text{Daily Spending} = \frac{\text{Total Period Expenses}}{\text{Days in Period}}$$

3. **Budget Utilization**:
   $$\text{Budget Utilization \%} = \left(\frac{\text{Actual Spending}}{\text{Budget Limit}}\right) \times 100$$

4. **Spending Concentration**:
   Computes the percentage of total spending consumed by the top 3 highest categories to identify risk concentration.

5. **Cycle Normalization**:
   - Weekly amount $\times \frac{52}{12}$
   - Quarterly amount $\div 3$
   - Annual amount $\div 12$

---

## 11. Security & Privacy Architecture

- **No Sensitive Banking Credentials**: EBAP does not ask for or store bank account numbers, debit/credit card numbers, or banking credentials.
- **Password Protection**: Passwords are saved exclusively as bcrypt hashes generated via `password_hash($pwd, PASSWORD_DEFAULT)`.
- **Session Protection**: Hardened cookies with `HttpOnly` and `SameSite=Lax`. Session IDs are regenerated upon login.
- **SQL Injection Defense**: 100% of queries use PDO prepared statements with parameter binding.
- **CSRF Defense**: All mutating POST operations require a 64-character token validated with timing-attack resistant `hash_equals()`.
- **User Ownership Isolation**: Every query includes `user_id = :uid` to prevent IDOR attacks.
- **CSV Formula Injection Sanitization**: All cell exports are sanitized against malicious spreadsheet command execution.

---

## 12. Publishing to GitHub

To publish this project to your GitHub account:

```bash
# 1. Check git status
git status

# 2. Stage all project files (safe .gitignore prevents credentials or private uploads)
git add .

# 3. Commit with a clear descriptive message
git commit -m "Initial commit: Expense Behaviour Analytical Platform (EBAP)"

# 4. Set default branch to main
git branch -M main

# 5. Add your remote GitHub repository URL
git remote add origin https://github.com/YOUR_USERNAME/expense-behaviour-analytics.git

# 6. Push to GitHub
git push -u origin main
```

---

## 13. Known Limitations & Future Enhancements

### Known Limitations
- **Personal Tool, Not Regulated Advice**: Anomaly detection identifies mathematical outliers, not actual fraudulent transactions.
- **Manual Logging / CSV**: External bank syncing is not automated, as EBAP avoids storing banking API keys or user credentials.

### Future Enhancements
- Multi-currency conversion via an open exchange-rate API.
- Automated email digests for weekly spending summaries.
- Machine-learning classification of transaction descriptions into categories.

---

## 14. License

This project is licensed under the **MIT License**. See the [LICENSE](LICENSE) file for complete details.
