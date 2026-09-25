<?php

use MotoGp\Admin;
use Webmin\Database;
use Webmin\Session;
use Webmin\Template;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$session = new Session();

if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$session->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$tpl = new Template($config['template'], $logger);
$db = new Database($config['database']['dsn'], $logger);
$adminModel = new Admin($db);

$pendingUserExpiryDays =
    (int)($config['app']['pending_user_expiry_days'] ?? 7);

$data = $adminModel->getDashboard($pendingUserExpiryDays);

$data['pendingUserExpiryDays'] = $pendingUserExpiryDays;
$data['user'] = $session->getUser();

echo $tpl->render('admin/index', $data);
