<?php

use Webmin\Session;
use Webmin\Csrf;
use MotoGp\Event;
use MotoGp\Rider;
use MotoGp\Bid;
use MotoGp\Player;
use MotoGp\Utility;

$app = require __DIR__ . '/../src/bootstrap.php';

$playerModel = new Player($app->db, $app->logger);

$session = new Session();

if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (
    !isset($_GET['event_id']) ||
    !ctype_digit($_GET['event_id'])
) {
    http_response_code(404);
    exit('Race not found.');
}

$eventId = (int)$_GET['event_id'];

$eventModel = new Event($app->db);
$riderModel = new Rider($app->db);
$bidModel = new Bid($app->db);

$event = $eventModel->getEventById($eventId);

if ($event === null) {
    http_response_code(404);
    exit('Race not found.');
}

if (!(bool)$event['bids_open']) {
    http_response_code(403);
    exit('Bidding is not open for this race.');
}

$sessionUser = $session->getUser();
$userId = (int)$sessionUser['user_id'];

$balance = $playerModel->getBalance($userId);

if ($balance === null) {
    http_response_code(403);
    exit('User account not found.');
}

$data['user'] = $sessionUser;
$data['user']['balance'] = $balance;
$data['event'] = $event;
$data['event']['display_date'] =
    Utility::formatDate($event['start_date'], 'M d');

$data['csrfToken'] = Csrf::token();
$data['message'] = '';

$bids = [
    1 => ['rider_id' => '', 'amount' => ''],
    2 => ['rider_id' => '', 'amount' => ''],
    3 => ['rider_id' => '', 'amount' => ''],
];

foreach ($bidModel->getUserBids($userId, $eventId) as $bid) {
    $bids[(int)$bid['bid_number']] = [
        'rider_id' => (string)$bid['rider_id'],
        'amount' => (int)$bid['amount'],
    ];
}

$riders = $riderModel->getActiveRiders();

$activeRiderIds = [];

foreach ($riders as $rider) {
    $activeRiderIds[(int)$rider['rider_id']] = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $errors = [];
    $selectedRiders = [];
    $totalBid = 0;

    foreach ($bids as $bidNumber => &$bid) {
        $riderId =
            trim($_POST['rider-' . $bidNumber] ?? '');

        $amount =
            trim($_POST['amount-' . $bidNumber] ?? '');

        $bid['rider_id'] = $riderId;
        $bid['amount'] = $amount;

        if (
            $riderId === '' ||
            !ctype_digit($riderId) ||
            !isset($activeRiderIds[(int)$riderId])
        ) {
            $errors[] = 'Please select a valid rider for bid ' . $bidNumber . '.';
        } else {
            $selectedRiders[] = (int)$riderId;
        }

        if ($amount === '' || !ctype_digit($amount)) {
            $errors[] =
                'Bid ' . $bidNumber .
                ' must be a whole number of zero or more.';
        } else {
            $bid['amount'] = (int)$amount;
            $totalBid += $bid['amount'];
        }
    }

    unset($bid);

    if (
        count($selectedRiders) === 3 &&
        count(array_unique($selectedRiders)) !== 3
    ) {
        $errors[] = 'You must select three different riders.';
    }

    if ($totalBid > $balance) {
        $errors[] =
            'Your bids total ' . $totalBid .
            ' points, but you only have ' .
            $balance .
            ' points available.';
    }

    if (!$errors) {
        if ($bidModel->saveUserBids(
            $userId,
            $eventId,
            $bids
        )) {
            header(
                'Location: /bids.php?event_id=' .
                $eventId .
                '&saved=1'
            );
            exit();
        }

        $errors[] = 'Unable to save bids.';
    }

    $data['has_errors'] = true;
    $data['errors'] = $errors;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['saved'])
) {
    $data['message'] = 'Bids saved.';
}

$currentBids = 0;

$data['bid_rows'] = [];

foreach ($bids as $bidNumber => $bid) {
    $options = [];

    foreach ($riders as $rider) {
        $rider['selected'] =
            (string)$rider['rider_id'] === $bid['rider_id'];

        $options[] = $rider;
    }

    if ($bid['amount'] !== '') {
        $currentBids += (int)$bid['amount'];
    }

    $data['bid_rows'][] = [
        'bid_number' => $bidNumber,
        'amount' => $bid['amount'],
        'riders' => $options,
    ];
}

$data['current_bids'] = $currentBids;

echo $app->template->render('bids', $data);
