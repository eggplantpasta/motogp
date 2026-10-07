<?php

use Webmin\Session;
use MotoGp\Utility;
use MotoGp\Event;
use MotoGp\Player;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

// redirect to login page if not logged in
$db = $app->db;
$tpl = $app->template;
$session = new Session();

if (!$session->isLoggedIn()) {
    header("Location: /user/login.php");
    exit();
}

$eventModel = new Event($db);
$playerModel = new Player($db, $logger);

$nextEventId = $eventModel->getNextEventId();
$nextEvent = $nextEventId !== null
    ? $eventModel->getEventById($nextEventId)
    : null;

if ($nextEvent !== null) {
    $nextEvent['start_date'] =
        Utility::formatDate(
            $nextEvent['start_date'],
            'M d'
        );

    $nextEvent['bidding_open'] =
        (bool)$nextEvent['bids_open'];
}

$data['next_event'] = $nextEvent;

$data['user'] = $session->getUser();
$data['user']['balance'] = $playerModel->getBalance(
    (int)$data['user']['user_id']
);
$data['user']['created_ago'] = Utility::timeAgo($data['user']['created_at']);

echo $tpl->render('user/account', $data);
