<?php

require_once 'bootstrap.php';

include 'partials/page_top.html';

use Webmin\Database;
use Webmin\HtmlRenderer;
?>

<h2>Events Schedule for 2025</h2>

<?php
try {
    // Initialize the Database class with the SQLite DSN
    $db = new Database(SQLITE_DSN);

    // Define the SQL query to get the next event
    $sql = '
    SELECT
      event_id
    FROM events
    WHERE strftime("%Y-%m-%d", start_date) >= date("now")
    ORDER BY start_date
    LIMIT 1
    ';

    // Execute the query and fetch the result
    $results = $db->query($sql);
    $next_event_id = !empty($results) ? $results[0]['event_id'] : null; // Get the first result or null if empty

    // Define the SQL query
    $sql = '
    SELECT
        CASE
            WHEN start_date < date("now") THEN "row-disable"
            WHEN event_id = :next_event_id THEN "row-highlight"
            ELSE ""
        END as "rowclass",
      event_id as "ID",
      name as "Name",
      substr("--JanFebMarAprMayJunJulAugSepOctNovDec", strftime ("%m", start_date) * 3, 3) || " " || strftime ("%d", start_date) as "Date"
    FROM events
    ORDER BY start_date
    ';

    // Execute the query and fetch results
    $results = $db->query($sql, [':next_event_id' => $next_event_id]);

    // Output the results
   // Use HtmlRenderer to output the results in an HTML table
   $renderer = new HtmlRenderer();
   echo $renderer->renderTable($results);

} catch (\PDOException $e) {
    // Handle any errors
    echo "Error: " . $e->getMessage();
}
