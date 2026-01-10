<?php

use Webmin\Template;
use Webmin\User;
use Webmin\Database;

// redirect to account page if already logged in
$user = new User();
if ($user->isLoggedIn()) {
    header("Location: /user/account.php");
    exit();
}

$tpl = new Template($config['template']);

$data['form']['action'] = htmlspecialchars($_SERVER["PHP_SELF"]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $db = new Database($config['database']['dsn']);
    $user = new User($db);

    // Process form submission
    $user->username = trim($_POST['username'] ?? '');
    $user->password = trim($_POST['password'] ?? '');

    // Validate inputs
    $user->validateLogin();

    // return form data and errors to template
    $data['form']['username'] = $user->username;
    $data['form']['usernameErr'] = $user->usernameErr;
    $data['form']['usernameInvalid'] = !empty($user->usernameErr) ? 'true' : 'false';
    $data['form']['password'] = $user->password;
    $data['form']['passwordErr'] = $user->passwordErr;
    $data['form']['passwordInvalid'] = !empty($user->passwordErr) ? 'true' : 'false';

    // If no errors, proceed with login logic (e.g., check credentials)
    if (empty($user->usernameErr) && empty($user->passwordErr)) {
        if ($user->login()) {
            // Redirect after successful login
            header("Location: /user/account.php");
            exit();
        } else {
            $user->usernameErr = 'Invalid username or password.';
            $user->passwordErr = 'Invalid username or password.';
            $data['form']['usernameErr'] = "";
            $data['form']['usernameInvalid'] = 'true';
            $data['form']['passwordErr'] = $user->passwordErr;
            $data['form']['passwordInvalid'] = 'true';
        }
    }
}

echo $tpl->render('user/login', $data);
