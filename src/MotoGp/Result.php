<?php

namespace MotoGp;

class Result {

    private $db;
    public function __construct($db) {
            $this->db = $db;
    }

    public function getResultsByEventId(int $eventId): array
    {
        $sql = '
        SELECT r.*, p.name AS rider_name
        FROM results r
        JOIN riders p ON r.rider_id = p.rider_id
        WHERE r.event_id = :event_id
        ORDER BY r.position ASC
        ';

        $results = $this->db->query($sql, [':event_id' => $eventId]);
        return !empty($results) ? $results : []; // return results or empty array
    }

}
