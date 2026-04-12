<?php

use Webmin\Template;
use Webmin\User;
use Webmin\Database;
use MotoGp\Utility;
use MotoGp\Event;

// redirect to login page if not logged in
$user = new User();
if (!$user->isLoggedIn()) {
    header("Location: /user/login.php");
    exit();
}

$db = new Database($config['database']['dsn']);
$event = new Event($db);

$next_event = $event->getEventById($event->getNextEventId());

$tpl = new Template($config['template']);
$data['user'] = $user->getSessionUser();
$data['user']['created_ago'] = Utility::timeAgo($data['user']['created_at']);
$data['next_event'] = $next_event;
$data['next_event']['start_date'] = Utility::formatDate($data['next_event']['start_date'], 'M d');

echo $tpl->render('user/account', $data);
