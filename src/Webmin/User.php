<?php

namespace Webmin;

use Psr\Log\LoggerInterface;
use Webmin\Database;

class User
{
    public $username = '';
    public $usernameErr = '';
    public $email = '';
    public $emailErr = '';
    public $password = '';
    public $passwordErr = '';
    public $loginErr = '';
    public $accountErr = '';

    private ?Database $db;
    private ?LoggerInterface $logger;

    public function __construct(?Database $db = null, ?LoggerInterface $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger;

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

            $result = $this->db->queryOne($sql, $params);

            if ($result !== null) {
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

            $result = $this->db->queryOne($sql, $params);

            if ($result !== null) {
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

        try {
            $this->db->beginTransaction();

            $this->db->execute(
                '
                    INSERT INTO users (
                        username,
                        email,
                        password
                    )
                    VALUES (
                        :username,
                        :email,
                        :password
                    )
                ',
                [
                    ':username' => $this->username,
                    ':email' => $this->email,
                    ':password' => password_hash(
                        $this->password,
                        PASSWORD_DEFAULT
                    ),
                ]
            );

            $userId = (int)$this->db
                ->getConnection()
                ->lastInsertId();

            $this->db->execute(
                '
                    INSERT INTO balance_transactions (
                        user_id,
                        transaction_type,
                        amount
                    )
                    VALUES (
                        :user_id,
                        \'opening_balance\',
                        20
                    )
                ',
                [
                    ':user_id' => $userId,
                ]
            );

            $this->db->commit();

            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();

            $message = $e->getMessage();

            if (str_contains($message, 'users.username')) {
                $this->usernameErr = 'That username is already taken.';
            } elseif (str_contains($message, 'users.email')) {
                $this->emailErr = 'That email address is already registered.';
            }

            return false;
        }
    }

    public function updateAccount(
        int $userId,
        ?string $username = null,
        ?string $email = null,
        ?string $password = null
    ): bool {
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
            $this->db->execute($sql, $params);
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

        $user = $this->db->queryOne($sql, [
            'username' => $this->username
        ]);

        if ($user === null) {
            $this->logger?->warning(
                "Login attempt with non-existent user: " . $this->username
            );

            $this->loginErr = 'Invalid username or password.';
            return false;
        }

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
            $this->db->execute($sql, [
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

        if ($this->isLastActiveAdmin($userId)) {
            $this->logger?->warning(
                'Attempt to disable final active administrator.',
                ['user_id' => $userId]
            );

            return false;
        }

        $sql = "
            UPDATE users
            SET disabled_at = CURRENT_TIMESTAMP
            WHERE user_id = :user_id
            AND approved_at IS NOT NULL
            AND disabled_at IS NULL
        ";

        try {
            $this->db->execute($sql, [
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
            $this->db->execute($sql, [
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

    public function setAdmin(int $userId, bool $admin): bool
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for changing administrator access.'
            );
        }

        if (!$admin && $this->isLastActiveAdmin($userId)) {
            $this->logger?->warning(
                'Attempt to demote final active administrator.',
                ['user_id' => $userId]
            );

            return false;
        }

        $sql = "
            UPDATE users
            SET admin = :admin
            WHERE user_id = :user_id
            AND approved_at IS NOT NULL
            AND disabled_at IS NULL
        ";

        try {
            $this->db->execute($sql, [
                'admin' => $admin ? 1 : 0,
                'user_id' => $userId,
            ]);

            $this->logger?->info(
                'User administrator status changed.',
                [
                    'user_id' => $userId,
                    'admin' => $admin,
                ]
            );

            return true;
        } catch (\PDOException $e) {
            $this->logger?->error(
                'Administrator status update failed: ' . $e->getMessage(),
                ['user_id' => $userId]
            );

            return false;
        }
    }

    public function isLastActiveAdmin(int $userId): bool
    {
        $sql = "
            SELECT COUNT(*) AS admin_count
            FROM users
            WHERE admin = 1
            AND approved_at IS NOT NULL
            AND disabled_at IS NULL
        ";

        $result = $this->db->queryOne($sql);

        if ((int)$result['admin_count'] !== 1) {
            return false;
        }

        $sql = "
            SELECT user_id
            FROM users
            WHERE user_id = :user_id
            AND admin = 1
            AND approved_at IS NOT NULL
            AND disabled_at IS NULL
        ";

        return $this->db->queryOne(
            $sql,
            ['user_id' => $userId]
        ) !== null;
    }

    public function adjustBalance(int $userId, int $balance): bool
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for adjusting balance.'
            );
        }

        if ($balance < 0) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $user = $this->db->queryOne(
                '
                    SELECT balance
                    FROM users
                    WHERE user_id = :user_id
                ',
                ['user_id' => $userId]
            );

            if ($user === null) {
                $this->db->rollBack();
                return false;
            }

            $currentBalance = (int)$user['balance'];
            $adjustment = $balance - $currentBalance;

            /*
            * Nothing has changed, so there is nothing to record.
            */
            if ($adjustment === 0) {
                $this->db->rollBack();
                return true;
            }

            $this->db->execute(
                '
                    UPDATE users
                    SET balance = :balance
                    WHERE user_id = :user_id
                ',
                [
                    ':balance' => $balance,
                    ':user_id' => $userId,
                ]
            );

            $this->db->execute(
                '
                    INSERT INTO balance_transactions (
                        user_id,
                        transaction_type,
                        amount
                    )
                    VALUES (
                        :user_id,
                        \'admin_adjustment\',
                        :amount
                    )
                ',
                [
                    ':user_id' => $userId,
                    ':amount' => $adjustment,
                ]
            );

            $this->db->commit();

            $this->logger?->info(
                'User balance adjusted.',
                [
                    'user_id' => $userId,
                    'old_balance' => $currentBalance,
                    'new_balance' => $balance,
                    'adjustment' => $adjustment,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();

            $this->logger?->error(
                'User balance adjustment failed: ' . $e->getMessage(),
                ['user_id' => $userId]
            );

            return false;
        }
    }

    public function deleteUser(int $userId): bool
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for deleting user.'
            );
        }

        if ($this->isLastActiveAdmin($userId)) {
            $this->logger?->warning(
                'Attempt to delete final active administrator.',
                ['user_id' => $userId]
            );

            return false;
        }

        try {
            $rows = $this->db->execute(
                'DELETE FROM users WHERE user_id = :user_id',
                ['user_id' => $userId]
            );

            if ($rows !== 1) {
                return false;
            }

            $this->logger?->info(
                'User deleted.',
                ['user_id' => $userId]
            );

            return true;
        } catch (\PDOException $e) {
            $this->logger?->error(
                'User deletion failed: ' . $e->getMessage(),
                ['user_id' => $userId]
            );

            return false;
        }
    }

    public function hasBids(int $userId): bool
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for checking user bids.'
            );
        }

