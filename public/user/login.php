<?php

use Webmin\User;
use Webmin\Session;
use Webmin\Csrf;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

// redirect to account page if already logged in
$session = new Session();
if ($session->isLoggedIn()) {
    header('Location: /user/account.php');
    exit();
}

$tpl = $app->template;

$data['form']['action'] = htmlspecialchars($_SERVER["PHP_SELF"]);
$data['form']['csrfToken'] = Csrf::token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $user = new User($app->db, $app->logger);

    // Process form submission
    $user->username = trim($_POST['username'] ?? '');
    $user->password = $_POST['password'] ?? '';

    // Validate inputs
    $user->validateLogin();

    // return form data and errors to template
    $data['form']['username'] = $user->username;
    $data['form']['usernameErr'] = $user->usernameErr;
    $data['form']['usernameInvalid'] = !empty($user->usernameErr) ? 'true' : 'false';
    $data['form']['passwordErr'] = $user->passwordErr;
    $data['form']['passwordInvalid'] = !empty($user->passwordErr) ? 'true' : 'false';

    // If no errors, proceed with login logic (e.g., check credentials)
    if (empty($user->usernameErr) && empty($user->passwordErr)) {
        if ($user->login()) {
            header('Location: /user/account.php');
            exit();
        }

        $data['form']['passwordErr'] = $user->loginErr;
        $data['form']['passwordInvalid'] = 'true';
    }
}

echo $tpl->render('user/login', $data);
