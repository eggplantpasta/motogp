<?php

use MotoGp\Admin;
use Webmin\Session;
use Webmin\Csrf;
use Webmin\User;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$session = new Session();

if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$session->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$adminModel = new Admin($app->db);
$userModel = new User($app->db, $app->logger);

$pendingUserExpiryDays =
    (int)($config['app']['pending_user_expiry_days'] ?? 7);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete_expired_pending_users':
            $userModel->deleteExpiredPendingUsers(
                $pendingUserExpiryDays
            );
            break;

        default:
            http_response_code(400);
            exit('Invalid action.');
    }

    header('Location: /admin/');
    exit();
}

$data = $adminModel->getDashboard($pendingUserExpiryDays);

$data['pendingUserExpiryDays'] = $pendingUserExpiryDays;
$data['csrfToken'] = Csrf::token();
$data['user'] = $session->getUser();

echo $app->template->render('admin/index', $data);
