<?php

use Webmin\Template;
use Webmin\User;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$user = new User();

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$user->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$tpl = new Template($config['template'], $logger);

$data['user'] = $user->getSessionUser();

echo $tpl->render('admin/index', $data);
