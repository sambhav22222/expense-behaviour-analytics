-- ============================================================================
-- Expense Behaviour Analytical Platform (EBAP)
-- Database Seed Data (Demonstration & College Submission)
-- All data is strictly fictional sample data for testing and demonstrations.
-- ============================================================================

USE `expense_behaviour_analytics`;

-- 1. Insert System-wide Default Expense Categories (user_id IS NULL)
INSERT INTO `expense_categories` (`id`, `user_id`, `category_name`, `category_icon`, `category_color`, `is_essential`, `status`) VALUES
(1, NULL, 'Food & Groceries', 'bi-cart-check', '#10b981', 1, 'active'),
(2, NULL, 'Housing & Utilities', 'bi-house-door', '#3b82f6', 1, 'active'),
(3, NULL, 'Transportation', 'bi-car-front', '#f59e0b', 1, 'active'),
(4, NULL, 'Healthcare & Medical', 'bi-heart-pulse', '#ef4444', 1, 'active'),
(5, NULL, 'Education & Books', 'bi-book', '#8b5cf6', 1, 'active'),
(6, NULL, 'Restaurants & Cafes', 'bi-cup-hot', '#f97316', 0, 'active'),
(7, NULL, 'Shopping & Apparel', 'bi-bag', '#ec4899', 0, 'active'),
(8, NULL, 'Entertainment & Leisure', 'bi-film', '#6366f1', 0, 'active'),
(9, NULL, 'Subscriptions & Digital', 'bi-play-btn', '#06b6d4', 0, 'active'),
(10, NULL, 'Personal Care & Fitness', 'bi-person', '#14b8a6', 0, 'active'),
(11, NULL, 'Travel & Vacations', 'bi-airplane', '#84cc16', 0, 'active'),
(12, NULL, 'Miscellaneous & Other', 'bi-three-dots', '#64748b', 0, 'active');

-- 2. Insert System-wide Default Income Categories (user_id IS NULL)
INSERT INTO `income_categories` (`id`, `user_id`, `category_name`, `category_icon`, `category_color`, `status`) VALUES
(1, NULL, 'Salary & Wages', 'bi-briefcase', '#10b981', 'active'),
(2, NULL, 'Freelance & Consulting', 'bi-laptop', '#3b82f6', 'active'),
(3, NULL, 'Business Income', 'bi-graph-up-arrow', '#8b5cf6', 'active'),
(4, NULL, 'Allowance & Stipend', 'bi-cash-coin', '#f59e0b', 'active'),
(5, NULL, 'Scholarship & Grants', 'bi-mortarboard', '#06b6d4', 'active'),
(6, NULL, 'Investments & Dividends', 'bi-piggy-bank', '#14b8a6', 'active'),
(7, NULL, 'Gifts & Awards', 'bi-gift', '#ec4899', 'active'),
(8, NULL, 'Other Income', 'bi-plus-circle', '#64748b', 'active');

-- 3. Insert Demo Users
-- User 1: demo@ebap.local / Demo@123
-- User 2: student@ebap.local / Student@123
INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `preferred_currency`, `timezone`, `account_status`) VALUES
(1, 'Aarav Sharma (Demo User)', 'demo@ebap.local', '$2y$10$cjeM7Tb12IpVCtwPgyeA3uSaGCQIA3BVmhziDiid5Tlku/bspzoEi', 'INR', 'Asia/Kolkata', 'active'),
(2, 'Priya Patel (Student Evaluator)', 'student@ebap.local', '$2y$10$wYTFLJ3yJIusmgUliAouUOaNza5S4vR3.6Kfgtdb02WphqM3mhBlS', 'INR', 'Asia/Kolkata', 'active');

-- 4. User Settings
INSERT INTO `user_settings` (`user_id`, `setting_key`, `setting_value`) VALUES
(1, 'date_format', 'd M Y'),
(1, 'currency_symbol', '₹'),
(1, 'dashboard_range', 'this_month'),
(2, 'date_format', 'd M Y'),
(2, 'currency_symbol', '₹'),
(2, 'dashboard_range', 'this_month');

