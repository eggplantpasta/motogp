<?php

use Webmin\Database;
use Webmin\Template;
use Webmin\User;
use Webmin\Session;
use MotoGp\Player;

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

$account['balance'] = $playerModel->getBalance($userId);

$data['account'] = $account;
$data['user'] = $session->getUser();

echo $tpl->render('admin/user', $data);
