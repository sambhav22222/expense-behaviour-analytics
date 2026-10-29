# System Architecture Documentation

**Expense Behaviour Analytical Platform (EBAP)**

## 1. Architectural Overview

EBAP follows a **Three-Tier, Service-Oriented Core PHP Architecture** without relying on external frameworks. This maintains maximum performance, zero bloated build steps, simple deployment on standard Apache/MySQL (XAMPP), and high transparency for academic evaluation.

```
+-------------------------------------------------------------------+
|                        Presentation Tier                          |
|  - HTML5 / CSS3 / Vanilla JavaScript (ES6)                        |
|  - Bootstrap 5 Responsive Layouts & Glassmorphism Light UI        |
|  - Chart.js 4.4 Data Visualizations & Dashboards                  |
|  - Print-friendly Media Stylesheets (@media print)                |
+---------------------------------+---------------------------------+
                                  | HTTP / Form POST / JSON Fetch
                                  v
+-------------------------------------------------------------------+
|                        Application Tier                           |
|  - Routing & Page Controllers (expenses/, budgets/, etc.)         |
|  - Session Authentication & Rate Limiting (includes/auth.php)     |
|  - CSRF Defense & Security Middleware (includes/csrf.php)         |
|  - Service Layer (Domain Logic & Mathematical Engines):           |
|      * AnalyticsService.php (Mean, Median, Concentration, Trends) |
|      * BehaviourInsightService.php (Explainable Insights Rules)   |
|      * AnomalyDetectionService.php (Statistical Outliers)         |
|      * ExpenseService.php & IncomeService.php (CRUD & Validation) |
|      * BudgetService.php (Utilization & Threshold Tracking)       |
|      * RecurringExpenseService.php (Cycle Normalization)          |
|      * GoalService.php (Target Tracking & Contributions)          |
|      * ReportService.php & ImportService.php (CSV & Security)     |
+---------------------------------+---------------------------------+
                                  | PHP Data Objects (PDO)
                                  v
+-------------------------------------------------------------------+
|                          Data Tier                                |
|  - MySQL 8.0+ / MariaDB 10.4+ (InnoDB Engine)                     |
|  - 13 Normalized Tables with Foreign Key Constraints              |
|  - Strict Multi-User Data Isolation (user_id Scoping)             |
|  - DECIMAL(12, 2) Monetary Columns (No Floating Point Errors)     |
+-------------------------------------------------------------------+
```

---

## 2. Directory Structure & Responsibilities

| Directory | Purpose |
| :--- | :--- |
| `config/` | Application constants (`app.php`) and singleton PDO connection factory (`database.php`) |
| `includes/` | Reusable layout partials (`header.php`, `navbar.php`, `sidebar.php`, `footer.php`, `alerts.php`), authentication (`auth.php`), CSRF token generator (`csrf.php`), and helpers (`functions.php`) |
| `services/` | Decoupled domain business logic classes and calculation engines |
| `dashboard/` | Main analytical executive dashboard |
| `expenses/` | Complete CRUD workspace for expense outflow transactions |
| `income/` | Complete CRUD workspace for income inflow records |
| `analytics/` | Central behavioural analytical views (overview, categories, trends, anomalies) |
| `budgets/` | Monthly & category budget management with real-time utilization progress |
| `recurring/` | Subscriptions management with normalized monthly/annual costs & 1-click logging |
| `goals/` | Financial goals tracking with progress percentages and contributions |
| `insights/` | Dedicated behavioural habit cards with mathematical derivation evidence |
| `reports/` | Pre-built printable reports and CSV exports |
| `import/` | CSV batch import with preview and row-level error reporting |
| `settings/` | User preferences, currency selection, custom categories, and password change |
| `assets/` | Stylesheets (`style.css`), interactions (`app.js`), Chart.js modules (`charts.js`) |
| `database/` | Normalized DDL schema script (`schema.sql`) and sample dataset (`seed.sql`) |
| `tests/` | Automated test suite verifying 57 functional and security assertions (`run_tests.php`) |
| `docs/` | Technical documentation, database design, test cases, and viva guide |

---

## 3. Mathematical & Behavioural Calculation Engine

All calculations are executed in deterministic PHP service classes:

1. **Category Share of Wallet**:
   $$\text{Category Share \%} = \left(\frac{\text{Category Spending}}{\text{Total Period Spending}}\right) \times 100$$

2. **Median vs. Mean Outlier Skew**:
   - Arrays of transactions are sorted in ascending order.
   - Exact median is computed using the middle element or average of two middle elements.
   - Significant divergence ($\text{Mean} > 1.5 \times \text{Median}$) triggers an *Outlier Distribution Insight*.

3. **Budget Utilization**:
   $$\text{Budget Utilization \%} = \left(\frac{\text{Actual Spending}}{\text{Budget Limit}}\right) \times 100$$
   $$\text{Remaining Budget} = \text{Budget Limit} - \text{Actual Spending}$$

4. **Recurring Normalization**:
   - Weekly: $\text{Amount} \times \left(\frac{52}{12}\right)$
   - Monthly: $\text{Amount} \times 1$
   - Quarterly: $\frac{\text{Amount}}{3}$
   - Yearly: $\frac{\text{Amount}}{12}$

5. **Period-over-Period Change**:
   $$\text{Percentage Change \%} = \left(\frac{\text{Current Period} - \text{Previous Period}}{\text{Previous Period}}\right) \times 100$$
   *(Zero previous baseline is handled safely without division by zero errors)*.

---

## 4. Security Architecture

1. **SQL Injection Defense**: Every database interaction utilizes PDO prepared statements with bound parameters. Emulated prepares are disabled (`PDO::ATTR_EMULATE_PREPARES => false`).
2. **User Data Isolation**: Every query strictly enforces `user_id = :uid`. Even if an attacker tampers with URL IDs (IDOR attack), the query fails to find records belonging to other users.
3. **Session Hardening**: Sessions use `HttpOnly`, `SameSite=Lax`, strict timeout invalidation, and regenerate session IDs upon login (`session_regenerate_id(true)`).
4. **CSRF Protection**: All POST mutations require a cryptographically random 64-character token generated via `random_bytes(32)` validated with `hash_equals()`.
5. **CSV Formula Injection Sanitization**: All CSV cell exports strip or prefix leading `=, +, -, @` with `'` to prevent spreadsheet command execution.
6. **Login Rate Limiting**: Tracks failed authentication attempts by IP address and email; locks out for 15 minutes after 5 consecutive failures.
