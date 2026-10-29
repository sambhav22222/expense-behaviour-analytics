# Viva Voce & Project Defense Guide

**Expense Behaviour Analytical Platform (EBAP)**
*Prepared for BCA College Examination & Technical Evaluation*

---

## 1. Project Background & Problem Statement

### Q1: What is the main objective of the Expense Behaviour Analytical Platform (EBAP)?
**Answer:** Many people know *how much* money they spend, but fail to understand *where* their money goes, which categories consume the largest share, how their spending habits change over time, or which impulsive patterns prevent them from reaching financial goals. EBAP addresses this by transforming daily transaction records into understandable, explainable analytics and data-driven behavioural insights.

### Q2: How does EBAP differ from basic expense-tracking apps?
**Answer:** Basic expense trackers simply record transactions and display static sums. EBAP incorporates an analytical rules engine that computes:
1. **Spending Quality:** Essential survival needs vs. discretionary lifestyle choices.
2. **Timing Patterns:** Weekday vs. weekend spending clustering.
3. **Distribution Dispersion:** Mean vs. median skew to detect high-ticket outliers.
4. **Normalized Fixed Obligations:** Standardizing weekly, quarterly, and annual subscriptions into monthly loads.
5. **Spending Anomaly Detection:** Rule-based detection of statistical deviations from historical baselines.
6. **Actionable Insights:** Every insight provides the exact mathematical calculation evidence.

---

## 2. Technology Stack & Technical Rationale

### Q3: Why use Core PHP instead of a framework like Laravel or Node.js?
**Answer:**
1. **Academic Clarity:** Core PHP exposes raw architectural concepts—session management, PDO prepared statements, HTTP headers, authentication, and SQL relational joins—without abstraction layers hiding how the web works.
2. **Zero Build Overhead:** Runs directly on standard Apache/MySQL (XAMPP) without npm builds, Node daemon dependencies, or complex deployment steps.
3. **Performance & Light Footprint:** Minimal memory usage and near-instant page response times.

### Q4: Why use MySQL InnoDB and not MyISAM?
**Answer:** InnoDB supports:
- **ACID Transactions:** Ensures atomicity (e.g. creating an expense and updating next due date in recurring logging).
- **Foreign Key Constraints:** Enforces referential integrity (`ON DELETE CASCADE` for user cleanup).
- **Row-Level Locking:** Superior multi-user concurrency compared to MyISAM table-level locking.

### Q5: Why is `DECIMAL(12, 2)` used for currency instead of `FLOAT` or `DOUBLE`?
**Answer:** Binary floating-point types (`FLOAT`/`DOUBLE`) cannot precisely represent decimal base-10 fractions (e.g. $0.1 + 0.2 \ne 0.3$). This causes rounding errors in financial transactions. `DECIMAL(12, 2)` provides fixed-point, exact decimal storage, ensuring financial totals remain 100% accurate.

---

## 3. Database Architecture & Design

### Q6: How is the database normalized?
**Answer:** The database conforms to **Third Normal Form (3NF)**:
- **1NF:** All attributes are atomic; no repeating groups.
- **2NF:** All non-key attributes are fully functionally dependent on primary keys.
- **3NF:** No transitive dependencies. For example, expense categories are stored in a dedicated `expense_categories` table referenced via `category_id` rather than repeating category names and colors in the `expenses` table.

### Q7: How does EBAP support system-wide default categories alongside custom user categories?
**Answer:** The `expense_categories` table uses a nullable `user_id`:
- System default categories have `user_id = NULL`.
- Custom user categories have `user_id = <authenticated_user_id>`.
When fetching categories, the query retrieves:
```sql
WHERE (user_id IS NULL OR user_id = :uid) AND status = 'active'
```
This enables shared standard defaults while keeping custom user categories strictly private.

---

## 4. Security & Privacy Engineering

### Q8: How does EBAP prevent SQL Injection?
**Answer:** Every SQL query involving user inputs utilizes **PHP PDO prepared statements** with bound parameters (`execute([':uid' => $userId])`). Furthermore, native prepared statements are enforced by setting `PDO::ATTR_EMULATE_PREPARES => false`. User input is never concatenated directly into SQL query strings.

### Q9: How is Cross-Site Request Forgery (CSRF) mitigated?
**Answer:** All mutating POST requests require a hidden `csrf_token`. The token is a 64-character cryptographically secure pseudo-random hex string generated via `bin2hex(random_bytes(32))` stored in the user's session. The server validates incoming tokens using timing-attack resistant `hash_equals()`.

### Q10: How does the system prevent Insecure Direct Object References (IDOR)?
**Answer:** A user can never read, modify, or delete another user's records simply by altering a URL or request parameter (e.g., `expenses/edit.php?id=5`). Every query checks:
```sql
WHERE id = :id AND user_id = :uid
```
If the record belongs to another user, the query returns zero rows, preventing unauthorized cross-user access.

### Q11: What is CSV Formula Injection and how is it prevented?
**Answer:** When exporting financial data to CSV, an attacker could input a title starting with `=cmd|' /C calc'!A0` or `=SUM(...)`. When opened in Microsoft Excel or LibreOffice, the spreadsheet interprets the cell as an executable formula. EBAP sanitizes every cell via `sanitizeCsvCell()` by checking if the first character is in `[=, +, -, @, \t, \r]` and prefixing it with a single quote (`'`), neutralizing formula execution.

---

## 5. Mathematical Calculations & Analytics

### Q12: How is the spending anomaly detection engine implemented?
**Answer:** Rather than making fraudulent claims or black-box predictions, EBAP implements explainable, rule-based detection:
1. **Category Statistical Outliers:** Calculates the historical median for transactions in each category. Flags transactions exceeding $2.5 \times \text{category median}$.
2. **Single Large Outlier:** Flags single transactions exceeding $3 \times$ the user's overall average transaction amount.
3. **Rapid Duplicates:** Flags identical charges for the same merchant within 48 hours.
Each detected anomaly displays the exact baseline, observed amount, and explanation.

### Q13: How is Net Cash Flow different from Bank Balance?
**Answer:** Cash Flow represents the *movement of funds during a selected timeframe*:
$$\text{Net Cash Flow} = \text{Total Period Income} - \text{Total Period Expenses}$$
EBAP explicitly labels this as Cash Flow and avoids claiming to know the user's real bank balance, as no third-party banking integration is configured.
