<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use MotoGp\Riders;

// get session user
$logger = $GLOBALS['logger'] ?? null;

// get the riders from the db
$db = new Database($config['database']['dsn'], $logger);
$user = new User($db, $logger);
$riders = new Riders($db, $logger);

function clearFormData(&$data) {
    $data['form']['message'] = '';
    $data['form']['message-class'] = '';
    $data['form']['rider_id'] = '';
    $data['form']['rider_name'] = '';
    $data['form']['rider_team'] = '';
    $data['form']['rider_active'] = 0;
    $data['form']['open_modal'] = false;
}

$data['form'] = [
	'errors' => [],
	'message' => '',
	'message-class' => '',
	'rider_id' => '',
	'rider_name' => '',
	'rider_team' => '',
	'rider_active' => 0,
	'open_modal' => false,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!$user->isAdmin()) {
		header('Location: /riders.php');
		exit();
	}

	$riderId = trim($_POST['rider-id'] ?? '');
    $operation = $_POST['operation'] ?? null;
	$formData = [
		'name' => trim($_POST['rider-name'] ?? ''),
		'team' => trim($_POST['rider-team'] ?? ''),
		'active' => isset($_POST['rider-active']) ? 1 : 0,
	];

	$data['form']['rider_id'] = $riderId;
	$data['form']['rider_name'] = $formData['name'];
	$data['form']['rider_team'] = $formData['team'];
	$data['form']['rider_active'] = $formData['active'];

    if ($operation !== 'delete') {
        // For insert and update, validate the name and team fields
        if ($formData['name'] === '') {
            $data['form']['errors']['rider_name'] = 'Rider name is required';
        }

        if ($formData['team'] === '') {
            $data['form']['errors']['team'] = 'Team name is required';
        }
    }

	if (empty($data['form']['errors'])) {
		try {
            if ($operation === 'create') {
                // Insert logic
                $createdRows = $riders->createRider($formData);
                if ($createdRows > 0) {
                    clearFormData($data);
                    $data['page']['message'] = 'Rider created successfully';
                    $data['page']['message-class'] = 'success';
                    $data['page']['open_modal'] = true;
                } else {
                    $data['form']['message'] = 'Failed to create rider';
                    $data['form']['message-class'] = 'error';
                    $data['form']['open_modal'] = true;
                }
            } elseif ($operation === 'update') {
                if ($riderId !== '' && ctype_digit($riderId)) {
                    $updatedRows = $riders->updateRider((int)$riderId, $formData);
                    if ($updatedRows > 0) {
                        clearFormData($data);
                        $data['page']['message'] = 'Rider updated successfully';
                        $data['page']['message-class'] = 'success';
                        $data['page']['open_modal'] = true;
                    } else {
                        $data['form']['message'] = 'No rider was updated';
                        $data['form']['message-class'] = 'error';
                        $data['form']['open_modal'] = true;
                    }
                }
            } elseif ($operation === 'delete') {
                // Delete logic
                if ($riderId !== '' && ctype_digit($riderId)) {
                    $deletedRows = $riders->deleteRider((int)$riderId);
                    if ($deletedRows > 0) {
                        clearFormData($data);
                        $data['page']['message'] = 'Rider deleted successfully';
                        $data['page']['message-class'] = 'success';
                        $data['page']['open_modal'] = true;
                    } else {
                        $data['form']['message'] = 'No rider was deleted';
                        $data['form']['message-class'] = 'error';
                        $data['form']['open_modal'] = true;
                    }
                }
            }
        } catch (\Throwable $e) {
            $data['form']['message'] = 'Unable to save rider changes';
            $data['form']['message-class'] = 'error';
            $data['form']['open_modal'] = true;
        }
	} else {
		$data['form']['message'] = 'Please fix the highlighted fields';
		$data['form']['message-class'] = 'error';
		$data['form']['open_modal'] = true;
	}
}

$results = $riders->getRiders();

$data['riders'] = $results;
foreach ($data['riders'] as &$rider) {
    $rider['cell-class'] = $rider['active'] ? '' : 'motogp-inactive';
}


$tpl = new Template($config['template'], $logger);

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Riders';
$data['page']['heading'] = 'Season ' . $config['app']['season'] . ' Riders';

echo $tpl->render('riders', $data);


