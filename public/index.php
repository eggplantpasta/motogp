<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use MotoGp\Event;
use MotoGp\Utility;

$app = require __DIR__ . '/../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$db = new Database($config['database']['dsn'], $logger);

$user = new User($db);

$event = new Event($db);
$eventData = $event->getNextEvent();

$data['user'] = $user->getSessionUser();

if ($user->isLoggedIn()) {
    $data['show_ladder'] = true;
    $data['leaders'] = $user->getLadder(3);
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
