<?php

namespace MotoGp;

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
        WHERE date(start_date) >= date("now")
        ORDER BY start_date
        LIMIT 1
        ';

        // Execute the query and fetch the result
        $results = $this->db->query($sql);

        return !empty($results) ? $results[0]['event_id'] : null; // return the first result or null if empty
    }

    public function lastEvent(): ?int
    {
        // Define the SQL query to get the last event
        $sql = '
        SELECT
        event_id
        FROM events
        WHERE date(start_date) < date("now")
        ORDER BY start_date DESC
        LIMIT 1
        ';

        // Execute the query and fetch the result
        $results = $this->db->query($sql);

        return !empty($results) ? $results[0]['event_id'] : null; // return the first result or null if empty
    }

    public function getNextEvent(): ?array
    {
        $sql = '
        SELECT e.*, c.name AS country_name, lower(c.alpha_2) AS alpha_2
        FROM events e
        LEFT JOIN countries c ON e.country_code = c.country_code
        WHERE date(e.start_date) >= date("now")
        ORDER BY e.start_date
        LIMIT 1
        ';

        $results = $this->db->query($sql);

        return !empty($results) ? $results[0] : null;
    }

    public function getEventById(int $eventId): ?array
    {
        $sql = '
        SELECT e.*, c.name AS country_name, lower(c.alpha_2) AS alpha_2
        FROM events e
        LEFT JOIN countries c ON e.country_code = c.country_code
        WHERE e.event_id = :event_id
        ';

        $results = $this->db->query($sql, [':event_id' => $eventId]);

        return !empty($results) ? $results[0] : null; // return the first result or null if empty
    }

    public function getEvents(): array
    {
        $sql = '
        SELECT e.*, c.name AS country_name, lower(c.alpha_2) AS alpha_2
        FROM events e
        LEFT JOIN countries c ON e.country_code = c.country_code
        ORDER BY e.start_date
        ';

        $results = $this->db->query($sql);

        return !empty($results) && $results[0] ? $results : []; // return results or empty array
    }

    public function updateEvent(int $eventId, array $data): bool
    {
        $sql = '
        UPDATE events
        SET start_date = :start_date,
            name = :name,
            circuit = :circuit,
            country_code = :country_code,
            bids_open = :bids_open
        WHERE event_id = :event_id
        ';

        $params = [
            ':event_id' => $eventId,
            ':start_date' => $data['start_date'],
            ':name' => $data['name'],
            ':circuit' => $data['circuit'],
            ':country_code' => $data['country_code'],
            ':bids_open' => $data['bids_open'] ? 1 : 0,
        ];

        $result = $this->db->query($sql, $params);

        return $result !== false;
    }
}
