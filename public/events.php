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

$eventModel = new Event($db);
$nextEventId = $eventModel->getNextEventId();

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Events';
$data['page']['heading'] = 'Season ' . $config['app']['season'] . ' Races';
$data['events'] = $eventModel->getEvents();

// manipulate columns for display
foreach ($data['events'] as &$event) {
    // set row class
    if ($event['event_id'] == $nextEventId) {
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

unset($event);

$tpl = new Template($config['template']);
echo $tpl->render('events', $data);

