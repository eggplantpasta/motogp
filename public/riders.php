<?php

use Webmin\Session;
use MotoGp\Rider;

$app = require __DIR__ . '/../src/bootstrap.php';

$session = new Session();
$riderModel = new Rider($app->db, $app->logger);

$data['riders'] = $riderModel->getRiders();

foreach ($data['riders'] as &$rider) {
    $rider['cell-class'] = $rider['active'] ? '' : 'motogp-inactive';
}
unset($rider);

$data['user'] = $session->getUser();

echo $app->template->render('riders', $data);
