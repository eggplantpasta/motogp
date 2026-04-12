<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use MotoGp\Utility;
use MotoGp\Event;
use MotoGp\Result;

// get session user
$user = new User();

// get the data from the db
$db = new Database($config['database']['dsn']);

$event = new Event($db);
$result = new Result($db);

if (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) {
    $eventId = (int)$_GET['event_id'];
} else {
    $eventId = $event->getLastEventId();
    $data['results'] = $result->getResultsByEventId($eventId);
}
$data['event'] = $event->getEventById($eventId);
$data['results'] = $result->getResultsByEventId($eventId);

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Results';
$data['page']['heading'] = 'Results';

// format date
$data['event']['display_date'] = Utility::formatDate($data['event']['start_date'], 'M d');

$tpl = new Template($config['template']);
echo $tpl->render('results', $data);


