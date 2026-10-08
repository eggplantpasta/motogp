<?php

use Webmin\Session;

$app = require __DIR__ . '/../src/bootstrap.php';

$session = new Session();

$data['user'] = $session->getUser();

echo $app->template->render('help', $data);
