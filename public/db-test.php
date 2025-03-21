<?php

require_once 'bootstrap.php';

use Webmin\Database;

try {
    // Initialize the Database class with the SQLite DSN
    $db = new Database(SQLITE_DSN);

    // Define the SQL query
    $sql = 'SELECT * FROM users';

    // Execute the query and fetch results
    $results = $db->query($sql);

    // Output the results
    echo '<pre>';
    print_r($results);
    echo '</pre>';

} catch (\PDOException $e) {
    // Handle any errors
    echo "Error: " . $e->getMessage();
}