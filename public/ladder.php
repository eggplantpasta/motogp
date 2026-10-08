<?php

use Webmin\Session;
use MotoGp\Player;

$app = require __DIR__ . '/../src/bootstrap.php';

$playerModel = new Player($app->db, $app->logger);
$session = new Session();

if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

$data['user'] = $session->getUser();

$data['ladder'] = $playerModel->getLadder();

$position = 1;

foreach ($data['ladder'] as &$player) {
    $player['position'] = $position++;
}

unset($player);

echo $app->template->render('ladder', $data);
