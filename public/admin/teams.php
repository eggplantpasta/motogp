<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\Session;
use Webmin\Csrf;
use MotoGp\Team;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$session = new Session();

if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$session->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$db = new Database($config['database']['dsn'], $logger);
$teamModel = new Team($db);

$data['user'] = $session->getUser();
$data['page'] = [
    'title' => 'Teams',
    'heading' => 'Manage Teams',
];
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

    $validTeamId = $teamId !== '' && ctype_digit($teamId);

    if (
        in_array($operation, ['update', 'delete'], true) &&
        !$validTeamId
    ) {
        $data['form']['message'] = 'Invalid team.';
        $data['form']['message-class'] = 'error';
        $data['form']['open_modal'] = true;
    }

    if ($operation !== 'delete') {
        if ($formData['team_name'] === '') {
            $data['form']['errors']['team_name'] = 'Team name is required.';
        }

        if ($formData['short_team_name'] === '') {
            $data['form']['errors']['short_team_name'] = 'Short name is required.';
        }

        if ($formData['manufacturer'] === '') {
            $data['form']['errors']['manufacturer'] = 'Manufacturer is required.';
        }
    }

    if (
        empty($data['form']['errors']) &&
        (
            $operation === 'create' || $validTeamId
        )
    ) {
        try {
            if ($operation === 'create') {
                $teamModel->createTeam($formData);

                header('Location: /admin/teams.php');
                exit();
            } elseif ($operation === 'update') {
                $teamModel->updateTeam((int)$teamId, $formData);

                header('Location: /admin/teams.php');
                exit();
            } elseif ($operation === 'delete') {
                if ($teamModel->deleteTeam((int)$teamId)) {
                    header('Location: /admin/teams.php');
                    exit();
                }

                $data['form']['message'] =
                    'Team could not be deleted. It may still have riders.';
                $data['form']['message-class'] = 'error';
            }
        } catch (\Throwable $e) {
            $data['form']['message'] = 'Unable to save team changes.';
            $data['form']['message-class'] = 'error';
        }
    } elseif (!empty($data['form']['errors'])) {
        $data['form']['message'] = 'Please fix the highlighted fields.';
        $data['form']['message-class'] = 'error';
    }

    $data['form']['open_modal'] = true;
}

$data['teams'] = $teamModel->getTeams();

foreach ($data['teams'] as &$team) {
    $team['can_delete'] = !$teamModel->hasRiders((int)$team['team_id']);
}

unset($team);

$tpl = new Template($config['template'], $logger);
$data['app'] = $config['app'];
$data['user'] = $session->getUser();
$data['page'] = [
    'title' => 'Teams',
    'heading' => 'Season ' . $config['app']['season'] . ' Teams',
];
$data['csrfToken'] = Csrf::token();
echo $tpl->render('admin/teams', $data);
