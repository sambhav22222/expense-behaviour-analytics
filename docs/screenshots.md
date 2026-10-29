# UI / UX Design & Interface Walkthrough

**Expense Behaviour Analytical Platform (EBAP)**

The platform utilizes a light theme with subtle glassmorphic panels, soft shadows, rounded border radii (14px), and clean data hierarchy.

---

## 1. Public Landing Page (`index.php`)
- **Hero Section**: Explains expense behaviour analysis vs basic tracking. Features "Create Free Account" and "Live Demo Sign In".
- **Interactive Live Sample Preview**: Embedded doughnut visualization demonstrating the October sample dataset.
- **Structured 8-Step Workflow**: Detailed step-by-step roadmap from onboarding to ongoing mindfulness.
- **Privacy Assurance**: Outlines multi-user isolation, bcrypt password hashing, and zero-bank-credential requirements.
- **FAQ Accordion**: Answers viva evaluation questions, empty-state behavior, and analytical boundaries.

---

## 2. Authentication Pages (`login.php`, `register.php`)
- **Login Workspace**: Modern centered card with CSRF token protection, password verification, and session regeneration.
- **Quick Demo Credentials**: One-click autofill for `demo@ebap.local` (pre-seeded with 3 months of rich records) and `student@ebap.local` (empty/clean state).
- **Registration Form**: Full name, email address, password validation (min 8 chars), and preferred currency selector (INR ₹, USD $, EUR €, GBP £, JPY ¥).

---

## 3. Executive Dashboard (`dashboard/index.php`)
- **Date-Range Filter**: Switch dynamically between This Week, This Month (default), Previous Month, Last 3 Months, This Year, or Custom range.
- **8 Metric Cards**: Total Expenses, Total Income, Net Cash Flow, Remaining Monthly Budget, Average Daily Spending, Top Spending Category, Average Transaction, and Recurring Share %.
- **Daily Spending Trend (Line Chart)**: Smooth Bézier curves with gradient fill showing daily expenses.
- **Category Proportion (Doughnut Chart)**: Clear category colors and share percentages.
- **6-Month Cash Flow (Grouped Bar Chart)**: Compares income vs expenses across the past half year.
- **Active Budget Utilization Progress**: Real-time progress bars with dynamic status indicators (Green < 70%, Yellow 70-85%, Red > 85%).
- **Recent Transactions Table**: Badges, payment methods, merchant names, and one-click edit links.

---

## 4. Expense & Income Management (`expenses/`, `income/`)
- **Multi-Filter Workspace**: Search keywords in title/merchant/notes, filter by category, date range, payment method, or amount min/max.
- **Pagination & Sorting**: Paginated records with direct jump links and count indicators.
- **Add / Edit Forms**: Server-side validation, decimal-safe amounts, and recurring indicators.
- **CSV Export**: Filtered records exported to sanitized CSV files.

---

## 5. Behavioural Analytics Workspace (`analytics/index.php`, `insights/index.php`)
- **Habit Observation Cards**:
  - *Dominant Category Insight*: Identifies highest expenditure categories and share of wallet.
  - *Weekday vs. Weekend Clustering*: Detects if lifestyle and dining out causes weekend spending spikes.
  - *Essential vs. Discretionary Ratio*: Compares survival needs (housing, food, healthcare) against discretionary spending.
  - *Mean vs. Median Outlier Skew*: Highlights when high-ticket purchases pull up typical averages.
  - *Period Momentum*: Calculates percentage changes against previous equivalent periods.
- **Evidence Box**: Each insight card includes a mathematical derivation box showing exact formula and variables.

---

## 6. Anomaly Detection Engine (`analytics/anomalies.php`)
- **Transparent Decision Rules**: Flags transactions exceeding 2.5x the historical category median, single transactions exceeding 3x the overall average, or duplicate transactions within 48 hours.
- **Review Workflow**: Allows users to mark flagged events as *Verified*, *Mark Normal / Expected*, or *Dismissed*.

---

## 7. Reports & CSV Import (`reports/index.php`, `import/index.php`)
- **Pre-Built Reports**: Comprehensive Monthly, Cash Flow, Category Breakdown, Budget Performance, and Recurring Commitments.
- **Print / PDF Optimized**: Styled specifically with `@media print` removing navigation and buttons for academic submission.
- **CSV Import Tool**: Preview first 5 rows, validate headers, detect format errors, and report row-by-row logs.
