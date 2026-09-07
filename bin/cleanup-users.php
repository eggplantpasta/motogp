#!/usr/bin/env php
<?php

require_once __DIR__ . '/../src/bootstrap.php';

use Webmin\Database;
use Webmin\User;

$db = new Database($config['database']['dsn']);
$user = new User($db);

$expiryDays = (int)($config['app']['pending_user_expiry_days'] ?? 7);

$count = $user->deleteExpiredPendingUsers($expiryDays);

echo "Deleted {$count} expired pending user(s).\n";
