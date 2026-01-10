<?php

namespace Webmin;

use Database;

class User {

    public $username = '';
    public $usernameErr = '';
    public $email = '';
    public $emailErr = '';
    public $password = '';
    public $passwordErr = '';

    private $db;
    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        }
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function validateUsername(): bool
    {

        if (empty(trim($this->username)) || is_null($this->username)) {
            $this->usernameErr = 'Username cannot be empty.';
        } elseif (strlen($this->username) < 3 || strlen($this->username) > 20) {
            $this->usernameErr = 'Username must be between 3 and 20 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $this->username)) {
            $this->usernameErr = 'Username can only contain letters, numbers, and underscores.';
        }

        if ($this->db) {  // Only check DB if $db is provided
            $sql = "SELECT user_id FROM users WHERE username = :username";
            $results = $this->db->query($sql, ['username' => $this->username]);

            if (!empty($results)) {
                $this->usernameErr = 'That username is already taken.';
            }
        }

        return empty($this->usernameErr);
    }

    public function validateEmail(): bool
    {
        if (empty(trim($this->email)) || is_null($this->email)) {
            $this->emailErr = 'Email cannot be empty.';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $this->emailErr = 'Invalid email format.';
        }

        return empty($this->emailErr);
    }

    public function validatePassword(): bool
    {
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
            return false;
        }
    }

    public function updatePassword($userId, $password): bool
    {
        if (!$this->db) {
            throw new \Exception('Database connection required for updating password.');
        }

        $sql = "UPDATE users SET password = :password WHERE user_id = :user_id";
        $params = [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'user_id' => $userId,
        ];

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function isLoggedIn(): bool
    {
        // Check session or cookie here as needed
        return isset($_SESSION['user']);
    }

    public function logout(): void
    {
        // Clear session or cookie here as needed
        session_unset();
        session_destroy();
    }

    public function login(): bool
    {
        if (!$this->db) {
            throw new \Exception('Database connection required for login.');
        }

        $sql = "SELECT * FROM users WHERE username = :username or email = :username";
        $results = $this->db->query($sql, ['username' => $this->username]);

        if (!empty($results)) {
            $hashedPassword = $results[0]['password'];
            if (password_verify($this->password, $hashedPassword)) {
                $results[0]['password'] = null;  // Clear password for security
                // Set session and cookie
                $_SESSION['user'] = $results[0];

                return true;
            }
        }

        return false;
    }

    public function getSessionUser(): array
    {
        return $_SESSION['user'] ?? [];
    }
}
