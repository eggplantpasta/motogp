<?php

use Webmin\Template;
use Webmin\User;
use Webmin\Database;
use MotoGp\Country;
use MotoGp\Event;
use MotoGp\Utility;

$tpl = new Template($config['template']);

// redirect to home page if user not logged in or not an admin
$user = new User();
if (!$user->isAdmin()) {
    header("Location: /");
    exit();
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

// Get event ID from URL parameter
$eventId = $_GET['event_id'] ?? null;
if (!$eventId || !is_numeric($eventId)) {
    header("Location: events.php");
    exit();
}

// Load event data
$eventData = $event->getEventById((int)$eventId);
if (!$eventData) {
    header("Location: events.php");
    exit();
}

$data['event'] = $eventData;
$data['form']['action'] = htmlspecialchars($_SERVER["PHP_SELF"]) . "?event_id=" . $eventId;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process form submission
    $startDateInput = trim($_POST['start_date'] ?? '');

    $formData = [
        'start_date' => normalizeDate($startDateInput),
        'name' => trim($_POST['name'] ?? ''),
        'circuit' => trim($_POST['circuit'] ?? ''),
        'country_code' => trim($_POST['country_code'] ?? ''),
        'bids_open' => isset($_POST['bids_open']) ? 1 : 0,
    ];

    // Basic validation
    $errors = [];
    if (empty($formData['name'])) {
        $errors['name'] = 'Event name is required';
    }
    if (empty($startDateInput)) {
        $errors['start_date'] = 'Start date is required';
    }
    // can only open bids for races in the future
    if ($formData['bids_open'] && ($formData['start_date'] < date('Y-m-d'))) {
        $errors['bids_open'] = 'Bidding can only be opened for races in the future';
    }

    // Update form data for template
    $data['form'] = array_merge($data['form'], $formData);
    $data['form']['errors'] = $errors;

    // If no errors, update the event
    if (empty($errors)) {
        if ($event->updateEvent((int)$eventId, $formData)) {
            $data['form']['message'] = 'Event updated successfully';
            $data['form']['message-class'] = 'success';
            // Refresh event data after update
            $data['event'] = $event->getEventById((int)$eventId);
        } else {
            $data['form']['message'] = 'Failed to update event';
            $data['form']['message-class'] = 'error';
        }
    }
} else {
    // Pre-populate form with existing data
    $data['form']['start_date'] = normalizeDate($eventData['start_date']);
    $data['form']['name'] = $eventData['name'];
    $data['form']['circuit'] = $eventData['circuit'];
    $data['form']['country_code'] = $eventData['country_code'];
    $data['form']['bids_open'] = $eventData['bids_open'];
}

$data['countries'] = $country->getCountriesSelected($data['form']['country_code'] ?? '');

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Edit Event';
$data['page']['heading'] = 'Edit Event';

echo $tpl->render('admin/edit-event', $data);
