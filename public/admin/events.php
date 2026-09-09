<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use Webmin\Csrf;
use MotoGp\Event;
use MotoGp\Country;
use MotoGp\Utility;

$user = new User();

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$user->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$db = new Database($config['database']['dsn']);
$events = new Event($db);
$country = new Country($db);

function normalizeDate(?string $dateValue): string
{
    if (empty($dateValue)) {
        return '';
    }

    $date = \DateTime::createFromFormat('Y-m-d', $dateValue)
        ?: \DateTime::createFromFormat('Y-m-d H:i:s', $dateValue);

    return $date ? $date->format('Y-m-d') : '';
}

$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Events';
$data['page']['heading'] = 'Manage Events';
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
    'bids_open' => 0,
    'errors' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $operation = $_POST['operation'] ?? '';
    $eventId = trim($_POST['event-id'] ?? '');

    if ($operation === 'delete') {
        if (
            $eventId !== '' &&
            ctype_digit($eventId) &&
            $events->deleteEvent((int)$eventId)
        ) {
            header('Location: /admin/events.php');
            exit();
        }

        $data['form']['message'] =
            'Event could not be deleted. It may already have bids or results.';

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
            $data['form']['errors']['name'] = 'Event name is required.';
        }

        if ($startDateInput === '') {
            $data['form']['errors']['start_date'] = 'Start date is required.';
        }

        if (
            $startDateInput !== '' &&
            (
                $startDateInput < $config['app']['season'] . '-01-01' ||
                $startDateInput > $config['app']['season'] . '-12-31'
            )
        ) {
            $data['form']['errors']['start_date'] =
                'Start date must be within the season year.';
        }

        if (
            $formData['bids_open'] &&
            $formData['start_date'] < date('Y-m-d')
        ) {
            $data['form']['errors']['bids_open'] =
                'Bidding can only be opened for races in the future.';
        }

        if (empty($data['form']['errors'])) {
            if ($operation === 'create') {
                if ($events->createEvent($formData) !== null) {
                    header('Location: /admin/events.php');
                    exit();
                }

                $data['form']['message'] = 'Unable to create event.';
                $data['form']['message-class'] = 'error';
            }

            if (
                $operation === 'update' &&
                $eventId !== '' &&
                ctype_digit($eventId)
            ) {
                if ($events->updateEvent((int)$eventId, $formData)) {
                    header('Location: /admin/events.php');
                    exit();
                }

                $data['form']['message'] = 'Unable to update event.';
                $data['form']['message-class'] = 'error';
            }
        }

        $data['form']['open_modal'] = true;
    }
}

$data['events'] = $events->getEvents();

foreach ($data['events'] as &$eventData) {
    $eventData['display_date'] = Utility::formatDate(
        $eventData['start_date'],
        'M d'
    );

    $eventData['can_delete'] =
        !$events->hasBids((int)$eventData['event_id']) &&
        !$events->hasResults((int)$eventData['event_id']);
}

unset($eventData);

$data['countries'] = $country->getCountriesSelected(
    $data['form']['country_code']
);

$tpl = new Template($config['template']);

echo $tpl->render('admin/events', $data);