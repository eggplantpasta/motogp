<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use MotoGp\Riders;

// get session user
$user = new User();

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

// get the riders from the db
$db = new Database($config['database']['dsn']);
$riders = new Riders($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (!$user->isAdmin()) {
		header('Location: /riders.php');
		exit();
	}

	$riderId = trim($_POST['rider-id'] ?? '');
	$formData = [
		'name' => trim($_POST['rider-name'] ?? ''),
		'team' => trim($_POST['rider-team'] ?? ''),
		'active' => isset($_POST['rider-active']) ? 1 : 0,
	];

	$data['form']['rider_id'] = $riderId;
	$data['form']['rider_name'] = $formData['name'];
	$data['form']['rider_team'] = $formData['team'];
	$data['form']['rider_active'] = $formData['active'];

	if ($formData['name'] === '') {
		$data['form']['errors']['rider_name'] = 'Rider name is required';
	}

	if ($formData['team'] === '') {
		$data['form']['errors']['team'] = 'Team name is required';
	}

	if (empty($data['form']['errors'])) {
		try {
			if ($riderId !== '' && ctype_digit($riderId)) {
				$updatedRows = $riders->updateRider((int)$riderId, $formData);
				if ($updatedRows > 0) {
					$data['form']['message'] = 'Rider updated successfully';
					$data['form']['message-class'] = 'success';
					$data['form']['rider_id'] = '';
					$data['form']['rider_name'] = '';
					$data['form']['rider_team'] = '';
					$data['form']['rider_active'] = 0;
					$data['form']['open_modal'] = false;
				} else {
					$data['form']['message'] = 'No rider was updated';
					$data['form']['message-class'] = 'error';
					$data['form']['open_modal'] = true;
				}
			} else {
				$createdRows = $riders->createRider($formData);
				if ($createdRows > 0) {
					$data['form']['message'] = 'Rider created successfully';
					$data['form']['message-class'] = 'success';
					$data['form']['rider_id'] = '';
					$data['form']['rider_name'] = '';
					$data['form']['rider_team'] = '';
					$data['form']['rider_active'] = 0;
					$data['form']['open_modal'] = false;
				} else {
					$data['form']['message'] = 'Failed to create rider';
					$data['form']['message-class'] = 'error';
					$data['form']['open_modal'] = true;
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

$tpl = new Template($config['template']);

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Riders';
$data['page']['heading'] = 'Season ' . $config['app']['season'] . ' Riders';
$data['riders'] = $results;

echo $tpl->render('riders', $data);


