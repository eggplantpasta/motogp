<?php

namespace MotoGp;

use DateTime;
use Database;

class Event {

    private $db;
    public function __construct($db) {
            $this->db = $db;
    }

    public function nextEvent(): ?int
    {
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
        $results = $this->db->query($sql);

        return !empty($results) ? $results[0]['event_id'] : null; // return the first result or null if empty
    }
}