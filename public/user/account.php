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

$tpl = new Template($config['template'], $logger);
$data['user'] = $session->getUser();
$data['user']['balance'] = $playerModel->getBalance(
    (int)$data['user']['user_id']
);
$data['user']['created_ago'] = Utility::timeAgo($data['user']['created_at']);

echo $tpl->render('user/account', $data);
