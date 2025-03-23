<?php

require_once 'bootstrap.php';

include 'partials/page_top.html';

use Webmin\Database;
use Webmin\HtmlRenderer;

try {
    // Initialize the Database class with the SQLite DSN
    $db = new Database(SQLITE_DSN);

    // Define the SQL query
    $sql = 'SELECT name as "Rider Name" FROM riders';

    // Execute the query and fetch results
    $results = $db->query($sql);

    // Use HtmlRenderer to output the results in an HTML table
    $renderer = new HtmlRenderer();
    echo $renderer->renderTable($results);
    
} catch (\PDOException $e) {
    // Handle any errors
    echo "Error: " . $e->getMessage();
}

include 'partials/page_bottom.html';