-- 5. Income Records for User 1 (Demo)
INSERT INTO `income_records` (`user_id`, `category_id`, `title`, `amount`, `received_date`, `payment_method`, `payer`, `description`) VALUES
(1, 1, 'Monthly Software Engineer Salary', 65000.00, '2026-08-01', 'Bank Transfer', 'TechCorp Solutions', 'August 2026 monthly base salary'),
(1, 2, 'Website Development Freelance', 15000.00, '2026-08-15', 'UPI', 'Local Retail Client', 'E-commerce website redesign payment'),
(1, 1, 'Monthly Software Engineer Salary', 65000.00, '2026-09-01', 'Bank Transfer', 'TechCorp Solutions', 'September 2026 monthly base salary'),
(1, 6, 'Mutual Fund Quarterly Dividend', 3200.00, '2026-09-18', 'Bank Transfer', 'UTI Nifty 50 Fund', 'Dividend payout credited'),
(1, 1, 'Monthly Software Engineer Salary', 65000.00, '2026-10-01', 'Bank Transfer', 'TechCorp Solutions', 'October 2026 monthly base salary'),
(1, 2, 'API Integration Freelance', 12500.00, '2026-10-12', 'UPI', 'Fintech Startup', 'Payment gateway module consultation');

-- 6. Budgets for User 1 (Demo)
INSERT INTO `budgets` (`user_id`, `category_id`, `budget_name`, `budget_amount`, `period_type`, `start_date`, `end_date`, `warning_threshold`, `status`) VALUES
(1, NULL, 'Overall Monthly Spending Budget', 48000.00, 'monthly', '2026-10-01', '2026-10-31', 80, 'active'),
(1, 1, 'Food & Groceries Monthly Budget', 12000.00, 'monthly', '2026-10-01', '2026-10-31', 75, 'active'),
(1, 6, 'Restaurants & Dining Budget', 7000.00, 'monthly', '2026-10-01', '2026-10-31', 80, 'active'),
(1, 3, 'Local Commute & Transportation Budget', 4500.00, 'monthly', '2026-10-01', '2026-10-31', 80, 'active'),
(1, 8, 'Entertainment & Movies Budget', 3500.00, 'monthly', '2026-10-01', '2026-10-31', 70, 'active'),
(1, 7, 'Shopping & Clothing Budget', 6000.00, 'monthly', '2026-10-01', '2026-10-31', 85, 'active');

-- 7. Recurring Subscriptions for User 1 (Demo)
INSERT INTO `recurring_expenses` (`user_id`, `category_id`, `title`, `amount`, `frequency`, `start_date`, `next_due_date`, `payment_method`, `notes`) VALUES
(1, 9, 'Netflix Premium 4K Plan', 649.00, 'monthly', '2026-01-10', '2026-11-10', 'Credit Card', 'Family 4-screen streaming plan'),
(1, 9, 'Spotify Premium Individual', 119.00, 'monthly', '2026-02-15', '2026-11-15', 'UPI', 'Auto-debit mandate via UPI'),
(1, 2, 'High-Speed Fiber Internet (300 Mbps)', 999.00, 'monthly', '2026-01-05', '2026-11-05', 'Net Banking', 'Airtel Xstream Fiber broadband'),
(1, 10, 'Cult.fit Fitness & Gym Pass', 1800.00, 'monthly', '2026-03-01', '2026-11-01', 'Debit Card', 'Monthly gym and boxing classes access'),
(1, 9, 'Google One 2TB Cloud Storage', 2100.00, 'yearly', '2026-05-15', '2027-05-15', 'Credit Card', 'Annual cloud storage backup');

-- 8. Financial Goals for User 1 (Demo)
INSERT INTO `financial_goals` (`user_id`, `goal_name`, `goal_type`, `category_id`, `target_amount`, `baseline_amount`, `current_progress`, `start_date`, `target_date`, `status`, `description`) VALUES
(1, 'Emergency Fund (6 Months Expenses)', 150000.00, NULL, 150000.00, 0.00, 85000.00, '2026-01-01', '2026-12-31', 'in_progress', 'Building personal safety net in liquid mutual funds'),
(1, 'Reduce Restaurant Dining Expenses', 2, 6, 2500.00, 8500.00, 1800.00, '2026-10-01', '2026-10-31', 'in_progress', 'Aiming to cook more at home and cut dining costs by 30%'),
(1, 'Annual Diwali Tech Gadget Fund', 1, NULL, 40000.00, 0.00, 32000.00, '2026-06-01', '2026-11-10', 'in_progress', 'Targeting new monitor and mechanical keyboard');

