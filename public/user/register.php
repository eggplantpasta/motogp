<?php

use Webmin\Template;

$tpl = new Template($config['template']);

$data= [
  'app' => $config['app']['name'],
  'title' => 'Register',
  'heading' => 'Register New Account'
];

echo $tpl->render('register', $data);