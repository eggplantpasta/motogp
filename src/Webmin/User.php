<?php

namespace Webmin;

use Database;
use Psr\Log\LoggerInterface;

class User {

    public $username = '';
    public $usernameErr = '';
    public $email = '';
    public $emailErr = '';
    public $password = '';
    public $passwordErr = '';
    public $loginErr = '';
    public $accountErr = '';

    private $db;
    private ?LoggerInterface $logger;

    public function __construct($db = null, ?LoggerInterface $logger = null) {
        $this->logger = $logger;
        if ($db) {
            $this->db = $db;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function validateUsername(?int $userId = null): bool
    {
        $this->usernameErr = '';

        if (empty(trim($this->username))) {
            $this->usernameErr = 'Username cannot be empty.';
        } elseif (strlen($this->username) < 3 || strlen($this->username) > 20) {
            $this->usernameErr = 'Username must be between 3 and 20 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $this->username)) {
            $this->usernameErr = 'Username can only contain letters, numbers, and underscores.';
        }

        if (empty($this->usernameErr) && $this->db) {
            $sql = "SELECT user_id
                    FROM users
                    WHERE username = :username";

            $params = ['username' => $this->username];

            if ($userId !== null) {
                $sql .= " AND user_id != :user_id";
                $params['user_id'] = $userId;
            }

            $results = $this->db->query($sql, $params);

            if (!empty($results)) {
                $this->usernameErr = 'That username is already taken.';
            }
        }

        return empty($this->usernameErr);
    }

    public function validateEmail(?int $userId = null): bool
    {
        $this->emailErr = '';

        if (empty(trim($this->email)) || is_null($this->email)) {
            $this->emailErr = 'Email cannot be empty.';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->emailErr = 'Invalid email format.';
        }

        if (empty($this->emailErr) && $this->db) {
            $sql = "SELECT user_id FROM users WHERE email = :email";
            $params = ['email' => $this->email];

            if ($userId !== null) {
                $sql .= " AND user_id != :user_id";
                $params['user_id'] = $userId;
            }

            $results = $this->db->query($sql, $params);

            if (!empty($results)) {
                $this->emailErr = 'That email address is already registered.';
            }
        }

        return empty($this->emailErr);
    }

    public function validatePassword(): bool
    {
        $this->passwordErr = '';

        if (empty($this->password)) {
            $this->passwordErr = 'Password cannot be empty.';
        } elseif (strlen($this->password) < 8) {
            $this->passwordErr = 'Password must be at least 8 characters long.';
        }

        return empty($this->passwordErr);
    }

    public function validateLogin(): bool
    {
        if (empty(trim($this->username)) || is_null($this->username)) {
            $this->usernameErr = 'Username cannot be empty.';
        }

        if (empty($this->password)) {
            $this->passwordErr = 'Password cannot be empty.';
        }

        return empty($this->usernameErr) && empty($this->passwordErr);
    }

    public function register(): bool
    {
        if (!$this->db) {
            throw new \Exception('Database connection required for registration.');
        }

        $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
        $params = [
            'username' => $this->username,
            'email' => $this->email,
            'password' => password_hash($this->password, PASSWORD_DEFAULT),
        ];

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (\PDOException $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'users.username')) {
                $this->usernameErr = 'That username is already taken.';
            } elseif (str_contains($message, 'users.email')) {
                $this->emailErr = 'That email address is already registered.';
            }

            $this->logger?->error(
                'User registration failed: ' . $message,
                [
                    'username' => $this->username,
                    'email' => $this->email,
                ]
            );

            return false;
        }
    }

