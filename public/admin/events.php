<?php

use Webmin\Session;
use Webmin\Csrf;
use MotoGp\Event;
use MotoGp\Bid;
use MotoGp\Admin;
use MotoGp\Country;
use MotoGp\Utility;

$app = require __DIR__ . '/../../src/bootstrap.php';

$session = new Session();

if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$session->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$eventModel = new Event($app->db);
$bidModel = new Bid($app->db);
$adminModel = new Admin($app->db);
$countryModel = new Country($app->db);

function normalizeDate(?string $dateValue): string
{
    if (empty($dateValue)) {
        return '';
    }

    $date = \DateTime::createFromFormat('Y-m-d', $dateValue)
        ?: \DateTime::createFromFormat('Y-m-d H:i:s', $dateValue);

    return $date ? $date->format('Y-m-d') : '';
}

$data['user'] = $session->getUser();

$data['csrfToken'] = Csrf::token();

$data['form'] = [
    'message' => '',
    'message-class' => '',
    'open_modal' => false,
    'event_id' => '',
    'start_date' => '',
    'name' => '',
    'circuit' => '',
    'country_code' => '',
    'bids_open' => 1,
    'errors' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $operation = $_POST['operation'] ?? '';
    $eventId = trim($_POST['event-id'] ?? '');

    if (in_array($operation, ['open-bidding', 'close-bidding'], true)) {
        $event = ctype_digit($eventId)
            ? $eventModel->getEventById((int)$eventId)
            : null;

        $today = date('Y-m-d');

        $canOpen = $event !== null
            && $event['start_date'] > $today
            && !(bool)$event['bids_open']
            && $event['cancelled_at'] === null
            && $event['bids_resolved_at'] === null;

        $canClose = $event !== null
            && $event['start_date'] <= $today
            && (bool)$event['bids_open']
            && $event['cancelled_at'] === null
            && $event['bids_resolved_at'] === null;

        $allowed = match ($operation) {
            'open-bidding' => $canOpen,
            'close-bidding' => $canClose
        };

        if (
            $allowed
            && $eventModel->setBiddingOpen(
                (int)$eventId,
                $operation === 'open-bidding'
            )
        ) {
            header('Location: /admin/events.php');
            exit();
        }

        $data['form']['message'] = 'Unable to change bidding status.';
        $data['form']['message-class'] = 'error';

    } elseif ($operation === 'cancel') {
        if ($eventId !== '' && ctype_digit($eventId)) {
            try {
                $app->db->beginTransaction();

                if (!$eventModel->cancelEvent((int)$eventId)) {
                    throw new \RuntimeException('Race cannot be cancelled.');
                }

                $bidModel->refundWinningBids((int)$eventId);

                $app->db->commit();

                header('Location: /admin/events.php');
                exit();
            } catch (\Throwable $e) {
                $app->db->rollBack();

                $app->logger->error(
                    'Race cancellation failed.',
                    [
                        'event_id' => $eventId,
                        'exception' => $e,
                    ]
                );
            }
        }

        $data['form']['message'] = 'Unable to cancel race.';
        $data['form']['message-class'] = 'error';

    } elseif ($operation === 'delete') {
        if (
            $eventId !== '' &&
            ctype_digit($eventId) &&
            $eventModel->deleteEvent((int)$eventId)
        ) {
            header('Location: /admin/events.php');
            exit();
        }

        $data['form']['message'] =
            'Race could not be deleted. It may already have bids or results.';

        $data['form']['message-class'] = 'error';

    } else {
        $startDateInput = trim($_POST['start-date'] ?? '');

        $formData = [
            'start_date' => normalizeDate($startDateInput),
            'name' => trim($_POST['event-name'] ?? ''),
            'circuit' => trim($_POST['circuit'] ?? ''),
            'country_code' => trim($_POST['country-code'] ?? ''),
            'bids_open' => isset($_POST['bids-open']) ? 1 : 0,
        ];

        $data['form']['event_id'] = $eventId;
        $data['form']['start_date'] = $formData['start_date'];
        $data['form']['name'] = $formData['name'];
        $data['form']['circuit'] = $formData['circuit'];
        $data['form']['country_code'] = $formData['country_code'];
        $data['form']['bids_open'] = $formData['bids_open'];

        if ($formData['name'] === '') {
            $data['form']['errors']['name'] = 'Race name is required.';
        }

        if (
            $startDateInput !== '' &&
            $formData['start_date'] === ''
        ) {
            $data['form']['errors']['start_date'] = 'Start date is invalid.';
        }

        if ($startDateInput === '') {
            $data['form']['errors']['start_date'] = 'Start date is required.';
        }

        if (
            $startDateInput !== '' &&
            (
                $startDateInput < $app->config['app']['season'] . '-01-01' ||
                $startDateInput > $app->config['app']['season'] . '-12-31'
            )
        ) {
            $data['form']['errors']['start_date'] =
                'Start date must be within the season year.';
        }

        if (
            $formData['bids_open'] &&
            $formData['start_date'] !== '' &&
            $formData['start_date'] < date('Y-m-d')
        ) {
            $data['form']['errors']['bids_open'] =
                'Bidding can only be opened for races in the future.';
        }

        if (empty($data['form']['errors'])) {
            if ($operation === 'create') {
                if ($eventModel->createEvent($formData) !== null) {
                    header('Location: /admin/events.php');
                    exit();
                }

                $data['form']['message'] = 'Unable to create race.';
                $data['form']['message-class'] = 'error';
            }

            if (
                $operation === 'update' &&
                $eventId !== '' &&
                ctype_digit($eventId)
            ) {
                if ($eventModel->updateEvent((int)$eventId, $formData)) {
                    header('Location: /admin/events.php');
                    exit();
                }

                $data['form']['message'] = 'Unable to update race.';
                $data['form']['message-class'] = 'error';
            }
        }

        $data['form']['open_modal'] = true;
    }
}

