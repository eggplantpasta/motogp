<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use Webmin\Csrf;
use MotoGp\Event;
use MotoGp\Riders;
use MotoGp\Bid;
use MotoGp\Utility;

$user = new User();

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (
    !isset($_GET['event_id']) ||
    !ctype_digit($_GET['event_id'])
) {
    http_response_code(404);
    exit('Event not found.');
}

$eventId = (int)$_GET['event_id'];

$db = new Database($config['database']['dsn']);

$eventModel = new Event($db);
$riderModel = new Riders($db);
$bidModel = new Bid($db);

$event = $eventModel->getEventById($eventId);

if ($event === null) {
    http_response_code(404);
    exit('Event not found.');
}

if (!(bool)$event['bids_open']) {
    http_response_code(403);
    exit('Bidding is not open for this event.');
}

$sessionUser = $user->getSessionUser();
$userId = (int)$sessionUser['user_id'];

$data['app'] = $config['app'];
$data['user'] = $sessionUser;
$data['event'] = $event;
$data['event']['display_date'] =
    Utility::formatDate($event['start_date'], 'M d');

$data['page']['title'] = 'Bids';
$data['page']['heading'] = 'Place Bids';
$data['csrfToken'] = Csrf::token();
$data['message'] = '';

$bids = [
    1 => ['rider_id' => '', 'amount' => 0],
    2 => ['rider_id' => '', 'amount' => 0],
    3 => ['rider_id' => '', 'amount' => 0],
];

foreach ($bidModel->getUserBids($userId, $eventId) as $bid) {
    $bids[(int)$bid['bid_number']] = [
        'rider_id' => (string)$bid['rider_id'],
        'amount' => (int)$bid['amount'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    foreach ($bids as $bidNumber => &$bid) {
        $bid['rider_id'] =
            trim($_POST['rider-' . $bidNumber] ?? '');

        $amount =
            trim($_POST['amount-' . $bidNumber] ?? '0');

        $bid['amount'] =
            ctype_digit($amount) ? (int)$amount : 0;
    }
    unset($bid);

    if ($bidModel->saveUserBids($userId, $eventId, $bids)) {
        header(
            'Location: /bids.php?event_id=' . $eventId . '&saved=1'
        );
        exit();
    }

    $data['message'] = 'Unable to save bids.';
}

if (isset($_GET['saved'])) {
    $data['message'] = 'Bids saved.';
}

$riders = $riderModel->getActiveRiders();

$data['bid_rows'] = [];

foreach ($bids as $bidNumber => $bid) {
    $options = [];

    foreach ($riders as $rider) {
        $rider['selected'] =
            (string)$rider['rider_id'] === $bid['rider_id'];

        $options[] = $rider;
    }

    $data['bid_rows'][] = [
        'bid_number' => $bidNumber,
        'amount' => $bid['amount'],
        'riders' => $options,
    ];
}

$tpl = new Template($config['template']);

echo $tpl->render('bids', $data);