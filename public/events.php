<?php

use Webmin\Template;
use Webmin\Database;

// get the data from the db
$db = new Database($config['database']['dsn']);

// Define the SQL query to get the next event
$sql = '
SELECT
  event_id
FROM events
WHERE strftime("%Y-%m-%d %H:%M:%S", start_date) >= date("now")
ORDER BY start_date
LIMIT 1
';

// Execute the query and fetch the result
$results = $db->query($sql);
$next_event_id = !empty($results) ? $results[0]['event_id'] : null; // Get the first result or null if empty

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
  'heading' => 'Season',
  'results' => $results,
];

echo $tpl->render('events', $data);


