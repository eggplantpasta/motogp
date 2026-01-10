<?php

use Webmin\Template;
use Webmin\Database;
use MotoGp\Event;

// get the data from the db
$db = new Database($config['database']['dsn']);

$event = new Event($db);
$next_event_id = $event->nextEvent();

$sql = '
SELECT
    CASE
        WHEN event_id = :next_event_id THEN "row-highlight"
        WHEN strftime("%Y-%m-%d %H:%M:%S", start_date) < date("now") THEN "row-disable"
        ELSE ""
    END as "rowclass",
  event_id,
  name,
  circuit,
  substr("--JanFebMarAprMayJunJulAugSepOctNovDec", strftime ("%m", start_date) * 3, 3) || " " || strftime ("%d", start_date) as "date"
FROM events
ORDER BY start_date
';
$results = $db->query($sql, [':next_event_id' => $next_event_id]);

$tpl = new Template($config['template']);

$data= [
  'app' => $config['app'],
  'title' => 'Events',
  'heading' => 'Season 2026',
  'results' => $results,
];

echo $tpl->render('events', $data);


