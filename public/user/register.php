<?php

use Webmin\Template;
use Webmin\User;
use Webmin\Database;
use Webmin\Csrf;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

// redirect to account page if already logged in
$user = new User();
if ($user->isLoggedIn()) {
    header("Location: /user/account.php");
    exit();
}

$tpl = new Template($config['template']);

$data['form']['action'] = htmlspecialchars($_SERVER["PHP_SELF"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $db = new Database($config['database']['dsn'], $logger);
    $user = new User($db);

    // Process form submission
    $user->username = trim($_POST['username'] ?? '');
    $user->email = strtolower(trim($_POST['email'] ?? ''));
    $user->password = trim($_POST['password'] ?? '');

    // Validate inputs
    $user->validateUsername();
    $user->validateEmail();
    $user->validatePassword();

    // return form data and errors to template
    $data['form']['username'] = $user->username;
    $data['form']['usernameErr'] = $user->usernameErr;
    $data['form']['usernameInvalid'] = !empty($user->usernameErr) ? 'true' : 'false';
    $data['form']['email'] = $user->email;
    $data['form']['emailErr'] = $user->emailErr;
    $data['form']['emailInvalid'] = !empty($user->emailErr) ? 'true' : 'false';
    $data['form']['password'] = $user->password;
    $data['form']['passwordErr'] = $user->passwordErr;
    $data['form']['passwordInvalid'] = !empty($user->passwordErr) ? 'true' : 'false';

    // If no errors, proceed with registration logic (e.g., save to database)
    if (empty($user->usernameErr) && empty($user->emailErr) && empty($user->passwordErr)) {
        if ($user->register()) {
            header("Location: /user/login.php");
            exit();
        }

        $data['form']['usernameErr'] = $user->usernameErr;
        $data['form']['usernameInvalid'] = !empty($user->usernameErr) ? 'true' : 'false';
        $data['form']['emailErr'] = $user->emailErr;
        $data['form']['emailInvalid'] = !empty($user->emailErr) ? 'true' : 'false';
    }
}

$data['form']['csrfToken'] = Csrf::token();
echo $tpl->render('user/register', $data);
