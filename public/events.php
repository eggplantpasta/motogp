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
$next_event_id = $event->getNextEventId();

$all_events = $event->getEvents();

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Events';
$data['page']['heading'] = 'Season ' . $config['app']['season'] . ' Races';
$data['events'] = $all_events;

// manipulate columns for display
foreach ($data['events'] as &$event) {
    // set row class
    if ($event['event_id'] == $next_event_id) {
        $event['cell-class'] = '';
        $event['row-class'] = 'motogp-highlight';
        $event['results'] = false;
    } elseif (strtotime($event['start_date']) < time()) {
        $event['cell-class'] = 'motogp-disable';
        $event['row-class'] = '';
        $event['results'] = true;
    } else {
        $event['cell-class'] = '';
        $event['row-class'] = '';
        $event['results'] = false;
    }
    // format date
    $event['display_date'] = Utility::formatDate($event['start_date'], 'M d');

}

$tpl = new Template($config['template']);
echo $tpl->render('events', $data);

