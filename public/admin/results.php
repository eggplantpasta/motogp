<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use Webmin\Csrf;
use MotoGp\Event;
use MotoGp\Result;

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

$events = new Event($db);
$results = new Result($db);

$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Results';
$data['page']['heading'] = 'Manage Results';
$data['csrfToken'] = Csrf::token();

$data['form'] = [
    'message' => '',
    'message-class' => '',
    'errors' => [],
];

$eventId = null;

if (isset($_GET['event_id']) && ctype_digit($_GET['event_id'])) {
    $eventId = (int)$_GET['event_id'];
} elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $eventId = $events->getLastEventId();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $postedEventId = $_POST['event-id'] ?? '';

    if ($postedEventId === '' || !ctype_digit($postedEventId)) {
        $data['form']['message'] = 'A valid event is required.';
        $data['form']['message-class'] = 'error';
    } else {
        $eventId = (int)$postedEventId;
        $positions = $_POST['positions'] ?? [];
        $saveResults = [];
        $usedPositions = [];

        foreach ($positions as $riderId => $position) {
            $position = trim((string)$position);

            if ($position === '') {
                continue;
            }

            if (!ctype_digit((string)$riderId) || !ctype_digit($position) || (int)$position < 1) {
                $data['form']['message'] = 'Positions must be positive whole numbers.';
                $data['form']['message-class'] = 'error';
                break;
            }

            $position = (int)$position;

            if (isset($usedPositions[$position])) {
                $data['form']['message'] = "Position {$position} has been entered more than once.";
                $data['form']['message-class'] = 'error';
                break;
            }

            $usedPositions[$position] = true;
            $saveResults[(int)$riderId] = $position;
        }

        if ($data['form']['message'] === '') {
            if ($results->saveResults($eventId, $saveResults)) {
                header('Location: /admin/results.php?event_id=' . $eventId);
                exit();
            }

            $data['form']['message'] = 'Unable to save results.';
            $data['form']['message-class'] = 'error';
        }
    }
}

$data['events'] = $events->getEvents();
$data['event'] = $eventId !== null
    ? $events->getEventById($eventId)
    : null;

$data['results'] = $eventId !== null
    ? $results->getRidersForEventResults($eventId)
    : [];

foreach ($data['events'] as &$event) {
    $event['selected'] = (int)$event['event_id'] === $eventId;
}
unset($event);

$tpl = new Template($config['template']);

echo $tpl->render('admin/results', $data);
