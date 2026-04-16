<?php

use Webmin\User;
use Webmin\Database;
use MotoGp\Utility;

$user = new User();
if (!$user->isAdmin()) {
    header("Location: /");
    exit();
}

$db = new Database($config['database']['dsn']);

Utility::dump($config);
Utility::dump($user);
Utility::dump($db);

