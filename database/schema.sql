-- ============================================================================
-- Expense Behaviour Analytical Platform (EBAP)
-- Database Schema Definition
-- Engine: MySQL 8.0+ / MariaDB 10.4+ (InnoDB, utf8mb4)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `expense_behaviour_analytics` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `expense_behaviour_analytics`;

-- Disable foreign key checks during creation/re-run
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Table
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `user_settings`;
DROP TABLE IF EXISTS `anomaly_reviews`;
DROP TABLE IF EXISTS `goal_contributions`;
DROP TABLE IF EXISTS `financial_goals`;
DROP TABLE IF EXISTS `recurring_expenses`;
DROP TABLE IF EXISTS `budgets`;
DROP TABLE IF EXISTS `income_records`;
DROP TABLE IF EXISTS `income_categories`;
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `expense_categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `preferred_currency` VARCHAR(10) NOT NULL DEFAULT 'INR',
    `timezone` VARCHAR(50) NOT NULL DEFAULT 'Asia/Kolkata',
    `account_status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_status` (`account_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Expense Categories Table (System defaults have user_id = NULL)
CREATE TABLE `expense_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `category_name` VARCHAR(100) NOT NULL,
    `category_icon` VARCHAR(50) NOT NULL DEFAULT 'bi-tag',
    `category_color` VARCHAR(20) NOT NULL DEFAULT '#4f46e5',
    `is_essential` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_expcat_user_status` (`user_id`, `status`),
    INDEX `idx_expcat_essential` (`is_essential`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Income Categories Table
CREATE TABLE `income_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `category_name` VARCHAR(100) NOT NULL,
    `category_icon` VARCHAR(50) NOT NULL DEFAULT 'bi-wallet2',
    `category_color` VARCHAR(20) NOT NULL DEFAULT '#10b981',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_inccat_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Expenses Table
CREATE TABLE `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `transaction_date` DATE NOT NULL,
    `payment_method` ENUM('Cash', 'UPI', 'Credit Card', 'Debit Card', 'Net Banking', 'Cheque', 'Other') NOT NULL DEFAULT 'UPI',
    `merchant` VARCHAR(150) NULL,
    `description` TEXT NULL,
    `is_recurring` TINYINT(1) NOT NULL DEFAULT 0,
    `recurring_expense_id` INT NULL,
    `import_reference` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE RESTRICT,
    INDEX `idx_expenses_user_date` (`user_id`, `transaction_date`),
    INDEX `idx_expenses_user_cat` (`user_id`, `category_id`),
    INDEX `idx_expenses_payment` (`payment_method`),
    INDEX `idx_expenses_recurring` (`is_recurring`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Income Records Table
CREATE TABLE `income_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `received_date` DATE NOT NULL,
    `payment_method` ENUM('Bank Transfer', 'Cash', 'UPI', 'Cheque', 'Other') NOT NULL DEFAULT 'Bank Transfer',
    `payer` VARCHAR(150) NULL,
    `description` TEXT NULL,
    `is_recurring` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `income_categories`(`id`) ON DELETE RESTRICT,
    INDEX `idx_income_user_date` (`user_id`, `received_date`),
    INDEX `idx_income_user_cat` (`user_id`, `category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Budgets Table
CREATE TABLE `budgets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NULL COMMENT 'NULL for overall total budget',
    `budget_name` VARCHAR(150) NOT NULL,
    `budget_amount` DECIMAL(12, 2) NOT NULL,
    `period_type` ENUM('monthly', 'weekly', 'yearly', 'custom') NOT NULL DEFAULT 'monthly',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `warning_threshold` INT NOT NULL DEFAULT 80,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_budgets_user_status` (`user_id`, `status`),
    INDEX `idx_budgets_date_range` (`user_id`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Recurring Expenses Table
CREATE TABLE `recurring_expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `frequency` ENUM('weekly', 'monthly', 'quarterly', 'yearly') NOT NULL DEFAULT 'monthly',
    `start_date` DATE NOT NULL,
    `next_due_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'UPI',
    `auto_log` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active', 'paused', 'cancelled') NOT NULL DEFAULT 'active',
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE RESTRICT,
    INDEX `idx_recurring_user_status` (`user_id`, `status`),
    INDEX `idx_recurring_due_date` (`user_id`, `next_due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Financial Goals Table
CREATE TABLE `financial_goals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `goal_name` VARCHAR(150) NOT NULL,
    `goal_type` ENUM('savings', 'spending_reduction', 'category_cap') NOT NULL DEFAULT 'savings',
    `category_id` INT NULL,
    `target_amount` DECIMAL(12, 2) NOT NULL,
    `baseline_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `current_progress` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `start_date` DATE NOT NULL,
    `target_date` DATE NOT NULL,
    `status` ENUM('in_progress', 'achieved', 'abandoned') NOT NULL DEFAULT 'in_progress',
    `description` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_goals_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Goal Contributions Table
CREATE TABLE `goal_contributions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `goal_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `amount` DECIMAL(12, 2) NOT NULL,
    `contribution_date` DATE NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`goal_id`) REFERENCES `financial_goals`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_goalcontrib_goal` (`goal_id`),
    INDEX `idx_goalcontrib_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Anomaly Reviews Table
CREATE TABLE `anomaly_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `expense_id` INT NULL,
    `category_id` INT NULL,
    `detection_method` VARCHAR(100) NOT NULL,
    `baseline_value` DECIMAL(12, 2) NOT NULL,
    `observed_value` DECIMAL(12, 2) NOT NULL,
    `explanation` TEXT NOT NULL,
    `review_status` ENUM('pending', 'reviewed', 'dismissed', 'false_positive') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at` TIMESTAMP NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`expense_id`) REFERENCES `expenses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE SET NULL,
    INDEX `idx_anom_user_status` (`user_id`, `review_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. User Settings Table
CREATE TABLE `user_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NOT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uniq_user_setting` (`user_id`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Login Rate Limiting Table
CREATE TABLE `login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_login_ip_time` (`ip_address`, `attempted_at`),
    INDEX `idx_login_email_time` (`email`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Audit Logs Table
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_audit_user_time` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