$data['events'] = $eventModel->getEvents();
$adminStates = $adminModel->getEventAdminStates();

foreach ($data['events'] as &$event) {
    $event['admin_state'] =
        $adminStates[(int)$event['event_id']];

    $event['start_date'] = normalizeDate(
        $event['start_date']
    );

    $event['display_date'] = Utility::formatDate(
        $event['start_date'],
        'M d'
    );

    $event['can_cancel'] =
        $event['cancelled_at'] === null
        && $event['payouts_settled_at'] === null;

    $event['has_bids'] =
        $eventModel->hasBids((int)$event['event_id']);

    $event['has_history'] =
        $event['has_bids']
        || $eventModel->hasResults((int)$event['event_id']);

    $event['admin_state_display'] = match ($event['admin_state']) {
        'open' => 'open',
        'closed' => 'closed',
        'ready_to_resolve' => 'resolve bids',
        'results_pending' => 'enter results',
        'ready_to_settle' => 'settle payouts',
        'complete' => 'complete',
        'cancelled' => 'cancelled',
        default => 'unknown',
    };

    $event['ready_to_resolve'] =
    $event['admin_state'] === 'ready_to_resolve';

    $event['results_pending'] =
        $event['admin_state'] === 'results_pending';

    $event['ready_to_settle'] =
        $event['admin_state'] === 'ready_to_settle';


    $event['can_open_bidding'] =
    $event['admin_state'] === 'closed'
    && $event['start_date'] > date('Y-m-d')
    && $event['cancelled_at'] === null
    && $event['bids_resolved_at'] === null;

    $event['can_close_bidding'] =
        $event['admin_state'] === 'open'
        && $event['start_date'] <= date('Y-m-d');

    $event['state_is_action'] =
        $event['ready_to_resolve']
        || $event['results_pending']
        || $event['ready_to_settle']
        || $event['can_open_bidding']
        || $event['can_close_bidding'];

}

unset($event);

$data['countries'] = $countryModel->getCountriesSelected(
    $data['form']['country_code']
);

echo $app->template->render('admin/events', $data);
