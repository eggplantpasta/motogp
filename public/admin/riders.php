<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use Webmin\Csrf;
use MotoGp\Riders;
use MotoGp\Team;

$logger = $GLOBALS['logger'] ?? null;

$db = new Database($config['database']['dsn'], $logger);
$user = new User($db, $logger);

if (!$user->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$user->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$riders = new Riders($db, $logger);
$teams = new Team($db, $logger);

function clearFormData(&$data) {
    $data['form']['message'] = '';
    $data['form']['message-class'] = '';
    $data['form']['rider_id'] = '';
    $data['form']['rider_name'] = '';
    $data['form']['team_id'] = '';
    $data['form']['rider_active'] = 0;
    $data['form']['open_modal'] = false;
}

function withSelectedTeam(array $teams, string $selectedTeamId): array {
    foreach ($teams as &$team) {
        $team['selected'] = (string)($team['team_id'] ?? '') === $selectedTeamId;
    }
    unset($team);

    return $teams;
}

$data['form'] = [
	'errors' => [],
	'message' => '',
	'message-class' => '',
	'rider_id' => '',
	'rider_name' => '',
	'team_id' => '',
	'rider_active' => 0,
	'open_modal' => false,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

	$riderId = trim($_POST['rider-id'] ?? '');
    $operation = $_POST['operation'] ?? null;
	$formData = [
		'name' => trim($_POST['rider-name'] ?? ''),
		'team_id' => trim($_POST['rider-team'] ?? ''),
		'active' => isset($_POST['rider-active']) ? 1 : 0,
	];

	$data['form']['rider_id'] = $riderId;
	$data['form']['rider_name'] = $formData['name'];
	$data['form']['team_id'] = $formData['team_id'];
	$data['form']['rider_active'] = $formData['active'];

    if ($operation !== 'delete') {
        // For insert and update, validate the name and team fields
        if ($formData['name'] === '') {
            $data['form']['errors']['rider_name'] = 'Rider name is required';
        }
    }

	if (empty($data['form']['errors'])) {
		try {
            if ($operation === 'create') {
                // Insert logic
                $createdRows = $riders->createRider($formData);
                if ($createdRows > 0) {
                    header('Location: /admin/riders.php');
                    exit();
                } else {
                    $data['form']['message'] = 'Failed to create rider';
                    $data['form']['message-class'] = 'error';
                    $data['form']['open_modal'] = true;
                }
            } elseif ($operation === 'update') {
                if ($riderId !== '' && ctype_digit($riderId)) {
                    $updatedRows = $riders->updateRider((int)$riderId, $formData);
                    if ($updatedRows > 0) {
                        header('Location: /admin/riders.php');
                        exit();
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
                        header('Location: /admin/riders.php');
                        exit();
                    } else {
                        $data['form']['message'] = 'No rider was deleted';
                        $data['form']['message-class'] = 'error';
                        $data['form']['open_modal'] = true;
                    }
                }
            }
            } catch (\Throwable $e) {
                throw $e;
            }
        // } catch (\Throwable $e) {
        //     $data['form']['message'] = 'Unable to save rider changes';
        //     $data['form']['message-class'] = 'error';
        //     $data['form']['open_modal'] = true;
        // }
	} else {
		$data['form']['message'] = 'Please fix the highlighted fields';
		$data['form']['message-class'] = 'error';
		$data['form']['open_modal'] = true;
	}
}

$results = $riders->getRiders();
$allTeams = $teams->getTeams();

$data['riders'] = $results;
foreach ($data['riders'] as &$rider) {
    $rider['cell-class'] = $rider['active'] ? '' : 'motogp-inactive';

    $rider['can_delete'] =
        !$riders->hasBids((int)$rider['rider_id']) &&
        !$riders->hasResults((int)$rider['rider_id']);
}
unset($rider);

$data['teams'] = withSelectedTeam($allTeams, (string)$data['form']['team_id']);


$tpl = new Template($config['template'], $logger);

$data['app'] = $config['app'];
$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Riders';
$data['page']['heading'] = 'Season ' . $config['app']['season'] . ' Riders';
$data['csrfToken'] = Csrf::token();

echo $tpl->render('admin/riders', $data);
