<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\Session;
use MotoGp\Event;
use MotoGp\Player;
use MotoGp\Utility;

$app = require __DIR__ . '/../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$db = new Database($config['database']['dsn'], $logger);
$playerModel = new Player($db, $logger);

$session = new Session();

$eventModel = new Event($db);
$eventData = $eventModel->getNextEvent();

$data['user'] = $session->getUser();

if ($session->isLoggedIn()) {
    $data['show_ladder'] = true;
    $data['leaders'] = $playerModel->getLadder(3);
}

$data['app'] = $config['app'];
$data['page'] = [
    'title' => 'Home',
    'heading' => $config['app']['season'] . ' Season',
    'days_to_go' => $eventData ? Utility::daysToGo($eventData['start_date']) : 'N/A',
    'next_race_name' => $eventData ? $eventData['name'] : 'N/A'
];

$tpl = new Template($config['template'], $logger);
echo $tpl->render('main', $data);
