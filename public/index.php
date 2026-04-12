<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use MotoGp\Event;
use MotoGp\Utility;

// get session user
$user = new User();

// get the data from the db
$db = new Database($config['database']['dsn']);

$event = new Event($db);
$eventData = $event->getNextEvent();

$data['user'] = $user->getSessionUser();
$data['app'] = $config['app'];
$data['page'] = [
    'title' => 'Home',
    'heading' => '2026 Season',
    'days_to_go' => $eventData ? Utility::daysToGo($eventData['start_date']) : 'N/A',
    'next_race_name' => $eventData ? $eventData['name'] : 'N/A'
];

$tpl = new Template($config['template']);
echo $tpl->render('main', $data);


