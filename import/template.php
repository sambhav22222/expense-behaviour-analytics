<?php
declare(strict_types=1);

/**
 * Download Sample CSV Template Endpoint
 * Expense Behaviour Analytical Platform (EBAP)
 */

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="ebap_expenses_sample_template.csv"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

// Headers
fputcsv($out, ['Title', 'Amount', 'Date', 'Category', 'Payment Method', 'Merchant', 'Notes']);

// Sample Rows
fputcsv($out, ['Supermarket Groceries', '2450.00', date('Y-m-d', strtotime('-5 days')), 'Food & Groceries', 'Credit Card', 'D-Mart Store', 'Weekly pantry restocking']);
fputcsv($out, ['Metro Card Recharge', '500.00', date('Y-m-d', strtotime('-4 days')), 'Transportation', 'UPI', 'Metro Transit', 'Commute top up']);
fputcsv($out, ['Weekend Restaurant Meal', '1200.00', date('Y-m-d', strtotime('-2 days')), 'Restaurants & Cafes', 'UPI', 'Urban Cafe', 'Dinner with college friends']);
fputcsv($out, ['Fiber Broadband Bill', '999.00', date('Y-m-d', strtotime('-1 days')), 'Housing & Utilities', 'Net Banking', 'Airtel Broadband', 'Monthly high-speed internet']);

fclose($out);
exit;