        $result = $this->db->queryOne(
            'SELECT 1 FROM bids WHERE user_id = :user_id LIMIT 1',
            ['user_id' => $userId]
        );

        return $result !== null;
    }

    public function deleteExpiredPendingUsers(int $expiryDays): int
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for deleting expired users.'
            );
        }

        if ($expiryDays < 1) {
            throw new \InvalidArgumentException(
                'Pending user expiry must be at least 1 day.'
            );
        }

        $sql = "
            DELETE FROM users
            WHERE approved_at IS NULL
            AND created_at < datetime('now', :expiry)
        ";

        return $this->db->execute($sql, [
            'expiry' => "-{$expiryDays} days",
        ]);
    }

    public function getLadder(?int $limit = null): array
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for retrieving ladder.'
            );
        }

        $sql = '
            SELECT
                user_id,
                username,
                balance
            FROM users
            WHERE approved_at IS NOT NULL
            AND disabled_at IS NULL
            ORDER BY balance DESC, username
        ';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        return $this->db->query($sql);
    }

    public function getBalance(int $userId): ?int
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for getting user balance.'
            );
        }

        $result = $this->db->queryOne(
            'SELECT balance FROM users WHERE user_id = :user_id',
            ['user_id' => $userId]
        );

        if ($result === null) {
            return null;
        }

        return (int)$result['balance'];
    }

    public function getUserById(int $userId): ?array
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for retrieving user.'
            );
        }

        return $this->db->queryOne(
            '
                SELECT
                    user_id,
                    username,
                    balance
                FROM users
                WHERE user_id = :user_id
            ',
            [':user_id' => $userId]
        );
    }

    public function getBalanceTransactions(int $userId): array
    {
        if (!$this->db) {
            throw new \Exception(
                'Database connection required for retrieving transactions.'
            );
        }

        return $this->db->query(
            '
                SELECT
                    bt.transaction_id,
                    bt.transaction_type,
                    bt.amount,
                    bt.created_at,
                    e.name AS event_name,
                    r.name AS rider_name,
                    r.race_number
                FROM balance_transactions bt
                LEFT JOIN events e
                    ON e.event_id = bt.event_id
                LEFT JOIN bids b
                    ON b.bid_id = bt.bid_id
                LEFT JOIN riders r
                    ON r.rider_id = b.rider_id
                WHERE bt.user_id = :user_id
                ORDER BY
                    bt.created_at,
                    bt.transaction_id
            ',
            [':user_id' => $userId]
        );
    }

}
