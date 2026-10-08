#!/usr/bin/env php
<?php

use MotoGp\Scheduler;

$app = require __DIR__ . '/../src/bootstrap.php';

$scheduler = new Scheduler(
    $app->db,
    $app->logger,
    $app->config
);

$scheduler->run();
