<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use MotoGp\Event;
use MotoGp\Utility;
use MotoGp\Bid;

$user = new User();

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$user->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
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
$bidModel = new Bid($db);

$event = $eventModel->getEventById($eventId);

if ($event === null) {
    http_response_code(404);
    exit('Event not found.');
}

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();

$data['event'] = $event;
$data['event']['display_date'] =
    Utility::formatDate($event['start_date'], 'M d');

$data['page']['title'] = 'Bid Resolution';
$data['page']['heading'] = 'Bid Resolution';

$bids = $bidModel->getEventBids($eventId);

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

    $bid['winner'] =
        (int)$bid['amount'] ===
        $riders[$riderId]['highest_bid'];

    $riders[$riderId]['bids'][] = $bid;
}

$data['riders'] = array_values($riders);

$tpl = new Template($config['template']);

echo $tpl->render('admin/bids', $data);
