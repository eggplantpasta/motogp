<?php

use Webmin\Template;
use Webmin\User;
use Webmin\Database;
use Webmin\Csrf;
use MotoGp\Country;
use MotoGp\Event;

$tpl = new Template($config['template']);

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
$event = new Event($db);
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

$data['form'] = [
    'action' => '/admin/create-event.php',
    'start_date' => '',
    'name' => '',
    'circuit' => '',
    'country_code' => '',
    'bids_open' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $startDateInput = trim($_POST['start_date'] ?? '');

    $formData = [
        'start_date' => normalizeDate($startDateInput),
        'name' => trim($_POST['name'] ?? ''),
        'circuit' => trim($_POST['circuit'] ?? ''),
        'country_code' => trim($_POST['country_code'] ?? ''),
        'bids_open' => isset($_POST['bids_open']) ? 1 : 0,
    ];

    $errors = [];

    if (empty($formData['name'])) {
        $errors['name'] = 'Event name is required';
    }

    if (empty($startDateInput)) {
        $errors['start_date'] = 'Start date is required';
    }

    if (
        $startDateInput &&
        ($startDateInput < $config['app']['season'] . '-01-01' ||
         $startDateInput > $config['app']['season'] . '-12-31')
    ) {
        $errors['start_date'] = 'Start date must be within the season year';
    }

    if (
        $formData['bids_open'] &&
        $formData['start_date'] < date('Y-m-d')
    ) {
        $errors['bids_open'] = 'Bidding can only be opened for races in the future';
    }

    $data['form'] = array_merge($data['form'], $formData);
    $data['form']['errors'] = $errors;

    if (empty($errors)) {
        $eventId = $event->createEvent($formData);

        if ($eventId !== null) {
            header(
                'Location: /admin/edit-event.php?event_id=' . $eventId
            );
            exit();
        }

        $data['form']['message'] = 'Failed to create event';
        $data['form']['message-class'] = 'error';
    }
}

$data['countries'] = $country->getCountriesSelected(
    $data['form']['country_code']
);

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Create Event';
$data['page']['heading'] = 'Create Event';
$data['csrfToken'] = Csrf::token();

echo $tpl->render('admin/create-event', $data);
