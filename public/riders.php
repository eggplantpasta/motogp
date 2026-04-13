<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;

// get session user
$user = new User();

// get the riders from the db
$db = new Database($config['database']['dsn']);
$results = $db->query('SELECT name FROM riders');

$tpl = new Template($config['template']);

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Riders';
$data['page']['heading'] = 'Riders for ' . $config['app']['season'] . ' Season';
$data['riders'] = $results;

echo $tpl->render('riders', $data);


