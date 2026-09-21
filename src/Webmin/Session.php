<?php

namespace Webmin;

class Session
{
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user']);
    }

    public function isAdmin(): bool
    {
        return isset($_SESSION['user']['admin'])
            && $_SESSION['user']['admin'] == 1;
    }

    public function getUser(): array
    {
        return $_SESSION['user'] ?? [];
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
                'samesite' => $params['samesite'],
            ]);
        }

        session_destroy();
    }
}
