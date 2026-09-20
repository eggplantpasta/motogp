<?php

use Webmin\Database;
use Webmin\Template;
use Webmin\User;

$app = require __DIR__ . '/../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$db = new Database($config['database']['dsn'], $logger);
$user = new User($db);

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

$sessionUser = $user->getSessionUser();
$userId = (int)$sessionUser['user_id'];

if (isset($_GET['user_id'])) {
    if (!$user->isAdmin()) {
        http_response_code(403);
        exit('Forbidden');
    }

    if (!ctype_digit($_GET['user_id'])) {
        http_response_code(400);
        exit('Invalid user.');
    }

    $userId = (int)$_GET['user_id'];
}

$account = $user->getUserById($userId);

if ($account === null) {
    http_response_code(404);
    exit('User not found.');
}

$transactions = $user->getBalanceTransactions($userId);

$runningBalance = 0;

foreach ($transactions as &$transaction) {
    $amount = (int)$transaction['amount'];

    $runningBalance += $amount;

    $transaction['amount_display'] =
        ($amount > 0 ? '+' : '') . $amount;

    $transaction['description'] =
        match ($transaction['transaction_type']) {
            'opening_balance' =>
                'Opening balance',

            'winning_bid' =>
                $transaction['event_name']
                . ' — #'
                . $transaction['race_number']
                . ' '
                . $transaction['rider_name']
                . ' winning bid',

            'payout' =>
                $transaction['event_name']
                . ' — #'
                . $transaction['race_number']
                . ' '
                . $transaction['rider_name']
                . ' payout',

            'admin_adjustment' =>
                'Admin adjustment',

            default =>
                $transaction['transaction_type'],
        };

    $transaction['running_balance'] = $runningBalance;
}
unset($transaction);

$data['user'] = $sessionUser;
$data['account'] = $account;
$data['transactions'] = $transactions;

$tpl = new Template($config['template']);

echo $tpl->render('statement', $data);
