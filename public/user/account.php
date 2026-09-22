<?php

use Webmin\Template;
use Webmin\Session;
use Webmin\Database;
use MotoGp\Utility;
use MotoGp\Event;
use MotoGp\Player;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

// redirect to login page if not logged in
$db = new Database($config['database']['dsn'], $logger);
$session = new Session();

if (!$session->isLoggedIn()) {
    header("Location: /user/login.php");
    exit();
}

$event = new Event($db);
$player = new Player($db, $logger);

$nextEventId = $event->getNextEventId();
$nextEvent = $nextEventId !== null
    ? $event->getEventById($nextEventId)
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

$tpl = new Template($config['template'], $logger);
$data['user'] = $session->getUser();
$data['user']['balance'] = $player->getBalance(
    (int)$data['user']['user_id']
);
$data['user']['created_ago'] = Utility::timeAgo($data['user']['created_at']);

$data['next_event']['bidding_open'] =
    (bool)$data['next_event']['bids_open'];

echo $tpl->render('user/account', $data);
