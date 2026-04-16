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

echo Utility::dump($config);
echo Utility::dump($user);
echo Utility::dump($db);

