<?php

use Webmin\Database;
use Webmin\Template;
use Webmin\User;
use Webmin\Csrf;

$tpl = new Template($config['template']);
$db = new Database($config['database']['dsn']);
$user = new User($db);

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$user->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        http_response_code(400);
        exit('Invalid user.');
    }

    switch ($action) {
        case 'approve':
            if (!$user->approve($userId)) {
                http_response_code(500);
                exit('Unable to approve user.');
            }
            break;

        case 'disable':
            if (!$user->disable($userId)) {
                http_response_code(400);
                exit(
                    'Unable to disable this account. '
                    . 'The account may be the final active administrator.'
                );
            }
            break;

        case 'enable':
            if (!$user->enable($userId)) {
                http_response_code(500);
                exit('Unable to enable user.');
            }
            break;

        case 'promote':
            if (!$user->promote($userId)) {
                http_response_code(500);
                exit('Unable to promote user.');
            }
            break;

        case 'demote':
            if (!$user->demote($userId)) {
                http_response_code(400);
                exit(
                    'Unable to remove administrator access. '
                    . 'The account may be the final active administrator.'
                );
            }
            break;

        default:
            http_response_code(400);
            exit('Invalid action.');
    }

    header('Location: /admin/users.php');
    exit();
}

$data['users'] = $user->getUsers();

foreach ($data['users'] as &$account) {
    if (!empty($account['disabled_at'])) {
        $account['status'] = 'Disabled';
    } elseif (empty($account['approved_at'])) {
        $account['status'] = 'Pending';
    } else {
        $account['status'] = 'Active';
    }

    $account['canApprove'] =
        empty($account['approved_at']);

    $account['canDisable'] =
        !empty($account['approved_at'])
        && empty($account['disabled_at']);

    $account['canEnable'] =
        !empty($account['disabled_at']);

    $account['canPromote'] =
        !$account['admin']
        && !empty($account['approved_at'])
        && empty($account['disabled_at']);

    $account['canDemote'] =
        $account['admin']
        && !empty($account['approved_at'])
        && empty($account['disabled_at']);

    $account['adminDisplay'] = $account['admin'] ? 'Yes' : 'No';
}
unset($account);

$action = $_POST['action'] ?? '';
$userId = (int)($_POST['user_id'] ?? 0);
$data['csrfToken'] = Csrf::token();
echo $tpl->render('admin/users', $data);
