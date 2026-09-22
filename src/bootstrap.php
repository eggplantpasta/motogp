<?php

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Webmin\App;

$rootDir = __DIR__ . '/..';

require_once $rootDir . '/vendor/autoload.php';

require_once $rootDir . '/vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$appEnv = getenv('APP_ENV') ?: 'prod';
$configDir = $rootDir . '/config';

$configCandidates = [
    $configDir . '/app.' . $appEnv . '.ini',
    $configDir . '/app.ini',
];

$config = null;

foreach ($configCandidates as $configPath) {
    if (!is_readable($configPath)) {
        continue;
    }

    $parsedConfig = parse_ini_file($configPath, true);

    if ($parsedConfig === false) {
        continue;
    }

    $config = $parsedConfig;
    $config['app']['env'] = $appEnv;
    break;
}

if ($config === null) {
    throw new RuntimeException('Unable to load configuration file.');
}

array_walk_recursive($config, function (&$value) use ($rootDir) {
    if (is_string($value)) {
        $value = str_replace('{{ROOT_DIR}}', $rootDir, $value);
    }
});

$logPath = $config['log']['path'] ?? $rootDir . '/var/log/app.log';
$logLevel = constant('Monolog\Logger::' . strtoupper($config['log']['level'] ?? 'debug'));
$logDays = max(1, (int) ($config['log']['days'] ?? 30));

$logDir = dirname($logPath);

if (!is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}

$logger = new Logger('motogp');
$logger->pushHandler(new RotatingFileHandler($logPath, $logDays, $logLevel));

return new App($config, $logger);
