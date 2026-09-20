<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;

$app = require __DIR__ . '/../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$db = new Database($config['database']['dsn'], $logger);
$user = new User($db);

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Ladder';
$data['page']['heading'] = 'Ladder';

$data['ladder'] = $user->getLadder();

$position = 1;

foreach ($data['ladder'] as &$player) {
    $player['position'] = $position++;
}

unset($player);

$tpl = new Template($config['template']);

echo $tpl->render('ladder', $data);
