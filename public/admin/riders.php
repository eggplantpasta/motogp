<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\Session;
use Webmin\Csrf;
use MotoGp\Rider;
use MotoGp\Team;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$db = new Database($config['database']['dsn'], $logger);
$session = new Session();

if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

if (!$session->isAdmin()) {
    http_response_code(403);
    exit('Forbidden');
}

$riderModel = new Rider($db, $logger);
$teamModel = new Team($db);

function withSelectedTeam(array $teams, string $selectedTeamId): array
{
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
    'race_number' => '',
    'name' => '',
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
        'race_number' => trim($_POST['rider-number'] ?? ''),
        'name' => trim($_POST['rider-name'] ?? ''),
        'team_id' => trim($_POST['rider-team'] ?? ''),
        'active' => isset($_POST['rider-active']) ? 1 : 0,
    ];

    $validRiderId = $riderId !== '' && ctype_digit($riderId);

    if (
        in_array($operation, ['update', 'delete'], true) &&
        !$validRiderId
    ) {
        $data['form']['message'] = 'Invalid rider.';
        $data['form']['message-class'] = 'error';
        $data['form']['open_modal'] = true;
    }

    $data['form']['rider_id'] = $riderId;
    $data['form']['race_number'] = $formData['race_number'];
    $data['form']['name'] = $formData['name'];
    $data['form']['team_id'] = $formData['team_id'];
    $data['form']['rider_active'] = $formData['active'];

    if ($operation !== 'delete') {
        if ($formData['name'] === '') {
            $data['form']['errors']['name'] = 'Rider name is required.';
        }
    }

    if (
        empty($data['form']['errors']) &&
        (
            $operation === 'create' ||
            $validRiderId
        )
    ) {
        if ($operation === 'create') {
            $createdRows = $riderModel->createRider($formData);
            if ($createdRows > 0) {
                header('Location: /admin/riders.php');
                exit();
            } else {
                $data['form']['message'] = 'Unable to create rider.';
                $data['form']['message-class'] = 'error';
                $data['form']['open_modal'] = true;
            }
        } elseif ($operation === 'update') {
            $updatedRows = $riderModel->updateRider((int)$riderId, $formData);
            if ($updatedRows > 0) {
                header('Location: /admin/riders.php');
                exit();
            } else {
                $data['form']['message'] = 'Unable to update rider.';
                $data['form']['message-class'] = 'error';
                $data['form']['open_modal'] = true;
            }
        } elseif ($operation === 'delete') {
            $deletedRows = $riderModel->deleteRider((int)$riderId);
            if ($deletedRows > 0) {
                header('Location: /admin/riders.php');
                exit();
            } else {
                $data['form']['message'] = 'Unable to delete rider.';
                $data['form']['message-class'] = 'error';
                $data['form']['open_modal'] = true;
            }
        }
    } else {
        $data['form']['message'] = 'Please fix the highlighted fields.';
        $data['form']['message-class'] = 'error';
        $data['form']['open_modal'] = true;
    }
}

$data['riders'] = $riderModel->getRiders();
$allTeams = $teamModel->getTeams();

foreach ($data['riders'] as &$rider) {
    $rider['cell-class'] = $rider['active'] ? '' : 'motogp-inactive';

    $rider['has_history'] =
        $riderModel->hasBids((int)$rider['rider_id'])
        || $riderModel->hasResults((int)$rider['rider_id']);
}
unset($rider);

$data['teams'] = withSelectedTeam($allTeams, (string)$data['form']['team_id']);

$tpl = new Template($config['template'], $logger);

$data['app'] = $config['app'];
$data['user'] = $session->getUser();

$data['csrfToken'] = Csrf::token();

echo $tpl->render('admin/riders', $data);
