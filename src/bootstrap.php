<?php

$appEnv = getenv('APP_ENV') ?: 'prod';
$configDir = __DIR__ . '/../config';

$configCandidates = [
	$configDir . '/app.' . $appEnv . '.ini',
	$configDir . '/app.ini',
];

$config = false;
foreach ($configCandidates as $configPath) {
	if (!is_readable($configPath)) {
		continue;
	}

	$config = parse_ini_file($configPath, true);
	if ($config !== false) {
		$config['app']['env'] = $appEnv;
		break;
	}
}

if ($config === false) {
	throw new \RuntimeException('Unable to load configuration file.');
}

require_once __DIR__ . '/../vendor/autoload.php';


