<?php

use Webmin\Session;
use MotoGp\Event;
use MotoGp\Player;
use MotoGp\Bid;
use MotoGp\Utility;

$app = require __DIR__ . '/../src/bootstrap.php';

$playerModel = new Player($app->db, $app->logger);
$eventModel = new Event($app->db);
$session = new Session();

$data['user'] = $session->getUser();

$nextEvent = $eventModel->getNextEvent();

if ($nextEvent !== null) {

    $daysToGo = $nextEvent
        ? Utility::daysToGo($nextEvent['start_date'])
        : 'N/A';

    $nextEvent['start_date'] = Utility::formatDate(
        $nextEvent['start_date'],
        'M d'
    );

    $nextEvent['bidding_open'] = (bool)$nextEvent['bids_open'];
}

if ($session->isLoggedIn()) {
    $data['next_event'] = $nextEvent;

    $data['user']['balance'] = $playerModel->getBalance(
        (int)$data['user']['user_id']
    );

    $data['ladder'] = $playerModel->getLadder();

    $position = 1;

    foreach ($data['ladder'] as &$player) {
        $player['position'] = $position++;
        $player['current_user'] =
            (int)$player['user_id'] === (int)$data['user']['user_id'];

        $player['row_class'] = $player['current_user']
            ? 'motogp-highlight'
            : '';
    }

    unset($player);

    $bidModel = new Bid($app->db);

    $data['latest_winning_bids'] =
        $bidModel->getLatestResolvedWinningBids();
} else {
    $data['page'] = [
        'days_to_go' => $daysToGo,
        'next_race_name' => $nextEvent
            ? $nextEvent['name']
            : 'N/A',
    ];
}

echo $app->template->render('main', $data);
