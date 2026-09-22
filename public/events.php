<?php

use Webmin\Template;
use Webmin\Database;
use MotoGp\Utility;
use MotoGp\Event;
use Webmin\Session;

$app = require __DIR__ . '/../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$session = new Session();

$db = new Database($config['database']['dsn'], $logger);

$eventModel = new Event($db);
$nextEventId = $eventModel->getNextEventId();

$data['events'] = $eventModel->getEvents();

$data['app'] = $config['app'];
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

    $event['display_date'] =
        Utility::formatDate($event['start_date'], 'M d');

    if ($event['bids_open']) {
        $event['results'] = false;
    }
}
unset($event);

$tpl = new Template($config['template'], $logger);
echo $tpl->render('events', $data);
