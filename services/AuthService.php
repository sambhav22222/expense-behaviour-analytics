<?php
declare(strict_types=1);

/**
 * Authentication Service
 * Expense Behaviour Analytical Platform (EBAP)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

class AuthService {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? getDBConnection();
    }

    /**
     * Register a new user
     */
    public function register(string $fullName, string $email, string $password, string $confirmPassword, string $currency = 'INR'): array {
        $errors = [];
        $fullName = trim($fullName);
        $email = trim(strtolower($email));
        $currency = strtoupper(trim($currency));

        if (empty($fullName) || strlen($fullName) < 2 || strlen($fullName) > 100) {
            $errors[] = 'Full name must be between 2 and 100 characters.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        $validCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD'];
        if (!in_array($currency, $validCurrencies, true)) {
            $currency = 'INR';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'errors' => ['An account with this email address already exists.']];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $this->db->beginTransaction();

            $insertStmt = $this->db->prepare("
                INSERT INTO users (full_name, email, password_hash, preferred_currency, timezone, account_status, created_at)
                VALUES (:name, :email, :hash, :curr, 'Asia/Kolkata', 'active', NOW())
            ");
            $insertStmt->execute([
                ':name' => $fullName,
                ':email' => $email,
                ':hash' => $passwordHash,
                ':curr' => $currency
            ]);

            $userId = (int)$this->db->lastInsertId();

            // Set default user preferences in user_settings
            $settingsStmt = $this->db->prepare("
                INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES 
                (:uid1, 'date_format', 'd M Y'),
                (:uid2, 'dashboard_range', 'this_month')
            ");
            $settingsStmt->execute([':uid1' => $userId, ':uid2' => $userId]);

            $this->db->commit();

            logAudit($userId, 'REGISTER', 'users', $userId, 'New user account created.');

            return [
                'success' => true,
                'user_id' => $userId,
                'user' => [
                    'id' => $userId,
                    'full_name' => $fullName,
                    'email' => $email,
                    'preferred_currency' => $currency,
                    'timezone' => 'Asia/Kolkata',
                    'account_status' => 'active'
                ]
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Registration failed: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Registration failed due to a server error. Please try again.']];
        }
    }

    /**
     * Authenticate user with password and rate limiting
     */
    public function authenticate(string $email, string $password, string $ip): array {
        $email = trim(strtolower($email));

        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Please provide both email and password.'];
        }

        if (isRateLimited($ip, $email)) {
            return [
                'success' => false,
                'error' => 'Too many failed login attempts. For security reasons, please wait 15 minutes before trying again.'
            ];
        }

        $stmt = $this->db->prepare("
            SELECT id, full_name, email, password_hash, preferred_currency, timezone, account_status
            FROM users 
            WHERE email = :email 
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            recordFailedLogin($ip, $email);
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        if ($user['account_status'] !== 'active') {
            return ['success' => false, 'error' => 'Your account is ' . $user['account_status'] . '. Please contact support.'];
        }

        clearLoginAttempts($ip, $email);
        loginUser($user);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Change user password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmNewPassword): array {
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'New password must be at least 8 characters long.'];
        }
        if ($newPassword !== $confirmNewPassword) {
            return ['success' => false, 'error' => 'New passwords do not match.'];
        }

        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            return ['success' => false, 'error' => 'Current password is incorrect.'];
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $this->db->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
        $updateStmt->execute([':hash' => $newHash, ':id' => $userId]);

        logAudit($userId, 'CHANGE_PASSWORD', 'users', $userId, 'User changed password.');

        return ['success' => true];
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, string $fullName, string $currency, string $timezone): array {
        $fullName = trim($fullName);
        if (empty($fullName) || strlen($fullName) < 2) {
            return ['success' => false, 'error' => 'Full name must be at least 2 characters.'];
        }

        $stmt = $this->db->prepare("
            UPDATE users 
            SET full_name = :name, preferred_currency = :curr, timezone = :tz
            WHERE id = :id
        ");
        $stmt->execute([
            ':name' => $fullName,
            ':curr' => $currency,
            ':tz' => $timezone,
            ':id' => $userId
        ]);

        if (isset($_SESSION['user'])) {
            $_SESSION['user']['full_name'] = $fullName;
            $_SESSION['user']['preferred_currency'] = $currency;
            $_SESSION['user']['timezone'] = $timezone;
        }

        logAudit($userId, 'UPDATE_PROFILE', 'users', $userId, 'User profile settings updated.');

        return ['success' => true];
    }

    /**
     * Deactivate user account after verifying current password
     */
    public function deactivateAccount(int $userId, string $password): array {
        $stmt = $this->db->prepare("SELECT password_hash, email FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            return ['success' => false, 'error' => 'Password verification failed. Account was not deactivated.'];
        }

        // Prevent deactivating the primary shared demo account during live evaluations
        if ($row['email'] === 'demo@ebap.local') {
            return ['success' => false, 'error' => 'The primary demonstration account cannot be deactivated during evaluation mode.'];
        }

        $updStmt = $this->db->prepare("UPDATE users SET account_status = 'inactive', updated_at = NOW() WHERE id = :id");
        $updStmt->execute([':id' => $userId]);

        logAudit($userId, 'DEACTIVATE_ACCOUNT', 'users', $userId, 'User deactivated their account.');

        return ['success' => true];
    }
}
