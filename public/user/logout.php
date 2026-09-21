<?php

use Webmin\Session;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$session = new Session();
if ($session->isLoggedIn()) {
    $session->logout();
}
header("Location: /user/login.php");
exit();
