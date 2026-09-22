<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\Session;
use MotoGp\Event;
use MotoGp\Utility;
use MotoGp\Bid;
use Webmin\Csrf;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$session = new Session();

if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$session->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$db = new Database($config['database']['dsn'], $logger);

$eventModel = new Event($db);
$bidModel = new Bid($db);

$eventId = null;

if (isset($_GET['event_id'])) {
    if (!ctype_digit($_GET['event_id'])) {
        http_response_code(404);
        exit('Event not found.');
    }

    $eventId = (int)$_GET['event_id'];
} else {
    $eventId = $eventModel->getLastEventId();
}

$event = $eventId !== null
    ? $eventModel->getEventById($eventId)
    : null;

if ($eventId !== null && $event === null) {
    http_response_code(404);
    exit('Event not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    if ($event === null) {
        http_response_code(404);
        exit('Event not found.');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'resolve':
            if ((bool)$event['bids_open']) {
                http_response_code(400);
                exit(
                    'Bidding must be closed before bids can be resolved.'
                );
            }

            if ($event['bids_resolved_at'] !== null) {
                http_response_code(400);
                exit('Bids have already been resolved.');
            }

            if (!$bidModel->resolveBids($eventId)) {
                http_response_code(500);
                exit('Unable to resolve bids.');
            }

            header(
                'Location: /admin/bids.php?event_id='
                . $eventId
                . '&resolved=1'
            );
            exit();

        case 'settle':
            if ($event['bids_resolved_at'] === null) {
                http_response_code(400);
                exit(
                    'Bids must be resolved before payouts can be settled.'
                );
            }

            if ($event['payouts_settled_at'] !== null) {
                http_response_code(400);
                exit('Payouts have already been settled.');
            }

            if (!$bidModel->settlePayouts($eventId)) {
                http_response_code(500);
                exit('Unable to settle payouts.');
            }

            header(
                'Location: /admin/bids.php?event_id='
                . $eventId
                . '&settled=1'
            );
            exit();

        default:
            http_response_code(400);
            exit('Invalid action.');
    }
}

$data['app'] = $config['app'];
$data['user'] = $session->getUser();

$data['page'] = [
    'title' => 'Bid Resolution',
    'heading' => 'Bid Resolution',
];

if ($event !== null) {
    $event['display_date'] =
        Utility::formatDate($event['start_date'], 'M d');
}

$data['event'] = $event;

$data['events'] = $eventModel->getEvents();

foreach ($data['events'] as &$eventOption) {
    $eventOption['selected'] =
        (int)$eventOption['event_id'] === $eventId;
}
unset($eventOption);

$bids = $eventId !== null
    ? $bidModel->getEventBids($eventId)
    : [];

$riders = [];

foreach ($bids as $bid) {
    $riderId = (int)$bid['rider_id'];

    if (!isset($riders[$riderId])) {
        $riders[$riderId] = [
            'name' => $bid['rider_name'],
            'race_number' => $bid['race_number'],
            'highest_bid' => (int)$bid['amount'],
            'bids' => [],
        ];
    }

    if ($event['bids_resolved_at'] !== null) {
        $bid['winner'] = (bool)$bid['won'];
    } else {
        $bid['winner'] =
            (int)$bid['amount'] ===
            $riders[$riderId]['highest_bid'];
    }

    $riders[$riderId]['bids'][] = $bid;
}

$data['riders'] = array_values($riders);

$data['csrfToken'] = Csrf::token();

$data['resolved'] =
    $event !== null
    && $event['bids_resolved_at'] !== null;

$data['can_resolve'] =
    $event !== null
    && !(bool)$event['bids_open']
    && $event['bids_resolved_at'] === null
    && !empty($bids);

$data['payout'] = null;

$data['settled'] =
    $event !== null
    && $event['payouts_settled_at'] !== null;

$data['results_complete'] =
    $data['resolved']
    && $bidModel->resultsCompleteForPayout($eventId);

$data['can_settle'] =
    $data['resolved']
    && !$data['settled']
    && $data['results_complete'];

if ($data['resolved']) {
    $data['payout'] =
        $bidModel->calculatePayouts($eventId);

    $data['can_settle'] =
        $data['can_settle']
        && !empty($data['payout']['payouts']);
}

$tpl = new Template($config['template'], $logger);

echo $tpl->render('admin/bids', $data);
