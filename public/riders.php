<?php

use Webmin\Template;
use Webmin\Database;
use Webmin\Session;
use MotoGp\Rider;

$app = require __DIR__ . '/../src/bootstrap.php';

$config = $app->config;
$logger = $app->logger;

$db = new Database($config['database']['dsn'], $logger);
$session = new Session();
$riderModel = new Rider($db, $logger);

$data['riders'] = $riderModel->getRiders();

foreach ($data['riders'] as &$rider) {
    $rider['cell-class'] = $rider['active'] ? '' : 'motogp-inactive';
}
unset($rider);

$data['app'] = $config['app'];
$data['user'] = $session->getUser();

$tpl = new Template($config['template'], $logger);

echo $tpl->render('riders', $data);