-- 9. Goal Contributions
INSERT INTO `goal_contributions` (`goal_id`, `user_id`, `amount`, `contribution_date`, `description`) VALUES
(1, 1, 25000.00, '2026-08-05', 'August salary savings transfer'),
(1, 1, 30000.00, '2026-09-05', 'September salary transfer'),
(1, 1, 30000.00, '2026-10-05', 'October salary transfer'),
(3, 1, 10000.00, '2026-08-20', 'Freelance earnings contribution'),
(3, 1, 12000.00, '2026-09-22', 'Savings deposit'),
(3, 1, 10000.00, '2026-10-15', 'October project contribution');

-- 10. Expenses for User 1 (Historical & Current Month)
-- August 2026 Expenses
INSERT INTO `expenses` (`user_id`, `category_id`, `title`, `amount`, `transaction_date`, `payment_method`, `merchant`, `description`, `is_recurring`) VALUES
(1, 2, 'House Rent Payment', 18000.00, '2026-08-01', 'Net Banking', 'Landlord (R. K. Verma)', 'August month 2BHK apartment rent', 1),
(1, 1, 'Monthly Supermarket Grocery Stock', 5840.00, '2026-08-03', 'Credit Card', 'D-Mart Supercenter', 'Pantry staples, pulses, cooking oil and spices', 0),
(1, 2, 'Electricity Bill', 2450.00, '2026-08-05', 'UPI', 'State Electricity Board', 'Air conditioning summer bill', 1),
(1, 9, 'Netflix Monthly Subscription', 649.00, '2026-08-10', 'Credit Card', 'Netflix India', 'Premium streaming bill', 1),
(1, 6, 'Weekend Dinner with Team', 3200.00, '2026-08-14', 'Credit Card', 'Barbeque Nation', 'Team celebration buffet', 0),
(1, 3, 'Metro Smart Card Recharge', 1000.00, '2026-08-16', 'UPI', 'Metro Rail Corp', 'Monthly daily office commute', 0),
(1, 1, 'Vegetables and Dairy Store', 1250.00, '2026-08-18', 'UPI', 'Fresh Veggies Bazaar', 'Weekly green vegetables and organic milk', 0),
(1, 7, 'Office Formal Shirts & Trousers', 4200.00, '2026-08-22', 'Debit Card', 'Zara Clothing', 'Two formal shirts and trousers', 0),
(1, 8, 'Movie Tickets & Popcorn Combo', 980.00, '2026-08-26', 'UPI', 'PVR Cinemas', 'Sci-fi movie IMAX tickets for two', 0),
(1, 6, 'Coffee and Bistro Snacks', 450.00, '2026-08-29', 'Cash', 'Blue Tokai Roasters', 'Latte and sourdough croissant', 0);

-- September 2026 Expenses
INSERT INTO `expenses` (`user_id`, `category_id`, `title`, `amount`, `transaction_date`, `payment_method`, `merchant`, `description`, `is_recurring`) VALUES
(1, 2, 'House Rent Payment', 18000.00, '2026-09-01', 'Net Banking', 'Landlord (R. K. Verma)', 'September 2BHK apartment rent', 1),
(1, 1, 'Bulk Grocery Shopping', 6120.00, '2026-09-02', 'Credit Card', 'Nature Basket', 'Organic rice, wheat flour, ghee and essentials', 0),
(1, 2, 'Electricity Bill', 2180.00, '2026-09-06', 'UPI', 'State Electricity Board', 'September power bill', 1),
(1, 9, 'Netflix Monthly Subscription', 649.00, '2026-09-10', 'Credit Card', 'Netflix India', 'Subscription payment', 1),
(1, 3, 'Cab Rides for Meetings', 1450.00, '2026-09-12', 'UPI', 'Uber India', 'Airport client meeting cab ride', 0),
(1, 6, 'Family Birthday Dinner', 4650.00, '2026-09-15', 'Credit Card', 'Mainland China', 'Birthday celebration dinner banquet', 0),
(1, 4, 'Annual Health Checkup & Dental', 3200.00, '2026-09-19', 'Debit Card', 'Apollo Health Clinic', 'Preventative blood panel and dental clean', 0),
(1, 1, 'Weekly Groceries & Fruits', 1650.00, '2026-09-21', 'UPI', 'Blinkit Delivery', 'Exotic fruits, almond butter, oats', 0),
(1, 7, 'Running Shoes & Sports Gear', 4890.00, '2026-09-24', 'Credit Card', 'Decathlon Sports', 'Trail running shoes and gym gloves', 0),
(1, 8, 'Live Standup Comedy Show', 1600.00, '2026-09-27', 'UPI', 'BookMyShow', 'Weekend comedy show entry passes', 0),
(1, 3, 'Fuel Petrol Station', 2200.00, '2026-09-29', 'Credit Card', 'Indian Oil Fuel Pump', 'Car fuel tank fill up', 0);

