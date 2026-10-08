<?php

use Webmin\Session;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$session = new Session();
if ($session->isLoggedIn()) {
    $session->logout();
}
header("Location: /user/login.php");
exit();
