<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use MotoGp\Event;
use MotoGp\Utility;

$user = new User();

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$user->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$db = new Database($config['database']['dsn']);
$event = new Event($db);

$data['user'] = $user->getSessionUser();
$data['events'] = $event->getEvents();
$data['page']['title'] = 'Events';
$data['page']['heading'] = 'Manage Events';

foreach ($data['events'] as &$eventData) {
    $eventData['display_date'] = Utility::formatDate(
        $eventData['start_date'],
        'M d'
    );
}

unset($eventData);

$tpl = new Template($config['template']);

echo $tpl->render('admin/events', $data);
