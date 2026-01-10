<?php

use Webmin\Template;
use Webmin\User;
use Webmin\Database;
use MotoGp\Utility;

$tpl = new Template($config['template']);

// redirect to login page if not logged in
$user = new User();
if (!$user->isLoggedIn()) {
    header("Location: /user/login.php");
    exit();
}

$data['form']['action'] = htmlspecialchars($_SERVER["PHP_SELF"]);
$data['user'] = $user->getSessionUser();

echo Utility::dump($data);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $db = new Database($config['database']['dsn']);
    $user = new User($db);

     // Process form submission
    $user->username = trim($_POST['username'] ?? '');
    $user->email = trim($_POST['email'] ?? '');
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

    // If no errors, proceed with password reset logic (e.g., update in database)
    if (empty($user->passwordErr)) {
        $userData = $user->getSessionUser();
        $user->updatePassword($userData['user_id'], $user->password);

        // Redirect to account page after successful password reset
        header("Location: /user/account.php");
        exit();
    }
}

echo $tpl->render('user/edit-account', $data);
