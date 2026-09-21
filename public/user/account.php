<?php

use Webmin\Template;
use Webmin\User;
use Webmin\Session;
use Webmin\Database;
use MotoGp\Utility;
use MotoGp\Event;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

// redirect to login page if not logged in
$db = new Database($config['database']['dsn'], $logger);
$user = new User($db, $logger);
$session = new Session();

if (!$session->isLoggedIn()) {
    header("Location: /user/login.php");
    exit();
}

$event = new Event($db);

$next_event = $event->getEventById($event->getNextEventId());

$tpl = new Template($config['template'], $logger);
$data['user'] = $session->getUser();
$data['user']['balance'] = $user->getBalance(
    (int)$data['user']['user_id']
);
$data['user']['created_ago'] = Utility::timeAgo($data['user']['created_at']);
$data['next_event'] = $next_event;

$data['next_event']['start_date'] =
    Utility::formatDate(
        $data['next_event']['start_date'],
        'M d'
    );

$data['next_event']['bidding_open'] =
    (bool)$data['next_event']['bids_open'];

echo $tpl->render('user/account', $data);
