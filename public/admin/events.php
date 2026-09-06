<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use MotoGp\Event;
use MotoGp\Utility;
use Webmin\Csrf;

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $eventId = (int)($_POST['event_id'] ?? 0);

    if ($eventId && $event->deleteEvent($eventId)) {
        header('Location: /admin/events.php');
        exit();
    }

    $data['message'] = 'Event could not be deleted. It may already have bids or results.';
    $data['message-class'] = 'error';
}

$data['user'] = $user->getSessionUser();
$data['events'] = $event->getEvents();
$data['page']['title'] = 'Events';
$data['page']['heading'] = 'Manage Events';
$data['csrfToken'] = Csrf::token();

foreach ($data['events'] as &$eventData) {
    $eventData['display_date'] = Utility::formatDate(
        $eventData['start_date'],
        'M d'
    );

    $eventData['can_delete'] =
        !$event->hasBids((int)$eventData['event_id']) &&
        !$event->hasResults((int)$eventData['event_id']);
}

unset($eventData);

$tpl = new Template($config['template']);

echo $tpl->render('admin/events', $data);