-- October 2026 Expenses (Current Active Demonstration Period)
INSERT INTO `expenses` (`user_id`, `category_id`, `title`, `amount`, `transaction_date`, `payment_method`, `merchant`, `description`, `is_recurring`) VALUES
(1, 2, 'House Rent Payment', 18000.00, '2026-10-01', 'Net Banking', 'Landlord (R. K. Verma)', 'October apartment monthly rent', 1),
(1, 1, 'Monthly Grocery Superstore', 6450.00, '2026-10-02', 'Credit Card', 'BigBasket Wholesale', 'Whole grains, olive oil, dry fruits and cleaning goods', 0),
(1, 2, 'High-Speed Fiber Internet Bill', 999.00, '2026-10-05', 'Net Banking', 'Airtel Broadband', 'Monthly fiber internet plan', 1),
(1, 6, 'Weekend Brunch with Friends', 1850.00, '2026-10-04', 'UPI', 'The Daily Cafe', 'Sunday morning breakfast and artisan coffee', 0),
(1, 2, 'Electricity Power Bill', 1980.00, '2026-10-06', 'UPI', 'State Electricity Board', 'October domestic electricity bill', 1),
(1, 3, 'Fuel Petrol for Vehicle', 2100.00, '2026-10-08', 'Credit Card', 'Bharat Petroleum', 'Full tank fuel', 0),
(1, 9, 'Netflix Monthly Subscription', 649.00, '2026-10-10', 'Credit Card', 'Netflix India', 'Premium streaming plan auto-debit', 1),
(1, 10, 'Cult.fit Gym Monthly Fee', 1800.00, '2026-10-11', 'Debit Card', 'Cult.fit Centers', 'Gym membership auto-renew', 1),
(1, 1, 'Organic Dairy, Milk & Eggs', 980.00, '2026-10-13', 'UPI', 'Country Delight', 'A2 fresh cow milk and farm eggs subscription', 0),
(1, 6, 'Fine Dining Anniversary Dinner', 5200.00, '2026-10-17', 'Credit Card', 'Olive Bar & Kitchen', 'Special 4-course celebration dinner with wine (Anomaly Spike)', 0),
(1, 8, 'Concert Musical Festival Tickets', 2800.00, '2026-10-18', 'Debit Card', 'Paytm Insider', 'Music live performance stage entry', 0),
(1, 3, 'Metro Rail Office Commute', 950.00, '2026-10-20', 'UPI', 'Metro Transit Card', 'Card top up for office travel', 0),
(1, 1, 'Fresh Market Vegetables & Greens', 1120.00, '2026-10-23', 'Cash', 'Local Farmers Market', 'Weekly fresh greens and vegetables', 0),
(1, 7, 'Festival Apparel & Traditional Wear', 5400.00, '2026-10-25', 'Credit Card', 'FabIndia Ethnic Store', 'Kurta and festive gift apparel', 0),
(1, 6, 'Food Delivery Dinner Order', 850.00, '2026-10-27', 'UPI', 'Swiggy Food Delivery', 'Late night biryani and garlic naan order', 0),
(1, 5, 'Technical OReilly Programming Books', 1450.00, '2026-10-28', 'Credit Card', 'Amazon Books', 'System Design and Microservices architecture book', 0);

-- 11. Initial Explainable Anomaly Reviews for Demo User
INSERT INTO `anomaly_reviews` (`user_id`, `expense_id`, `category_id`, `detection_method`, `baseline_value`, `observed_value`, `explanation`, `review_status`, `created_at`) VALUES
(1, 31, 6, 'Category Statistical Outlier (Z-Score > 2.5x Median)', 1850.00, 5200.00, 'This transaction at Olive Bar & Kitchen (₹5,200.00) is 2.81x higher than your historical median dining transaction of ₹1,850.00 in Restaurants & Cafes.', 'pending', '2026-10-17 22:30:00'),
(1, 35, 7, 'Sudden Category Spending Surge', 1400.00, 5400.00, 'Shopping & Apparel expenditure of ₹5,400.00 on 2026-10-25 significantly exceeded your typical single transaction baseline of ₹1,400.00.', 'reviewed', '2026-10-25 19:15:00');
