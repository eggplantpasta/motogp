<?php

use Webmin\Template;
use Webmin\User;

$user = new User();

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$user->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$tpl = new Template($config['template']);

$data['user'] = $user->getSessionUser();

echo $tpl->render('admin/index', $data);
