<?php

use Webmin\Template;
use Webmin\User;
use Webmin\Database;
use Webmin\Csrf;

// redirect to account page if already logged in
$user = new User();
if ($user->isLoggedIn()) {
    header("Location: /user/account.php");
    exit();
}

$tpl = new Template($config['template']);

$data['form']['action'] = htmlspecialchars($_SERVER["PHP_SELF"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $db = new Database($config['database']['dsn'], $logger);
    $user = new User($db);

    // Process form submission
    $user->username = trim($_POST['username'] ?? '');
    $user->email = strtolower(trim($_POST['email'] ?? ''));
    $user->password = trim($_POST['password'] ?? '');

    // Validate inputs
    $user->validateUsername();
    $user->validateEmail();
    $user->validatePassword();

    // return form data and errors to template
    $data['form']['username'] = $user->username;
    $data['form']['usernameErr'] = $user->usernameErr;
    $data['form']['usernameInvalid'] = !empty($user->usernameErr) ? 'true' : 'false';
    $data['form']['email'] = $user->email;
    $data['form']['emailErr'] = $user->emailErr;
    $data['form']['emailInvalid'] = !empty($user->emailErr) ? 'true' : 'false';
    $data['form']['password'] = $user->password;
    $data['form']['passwordErr'] = $user->passwordErr;
    $data['form']['passwordInvalid'] = !empty($user->passwordErr) ? 'true' : 'false';

    // If no errors, proceed with registration logic (e.g., save to database)
    if (empty($user->usernameErr) && empty($user->emailErr) && empty($user->passwordErr)) {
        if ($user->register()) {
            header("Location: /user/login.php");
            exit();
        }

        $data['form']['usernameErr'] = $user->usernameErr;
        $data['form']['usernameInvalid'] = !empty($user->usernameErr) ? 'true' : 'false';
        $data['form']['emailErr'] = $user->emailErr;
        $data['form']['emailInvalid'] = !empty($user->emailErr) ? 'true' : 'false';
    }
}

$data['form']['csrfToken'] = Csrf::token();
echo $tpl->render('user/register', $data);
             http_response_code(400);
                exit('Payouts have already been settled.');
            }

            if (!$bidModel->settlePayouts($eventId)) {
                http_response_code(500);
                exit('Unable to settle payouts.');
            }

            header(
                'Location: /admin/bids.php?event_id='
                . $eventId
                . '&settled=1'
            );
            exit();

        default:
            http_response_code(400);
            exit('Invalid action.');
    }
}

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();

$data['page']['title'] = 'Bid Resolution';
$data['page']['heading'] = 'Bid Resolution';

$data['event'] = $event;

if ($event !== null) {
    $data['event']['display_date'] =
        Utility::formatDate($event['start_date'], 'M d');
}

$data['events'] = $eventModel->getEvents();

foreach ($data['events'] as &$eventOption) {
    $eventOption['selected'] =
        (int)$eventOption['event_id'] === $eventId;
}
unset($eventOption);

$bids = $eventId !== null
    ? $bidModel->getEventBids($eventId)
    : [];

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

    if ($event['bids_resolved_at'] !== null) {
        $bid['winner'] = (bool)$bid['won'];
    } else {
        $bid['winner'] =
            (int)$bid['amount'] ===
            $riders[$riderId]['highest_bid'];
    }

    $riders[$riderId]['bids'][] = $bid;
}

$data['riders'] = array_values($riders);

$data['csrfToken'] = Csrf::token();

$data['resolved'] =
    $event !== null
    && $event['bids_resolved_at'] !== null;

$data['can_resolve'] =
    $event !== null
    && !(bool)$event['bids_open']
    && $event['bids_resolved_at'] === null
    && !empty($bids);

$data['payout'] = null;

$data['settled'] =
    $event !== null
    && $event['payouts_settled_at'] !== null;

$data['results_complete'] =
    $data['resolved']
    && $bidModel->resultsCompleteForPayout($eventId);

$data['can_settle'] =
    $data['resolved']
    && !$data['settled']
    && $data['results_complete'];

if ($data['resolved']) {
    $data['payout'] =
        $bidModel->calculatePayouts($eventId);

    $data['can_settle'] =
        $data['can_settle']
        && !empty($data['payout']['payouts']);
}

$tpl = new Template($config['template']);

echo $tpl->render('admin/bids', $data);
