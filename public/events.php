<?php

use MotoGp\Utility;
use MotoGp\Event;
use Webmin\Session;

$app = require __DIR__ . '/../src/bootstrap.php';

$session = new Session();

$eventModel = new Event($app->db);
$nextEventId = $eventModel->getNextEventId();

$data['events'] = $eventModel->getEvents();

$data['user'] = $session->getUser();

$today = date('Y-m-d');

foreach ($data['events'] as &$event) {
    if ($event['event_id'] === $nextEventId) {
        $event['cell-class'] = '';
        $event['row-class'] = 'motogp-highlight';
        $event['results'] = false;
    } elseif ($event['start_date'] < $today) {
        $event['cell-class'] = 'motogp-disable';
        $event['row-class'] = '';
        $event['results'] = true;
    } else {
        $event['cell-class'] = '';
        $event['row-class'] = '';
        $event['results'] = false;
    }

    $event['next_race'] = $event['event_id'] === $nextEventId;
    $event['display_date'] = Utility::formatDate($event['start_date'], 'M d');

    if ($event['bids_open']) {
        $event['results'] = false;
    }
}
unset($event);

echo $app->template->render('events', $data);
