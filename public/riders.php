<?php

use Webmin\Template;
use Webmin\Database;

// get the riders from the db
$db = new Database($config['database']['dsn']);
$results = $db->query('SELECT name FROM riders');

$tpl = new Template($config['template']);

$data= [
  'app' => $config['app']['name'],
  'title' => 'Riders',
  'heading' => 'Riders',
  'riders' => $results,
];

echo $tpl->render('riders', $data);


