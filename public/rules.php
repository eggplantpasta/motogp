<?php

use Webmin\Template;
use Webmin\User;

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

$tpl = new Template($config['template']);

echo $tpl->render('rules', $data);
