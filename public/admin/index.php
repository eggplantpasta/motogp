<?php

use Webmin\Template;
use Webmin\Session;

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

$data['user'] = $session->getUser();
$data['page'] = [
    'title' => 'Admin',
    'heading' => 'Administration',
];

echo $tpl->render('admin/index', $data);
