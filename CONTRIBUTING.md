# Contributing to Expense Behaviour Analytical Platform (EBAP)

Thank you for your interest in contributing to the **Expense Behaviour Analytical Platform (EBAP)**! This platform is designed as an educational, explainable personal finance analytics system built with Core PHP, MySQL, Vanilla JavaScript, and Bootstrap 5.

## Code of Conduct

1. Be respectful and constructive in code reviews and discussions.
2. Adhere to security standards: never commit credentials, `.env` files, or personal financial data.
3. Keep financial calculations deterministic and explainable. Never fabricate arbitrary metrics or artificial predictions.

## Development Workflow

1. Fork the repository.
2. Clone your fork locally to your XAMPP `htdocs` directory:
   ```bash
   git clone https://github.com/YOUR_USERNAME/expense-behaviour-analytics.git
   cd expense-behaviour-analytics
   ```
3. Set up your local MySQL database using `database/schema.sql` and `database/seed.sql`.
4. Copy `.env.example` to `.env` if local overrides are needed.
5. Create a new topic branch:
   ```bash
   git checkout -b feature/your-feature-name
   ```
6. Implement your changes following PSR-12 coding guidelines.
7. Run the automated test suite before committing:
   ```bash
   php tests/run_tests.php
   ```
8. Commit your changes with clear, descriptive commit messages:
   ```bash
   git commit -m "feat(analytics): add weekly spending velocity metric"
   ```
9. Push to your fork and submit a Pull Request.

## Coding Standards

- **Core PHP 8.2+**: Use strict types (`declare(strict_types=1);`).
- **Database Access**: Always use PHP PDO with prepared statements. Never concatenate variables into SQL strings.
- **Amounts**: Store and calculate all monetary amounts with two-decimal precision (`DECIMAL(12, 2)`).
- **Security**: Always verify user ownership (`user_id = :uid`) on every database operation. Validate CSRF tokens on all POST requests.
- **Frontend**: Vanilla JavaScript (ES6+), Bootstrap 5, and Chart.js. Avoid adding heavy frontend frameworks.
