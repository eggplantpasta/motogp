<?php

use Webmin\Template;
use Webmin\User;
use Webmin\Database;

$tpl = new Template($config['template']);

// redirect to login page if not logged in
$user = new User();
if (!$user->isLoggedIn()) {
    header("Location: /user/login.php");
    exit();
}

$data['form']['action'] = htmlspecialchars($_SERVER["PHP_SELF"]);
$data['user'] = $user->getSessionUser();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $db = new Database($config['database']['dsn']);
    $user = new User($db);

    $sessionUser = $user->getSessionUser();
    $userId = $sessionUser['user_id'];

    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $valid = true;

    if ($username !== '') {
        $user->username = $username;
        $valid = $user->validateUsername($userId) && $valid;
    }

    if ($email !== '') {
        $user->email = $email;
        $valid = $user->validateEmail($userId) && $valid;
    }

    if ($password !== '') {
        $user->password = $password;
        $valid = $user->validatePassword() && $valid;
    }

    $data['form']['username'] = $username;
    $data['form']['usernameErr'] = $user->usernameErr;
    $data['form']['usernameInvalid'] =
        !empty($user->usernameErr) ? 'true' : 'false';

    $data['form']['email'] = $email;
    $data['form']['emailErr'] = $user->emailErr;
    $data['form']['emailInvalid'] =
        !empty($user->emailErr) ? 'true' : 'false';

    $data['form']['passwordErr'] = $user->passwordErr;
    $data['form']['passwordInvalid'] =
        !empty($user->passwordErr) ? 'true' : 'false';

    if ($valid) {
        if ($user->updateAccount(
            $userId,
            $username !== '' ? $username : null,
            $email !== '' ? $email : null,
            $password !== '' ? $password : null
        )) {
            // keep session copy in sync
            if ($username !== '') {
                $_SESSION['user']['username'] = $username;
            }

            if ($email !== '') {
                $_SESSION['user']['email'] = $email;
            }

            header("Location: /user/account.php");
            exit();
        }

        // updateAccount() may have found a DB uniqueness failure
        $data['form']['usernameErr'] = $user->usernameErr;
        $data['form']['usernameInvalid'] =
            !empty($user->usernameErr) ? 'true' : 'false';

        $data['form']['emailErr'] = $user->emailErr;
        $data['form']['emailInvalid'] =
            !empty($user->emailErr) ? 'true' : 'false';
    }
}

echo $tpl->render('user/edit-account', $data);
