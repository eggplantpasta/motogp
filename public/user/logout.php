<?php

use Webmin\User;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$user = new User();
if ($user->isLoggedIn()) {
    $user->logout();
}
header("Location: /user/login.php");
exit();
