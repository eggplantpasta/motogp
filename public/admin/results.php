<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use Webmin\Csrf;
use MotoGp\Event;
use MotoGp\Result;

require_once __DIR__ . '/../../src/bootstrap.php';

$user = new User();

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$user->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$db = new Database($config['database']['dsn'], $logger);

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
        $event = $events->getEventById($eventId);

        if ($event === null) {
            $data['form']['message'] = 'Event does not exist.';
            $data['form']['message-class'] = 'error';
        } elseif ($event['payouts_settled_at'] !== null) {
            http_response_code(400);
            exit('Results cannot be changed after payouts have been settled.');
        } else {
            $riders = $results->getRidersForEventResults($eventId);
            $validRiderIds = array_column(
                $riders,
                null,
                'rider_id'
            );

            $positions = $_POST['positions'] ?? [];
            $statuses = $_POST['statuses'] ?? [];

            $saveResults = [];
            $usedPositions = [];

            foreach ($validRiderIds as $riderId => $rider) {
                $position = trim(
                    (string)($positions[$riderId] ?? '')
                );

                $status = trim(
                    (string)($statuses[$riderId] ?? '')
                );

                /*
                * Completely blank means this rider did not
                * participate in this event.
                */
                if ($position === '' && $status === '') {
                    continue;
                }

                /*
                * A position without an explicit status is
                * a normal classified finish.
                */
                if ($position !== '' && $status === '') {
                    $status = 'classified';
                }

                if (!in_array(
                    $status,
                    ['classified', 'dnf', 'dns', 'dsq'],
                    true
                )) {
                    $data['form']['message'] =
                        'Invalid result status.';
                    $data['form']['message-class'] = 'error';
                    break;
                }

                if ($status === 'classified') {
                    if (
                        !ctype_digit($position)
                        || (int)$position < 1
                    ) {
                        $data['form']['message'] =
                            'Classified riders require a valid position.';
                        $data['form']['message-class'] = 'error';
                        break;
                    }

                    $position = (int)$position;

                    if (isset($usedPositions[$position])) {
                        $data['form']['message'] =
                            "Position {$position} has been entered more than once.";
                        $data['form']['message-class'] = 'error';
                        break;
                    }

                    $usedPositions[$position] = true;
                } else {
                    if ($position !== '') {
                        $data['form']['message'] =
                            strtoupper($status) .
                            ' riders cannot have a finishing position.';
                        $data['form']['message-class'] = 'error';
                        break;
                    }

                    $position = null;
                }

                $saveResults[(int)$riderId] = [
                    'position' => $position,
                    'status' => $status,
                ];
            }

            if ($data['form']['message'] === '') {
                if ($results->saveResults(
                    $eventId,
                    $saveResults
                )) {
                    header(
                        'Location: /admin/results.php?event_id=' .
                        $eventId
                    );
                    exit();
                }

                $data['form']['message'] =
                    'Unable to save results.';
                $data['form']['message-class'] = 'error';
            }
        }
    }
}

$data['events'] = $events->getEvents();
$data['event'] = $eventId !== null
    ? $events->getEventById($eventId)
    : null;

$data['settled'] =
    $data['event'] !== null
    && $data['event']['payouts_settled_at'] !== null;

$data['results'] = $eventId !== null
    ? $results->getRidersForEventResults($eventId)
    : [];

foreach ($data['results'] as &$result) {
    $result['status_none'] =
        $result['status'] === null
        || $result['status'] === 'classified';

    $result['status_dnf'] =
        $result['status'] === 'dnf';

    $result['status_dns'] =
        $result['status'] === 'dns';

    $result['status_dsq'] =
        $result['status'] === 'dsq';
}
unset($result);

foreach ($data['events'] as &$event) {
    $event['selected'] = (int)$event['event_id'] === $eventId;
}
unset($event);

$tpl = new Template($config['template']);

echo $tpl->render('admin/results', $data);
