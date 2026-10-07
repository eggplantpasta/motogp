<?php

use Webmin\Session;
use MotoGp\Event;
use MotoGp\Player;
use MotoGp\Utility;

$app = require __DIR__ . '/../src/bootstrap.php';

$db = $app->db;
$tpl = $app->template;

$playerModel = new Player($db, $app->logger);
$eventModel = new Event($db);
$session = new Session();

$eventData = $eventModel->getNextEvent();

$data['user'] = $session->getUser();

if ($session->isLoggedIn()) {
    $data['show_ladder'] = true;
    $data['leaders'] = $playerModel->getLadder(3);
}

$data['page'] = [
    'days_to_go' => $eventData
        ? Utility::daysToGo($eventData['start_date'])
        : 'N/A',
    'next_race_name' => $eventData
        ? $eventData['name']
        : 'N/A',
];

echo $tpl->render('main', $data);
