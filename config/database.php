<?php
declare(strict_types=1);

/**
 * Database Configuration & PDO Connection Factory
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/app.php';

class Database {
    private static ?PDO $instance = null;

    /**
     * Parse simple .env file if it exists
     */
    private static function loadEnv(): array {
        $envFile = ROOT_PATH . '/.env';
        $env = [];
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$key, $val] = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val);
                    $val = trim($val, '"\'');
                    $env[$key] = $val;
                }
            }
        }
        return $env;
    }

    /**
     * Get singleton PDO connection
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $env = self::loadEnv();
            
            $host = $env['DB_HOST'] ?? '127.0.0.1';
            $port = $env['DB_PORT'] ?? '3306';
            $dbname = $env['DB_NAME'] ?? 'expense_behaviour_analytics';
            $user = $env['DB_USER'] ?? 'root';
            $pass = $env['DB_PASS'] ?? '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // Log error safely without exposing credentials
                error_log("Database connection error: " . $e->getMessage());
                die("
                    <div style='font-family: system-ui, sans-serif; max-width: 600px; margin: 50px auto; padding: 24px; border: 1px solid #f87171; border-radius: 8px; background-color: #fef2f2; color: #991b1b;'>
                        <h2 style='margin-top:0;'>Database Connection Error</h2>
                        <p>Could not connect to the MySQL database. Please verify that:</p>
                        <ul>
                            <li>XAMPP MySQL is started in the XAMPP Control Panel.</li>
                            <li>The database <code>expense_behaviour_analytics</code> exists.</li>
                            <li>You have imported <code>database/schema.sql</code> and <code>database/seed.sql</code>.</li>
                        </ul>
                        <p><small>System message: " . htmlspecialchars($e->getMessage()) . "</small></p>
                    </div>
                ");
            }
        }

        return self::$instance;
    }
}

/**
 * Procedural helper to get DB connection
 */
function getDBConnection(): PDO {
    return Database::getConnection();
}
