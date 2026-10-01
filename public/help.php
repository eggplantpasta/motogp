<?php

use Webmin\Template;
use Webmin\Session;

$app = require __DIR__ . '/../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$session = new Session();

$data['app'] = $config['app'];
$data['user'] = $session->getUser();

$tpl = new Template($config['template'], $logger);

echo $tpl->render('help', $data);