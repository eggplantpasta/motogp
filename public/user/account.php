<?php

use Webmin\Template;
use Webmin\User;
use Webmin\Database;
use MotoGp\Utility;
use MotoGp\Event;

require_once __DIR__ . '/../../src/bootstrap.php';

// redirect to login page if not logged in
$db = new Database($config['database']['dsn'], $logger);
$user = new User($db);

if (!$user->isLoggedIn()) {
    header("Location: /user/login.php");
    exit();
}

$event = new Event($db);

$next_event = $event->getEventById($event->getNextEventId());

$tpl = new Template($config['template']);
$data['user'] = $user->getSessionUser();
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
