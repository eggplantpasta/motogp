<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\Session;
use MotoGp\Utility;
use MotoGp\Event;
use MotoGp\Result;

$app = require __DIR__ . '/../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$session = new Session();

$db = new Database($config['database']['dsn'], $logger);

$events = new Event($db);
$results = new Result($db);

if (isset($_GET['event_id'])) {
    if (!ctype_digit($_GET['event_id'])) {
        http_response_code(404);
        exit('Event not found.');
    }

    $eventId = (int)$_GET['event_id'];
    $event = $events->getEventById($eventId);

    if ($event === null) {
        http_response_code(404);
        exit('Event not found.');
    }
} else {
    $eventId = $events->getLastEventId();
    $event = $events->getEventById($eventId);
}

$data['event'] = $event;
$data['results'] = $results->getResultsByEventId($eventId);

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

$data['app'] = $config['app'];
$data['user'] = $session->getUser();
$data['page']['title'] = 'Results';
$data['page']['heading'] = 'Results';

$data['event']['display_date'] =
    Utility::formatDate($data['event']['start_date'], 'M d');

$tpl = new Template($config['template'], $logger);

echo $tpl->render('results', $data);
