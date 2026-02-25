<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use MotoGp\Event;

// get the data from the db
$db = new Database($config['database']['dsn']);

$event = new Event($db);
$eventData = $event->getNextEvent();

$data= [
    'app' => $config['app'],
    'title' => 'MotoGP',
    'heading' => 'MotoGP Bidding',
    'days_to_go' => $eventData ? ceil((strtotime($eventData['start_date']) - time()) / (60 * 60 * 24)) : 'N/A',
    'next_race_name' => $eventData ? $eventData['name'] : 'N/A'
];

$tpl = new Template($config['template']);
echo $tpl->render('main', $data);


