<?php

use MotoGp\Player;
use Webmin\Csrf;
use Webmin\Database;
use Webmin\Session;
use Webmin\Template;
use Webmin\User;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$tpl = new Template($config['template'], $logger);
$db = new Database($config['database']['dsn'], $logger);
$userModel = new User($db, $logger);
$playerModel = new Player($db, $logger);
$session = new Session();

if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$session->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

if (!isset($_GET['user_id']) || !ctype_digit($_GET['user_id'])) {
    http_response_code(404);
    exit('User not found.');
}

$userId = (int)$_GET['user_id'];

$account = $userModel->getUserById($userId);

if ($account === null) {
    http_response_code(404);
    exit('User not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'approve':
            if (!$userModel->approve($userId)) {
                http_response_code(500);
                exit('Unable to approve user.');
            }
            break;

        case 'disable':
            if (!$userModel->disable($userId)) {
                http_response_code(400);
                exit(
                    'Unable to disable this account. '
                    . 'The account may be the final active administrator.'
                );
            }
            break;

        case 'enable':
            if (!$userModel->enable($userId)) {
                http_response_code(500);
                exit('Unable to enable user.');
            }
            break;

        case 'set_admin':
            $admin = isset($_POST['admin']);

            if (!$userModel->setAdmin($userId, $admin)) {
                http_response_code(400);
                exit(
                    'Unable to change administrator access. '
                    . 'The account may be the final active administrator.'
                );
            }
            break;

        case 'update_balance':
            $balance = filter_input(
                INPUT_POST,
                'balance',
                FILTER_VALIDATE_INT
            );

            if ($balance === false || $balance < 0) {
                http_response_code(400);
                exit(
                    'Balance must be a whole number of zero or greater.'
                );
            }

            if (!$playerModel->adjustBalance($userId, $balance)) {
                http_response_code(500);
                exit('Unable to update user balance.');
            }
            break;

        case 'delete':
            if (!$userModel->deleteUser($userId)) {
                http_response_code(400);
                exit(
                    'Unable to delete this account. '
                    . 'The account may be the final active administrator.'
                );
            }

            header('Location: /admin/users.php');
            exit();

        default:
            http_response_code(400);
            exit('Invalid action.');
    }

    header('Location: /admin/user.php?user_id=' . $userId);
    exit();
}

$account['balance'] = $playerModel->getBalance($userId);

$account['isLastActiveAdmin'] =
    $userModel->isLastActiveAdmin($userId);

$account['canApprove'] =
    empty($account['approved_at']);

$account['canDisable'] =
    !empty($account['approved_at'])
    && empty($account['disabled_at']);

$account['canEnable'] =
    !empty($account['approved_at'])
    && !empty($account['disabled_at']);

$account['canSetAdmin'] =
    !empty($account['approved_at'])
    && empty($account['disabled_at'])
    && !$account['isLastActiveAdmin'];

$account['hasBids'] =
    $playerModel->hasBids($userId);

$data['account'] = $account;
$data['user'] = $session->getUser();
$data['csrfToken'] = Csrf::token();

echo $tpl->render('admin/user', $data);
