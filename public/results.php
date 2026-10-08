<?php

use Webmin\Session;
use MotoGp\Utility;
use MotoGp\Event;
use MotoGp\Result;

$app = require __DIR__ . '/../src/bootstrap.php';

$session = new Session();

$eventModel = new Event($app->db);
$resultModel = new Result($app->db);

if (isset($_GET['event_id'])) {
    if (!ctype_digit($_GET['event_id'])) {
        http_response_code(404);
        exit('Event not found.');
    }

    $eventId = (int)$_GET['event_id'];
    $event = $eventModel->getEventById($eventId);

    if ($event === null) {
        http_response_code(404);
        exit('Event not found.');
    }
} else {
    $eventId = $eventModel->getLastEventId();

    if ($eventId === null) {
        http_response_code(404);
        exit('No results available.');
    }

    $event = $eventModel->getEventById($eventId);

    if ($event === null) {
        http_response_code(404);
        exit('Event not found.');
    }
}

$event['display_date'] =
    Utility::formatDate($event['start_date'], 'M d');

$data['event'] = $event;
$data['results'] = $resultModel->getResultsByEventId($eventId);

foreach ($data['results'] as &$result) {
    $result['display_status'] = match ($result['status']) {
        'classified' => '',
        'dnf' => 'DNF',
        'dns' => 'DNS',
        'dsq' => 'DSQ',
        default => strtoupper($result['status']),
    };
}
unset($result);

$data['user'] = $session->getUser();

echo $app->template->render('results', $data);
