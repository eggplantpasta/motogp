<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\User;
use Webmin\Csrf;
use MotoGp\Team;

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
$teams = new Team($db);

$data['user'] = $user->getSessionUser();
$data['page']['title'] = 'Teams';
$data['page']['heading'] = 'Manage Teams';
$data['csrfToken'] = Csrf::token();

$data['form'] = [
    'message' => '',
    'message-class' => '',
    'open_modal' => false,
    'team_id' => '',
    'team_name' => '',
    'short_team_name' => '',
    'manufacturer' => '',
    'errors' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $operation = $_POST['operation'] ?? '';
    $teamId = trim($_POST['team-id'] ?? '');

    $formData = [
        'team_name' => trim($_POST['team-name'] ?? ''),
        'short_team_name' => trim($_POST['short-team-name'] ?? ''),
        'manufacturer' => trim($_POST['manufacturer'] ?? ''),
    ];

    $data['form']['team_id'] = $teamId;
    $data['form']['team_name'] = $formData['team_name'];
    $data['form']['short_team_name'] = $formData['short_team_name'];
    $data['form']['manufacturer'] = $formData['manufacturer'];

    if ($operation === 'delete') {
        if ($teamId !== '' && ctype_digit($teamId) && $teams->deleteTeam((int)$teamId)) {
            header('Location: /admin/teams.php');
            exit();
        }

        $data['form']['message'] = 'Team could not be deleted. It may still have riders.';
        $data['form']['message-class'] = 'error';
    } else {
        if ($formData['team_name'] === '') {
            $data['form']['errors']['team_name'] = 'Team name is required.';
        }

        if ($formData['short_team_name'] === '') {
            $data['form']['errors']['short_team_name'] = 'Short name is required.';
        }

        if ($formData['manufacturer'] === '') {
            $data['form']['errors']['manufacturer'] = 'Manufacturer is required.';
        }
        if (empty($data['form']['errors'])) {
            try {
                if ($operation === 'create') {
                    $teams->createTeam($formData);

                    header('Location: /admin/teams.php');
                    exit();
                }

                if ($operation === 'update' && $teamId !== '' && ctype_digit($teamId)) {
                    $teams->updateTeam((int)$teamId, $formData);

                    header('Location: /admin/teams.php');
                    exit();
                }
            } catch (\Throwable $e) {
                $data['form']['message'] = 'Unable to save team changes.';
                $data['form']['message-class'] = 'error';
            }
        }

        $data['form']['open_modal'] = true;
    }
}

$data['teams'] = $teams->getTeams();

foreach ($data['teams'] as &$team) {
    $team['can_delete'] = !$teams->hasRiders((int)$team['team_id']);
}

unset($team);

$tpl = new Template($config['template']);

echo $tpl->render('admin/teams', $data);