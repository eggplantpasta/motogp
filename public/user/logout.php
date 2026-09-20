<?php

use Webmin\User;

require_once __DIR__ . '/../../src/bootstrap.php';

$user = new User();
if ($user->isLoggedIn()) {
    $user->logout();
}
header("Location: /user/login.php");
exit();
