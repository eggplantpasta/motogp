<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$appEnv = getenv('APP_ENV') ?: 'prod';
$rootDir = __DIR__ . '/..';
$configDir = $rootDir . '/config';

// Load configuration
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

require_once $rootDir . '/vendor/autoload.php';

// Replace {{ROOT_DIR}} placeholders in config
array_walk_recursive($config, function (&$value) use ($rootDir) {
    if (is_string($value)) {
        $value = str_replace('{{ROOT_DIR}}', $rootDir, $value);
    }
});

// Initialize Logger
$logPath = $config['log']['path'] ?? $rootDir . '/var/log/app.log';
$logLevel = constant('Monolog\Logger::' . strtoupper($config['log']['level'] ?? 'debug'));

$logger = new Logger('motogp');
$logger->pushHandler(new StreamHandler($logPath, $logLevel));
$GLOBALS['logger'] = $logger;
