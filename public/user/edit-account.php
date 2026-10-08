<?php

use Webmin\User;
use Webmin\Session;
use Webmin\Csrf;

$app = require_once __DIR__ . '/../../src/bootstrap.php';

// redirect to login page if not logged in
$session = new Session();
if (!$session->isLoggedIn()) {
    header('Location: /user/login.php');
    exit();
}

$data['form']['action'] = htmlspecialchars($_SERVER["PHP_SELF"]);
$data['user'] = $session->getUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }

    $user = new User($app->db, $app->logger);

    $sessionUser = $session->getUser();
    $userId = $sessionUser['user_id'];

    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    $valid = true;

    if ($username !== '') {
        $user->username = $username;
        $valid = $user->validateUsername($userId);
    }

    if ($email !== '') {
        $user->email = $email;
        $valid = $user->validateEmail($userId) && $valid;
    }

    if ($password !== '') {
        $user->password = $password;
        $valid = $user->validatePassword() && $valid;

        if ($password !== $passwordConfirm) {
            $data['form']['passwordConfirmErr'] = 'Passwords do not match.';
            $valid = false;
        }
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
    $data['form']['passwordConfirmInvalid'] =
        !empty($data['form']['passwordConfirmErr']) ? 'true' : 'false';

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

            header('Location: /user/account.php');
            exit();
        }

        // updateAccount() may have found a DB uniqueness failure
        $data['form']['usernameErr'] = $user->usernameErr;
        $data['form']['usernameInvalid'] =
            !empty($user->usernameErr) ? 'true' : 'false';

        $data['form']['emailErr'] = $user->emailErr;
        $data['form']['emailInvalid'] =
            !empty($user->emailErr) ? 'true' : 'false';

        // Set the account error message if any
        $data['form']['accountErr'] = $user->accountErr;
    }
}

$data['form']['csrfToken'] = Csrf::token();
echo $app->template->render('user/edit-account', $data);
