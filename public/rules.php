<?php

use Webmin\Template;
use Webmin\User;

$app = require __DIR__ . '/../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$user = new User();

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page'] = [
    'title' => 'Game Rules',
    'heading' => 'Game Rules',
];

$tpl = new Template($config['template'], $logger);

echo $tpl->render('rules', $data);
