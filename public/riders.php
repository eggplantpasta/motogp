<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use MotoGp\Riders;

// get session user
$user = new User();

// get the riders from the db
$db = new Database($config['database']['dsn']);
$riders = new Riders($db);
$results = $riders->getRiders();

$tpl = new Template($config['template']);

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Riders';
$data['page']['heading'] = 'Season ' . $config['app']['season'] . ' Riders';
$data['riders'] = $results;

echo $tpl->render('riders', $data);


