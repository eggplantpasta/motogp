<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use Webmin\Session;
use MotoGp\Player;

$app = require __DIR__ . '/../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$db = new Database($config['database']['dsn'], $logger);
$user = new User($db, $logger);
$player = new Player($db, $logger);
$session = new Session();

if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

$data['app'] = $config['app'];
$data['user'] = $session->getUser();
$data['page']['title'] = 'Ladder';
$data['page']['heading'] = 'Ladder';

$data['ladder'] = $player->getLadder();

$position = 1;

foreach ($data['ladder'] as &$player) {
    $player['position'] = $position++;
}

unset($player);

$tpl = new Template($config['template'], $logger);

echo $tpl->render('ladder', $data);
