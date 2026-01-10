<?php

use Webmin\Template;

$tpl = new Template($config['template']);

$data= [
  'app' => $config['app'],
  'title' => 'MotoGP',
  'heading' => 'MotoGP Bidding',
];

echo $tpl->render('main', $data);


