<?php

use Webmin\User;

$user = new User();
if ($user->isLoggedIn()) {
    $user->logout();
}
header("Location: /user/login.php");
exit();