    public function updateAccount(
        int $userId,
        ?string $username = null,
        ?string $email = null,
        ?string $password = null
    ): bool
    {
        if (!$this->db) {
            throw new \Exception('Database connection required for updating account.');
        }

        $fields = [];
        $params = ['user_id' => $userId];

        if ($username !== null) {
            $fields[] = 'username = :username';
            $params['username'] = $username;
        }

        if ($email !== null) {
            $fields[] = 'email = :email';
            $params['email'] = $email;
        }

        if ($password !== null) {
            $fields[] = 'password = :password';
            $params['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return true;
        }

        $sql = 'UPDATE users SET '
            . implode(', ', $fields)
            . ' WHERE user_id = :user_id';

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (\PDOException $e) {
            $message = $e->getMessage();

            if (str_contains($message, 'users.username')) {
                $this->usernameErr = 'That username is already taken.';
            } elseif (str_contains($message, 'users.email')) {
                $this->emailErr = 'That email address is already registered.';
            }

            $this->accountErr = 'Unable to update your account. Please try again.';

            $this->logger?->error(
                'Account update failed for user ID: ' . $userId . '. ' . $message
            );

            return false;
        }
    }

    public function isLoggedIn(): bool
    {
        // Check session or cookie here as needed
        return isset($_SESSION['user']);
    }

    public function isAdmin(): bool
    {
        // Check if the logged-in user has admin privileges
        return (isset($_SESSION['user']['admin']) && $_SESSION['user']['admin'] == 1);
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    public function login(): bool
    {
        if (!$this->db) {
            throw new \Exception('Database connection required for login.');
        }

        $this->loginErr = '';

        $sql = "SELECT * FROM users
                WHERE username = :username
                OR email = :username";

        $results = $this->db->query($sql, [
            'username' => $this->username
        ]);

        if (empty($results)) {
            $this->logger?->warning(
                "Login attempt with non-existent user: " . $this->username
            );

            $this->loginErr = 'Invalid username or password.';
            return false;
        }

        $user = $results[0];

        if (!password_verify($this->password, $user['password'])) {
            $this->logger?->warning(
                "Login attempt with incorrect password for user: " . $this->username
            );

            $this->loginErr = 'Invalid username or password.';
            return false;
        }

        if (empty($user['approved_at'])) {
            $this->logger?->info(
                "Login attempt for unapproved user: " . $this->username
            );

            $this->loginErr = 'Your account is awaiting approval.';
            return false;
        }

        if (!empty($user['disabled_at'])) {
            $this->logger?->info(
                "Login attempt for disabled user: " . $this->username
            );

            $this->loginErr = 'Your account has been disabled.';
            return false;
        }

        $user['password'] = null;

        session_regenerate_id(true);

        $_SESSION['user'] = $user;

        $this->logger?->info(
            "User logged in successfully: " . $this->username
        );

        return true;
    }

    public function getSessionUser(): array
    {
        return $_SESSION['user'] ?? [];
    }

    public function getUsers(): array
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for retrieving users.'
            );
        }

        $sql = "
            SELECT
                user_id,
                username,
                email,
                admin,
                approved_at,
                disabled_at,
                balance,
                created_at
            FROM users
            ORDER BY created_at DESC
        ";

        return $this->db->query($sql);
    }

    public function approve(int $userId): bool
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for approving user.'
            );
        }

        $sql = "
            UPDATE users
            SET approved_at = CURRENT_TIMESTAMP,
                disabled_at = NULL
            WHERE user_id = :user_id
            AND approved_at IS NULL
        ";

        try {
            $this->db->query($sql, [
                'user_id' => $userId,
            ]);

            $this->logger?->info(
                'User approved.',
                ['user_id' => $userId]
            );

            return true;
        } catch (\PDOException $e) {
            $this->logger?->error(
                'User approval failed: ' . $e->getMessage(),
                ['user_id' => $userId]
            );

            return false;
        }
    }

    public function disable(int $userId): bool
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for disabling user.'
            );
        }

        $sql = "
            UPDATE users
            SET disabled_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id
            AND approved_at IS NOT NULL
            AND disabled_at IS NULL
        ";

        try {
            $this->db->query($sql, [
                'user_id' => $userId,
            ]);

            $this->logger?->info(
                'User disabled.',
                ['user_id' => $userId]
            );

            return true;
        } catch (\PDOException $e) {
            $this->logger?->error(
                'User disable failed: ' . $e->getMessage(),
                ['user_id' => $userId]
            );

            return false;
        }
    }

    public function enable(int $userId): bool
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for enabling user.'
            );
        }

        $sql = "
            UPDATE users
            SET disabled_at = NULL
            WHERE user_id = :user_id
            AND approved_at IS NOT NULL
            AND disabled_at IS NOT NULL
        ";

        try {
            $this->db->query($sql, [
                'user_id' => $userId,
            ]);

            $this->logger?->info(
                'User enabled.',
                ['user_id' => $userId]
            );

            return true;
        } catch (\PDOException $e) {
            $this->logger?->error(
                'User enable failed: ' . $e->getMessage(),
                ['user_id' => $userId]
            );

            return false;
        }
    }

}
