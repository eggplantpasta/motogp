<?php

use Webmin\Template;
use Webmin\User;
use Webmin\Database;
use MotoGp\Utility;
use MotoGp\Event;

$tpl = new Template($config['template']);

// redirect to home page if user not logged in or not an admin
$user = new User();
if (!$user->isAdmin()) {
    header("Location: /");
    exit();
}

$db = new Database($config['database']['dsn']);
$event = new Event($db);

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
    $formData = [
        'start_date' => trim($_POST['start_date'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'circuit' => trim($_POST['circuit'] ?? ''),
        'flag' => trim($_POST['flag'] ?? ''),
        'link' => trim($_POST['link'] ?? ''),
        'bids_open' => isset($_POST['bids_open']) ? 1 : 0,
    ];

    // Basic validation
    $errors = [];
    if (empty($formData['name'])) {
        $errors['name'] = 'Event name is required';
    }
    if (empty($formData['start_date'])) {
        $errors['start_date'] = 'Start date is required';
    }

    // Update form data for template
    $data['form'] = array_merge($data['form'], $formData);
    $data['form']['errors'] = $errors;

    // If no errors, update the event
    if (empty($errors)) {
        if ($event->updateEvent((int)$eventId, $formData)) {
            $data['form']['errors']['general'] = 'Event updated successfully';
        } else {
            $data['form']['errors']['general'] = 'Failed to update event';
        }
    }
} else {
    // Pre-populate form with existing data
    $data['form']['start_date'] = $eventData['start_date'];
    $data['form']['name'] = $eventData['name'];
    $data['form']['circuit'] = $eventData['circuit'];
    $data['form']['flag'] = $eventData['flag'];
    $data['form']['link'] = $eventData['link'];
    $data['form']['bids_open'] = $eventData['bids_open'];
}

$data['app'] = $config['app'];
$data['page']['title'] = 'Edit Event';
$data['page']['heading'] = 'Edit Event';

echo $tpl->render('admin/edit-event', $data);
