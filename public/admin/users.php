<?php

use MotoGp\Player;
use MotoGp\Utility;
use Webmin\Session;
use Webmin\User;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$userModel = new User($app->db, $app->logger);
$playerModel = new Player($app->db, $app->logger);
$session = new Session();

if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$session->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$data['users'] = $userModel->getUsers();

foreach ($data['users'] as &$account) {
    $account['pendingApproval'] = empty($account['approved_at']);

    $account['disabled'] =
        !empty($account['approved_at'])
        && !empty($account['disabled_at']);

    $account['balance'] = $playerModel->getBalance(
        (int)$account['user_id']
    );

    $account['created_at_display'] =
        Utility::timeAgo($account['created_at']);
    ;
}
unset($account);

$data['user'] = $session->getUser();

echo $app->template->render('admin/users', $data);
