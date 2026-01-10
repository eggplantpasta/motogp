<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use MotoGp\Utility;
use MotoGp\Event;

// get session user
$user = new User();

// get the data from the db
$db = new Database($config['database']['dsn']);

$event = new Event($db);
$next_event_id = $event->nextEvent();

$all_events = $event->getEvents();

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Events';
$data['page']['heading'] = 'Season 2026';
$data['events'] = $all_events;

// manipulate columns for display
foreach ($data['events'] as &$event) {
    // set row class
    if ($event['event_id'] == $next_event_id) {
        $event['rowclass'] = 'row-highlight';
    } elseif (strtotime($event['start_date']) < time()) {
        $event['rowclass'] = 'row-disable';
    } else {
        $event['rowclass'] = '';
    }
    // format date
    $date = new \DateTime($event['start_date']);
    $event['display_date'] = $date->format('M d');
}

echo Utility::dump($data);

$tpl = new Template($config['template']);
echo $tpl->render('events', $data);


