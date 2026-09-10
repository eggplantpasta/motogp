<?php

namespace MotoGp;

class Result
{
    private $db;

    public function __construct($db)
    {
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

        return $this->db->query($sql, [
            ':event_id' => $eventId
        ]);
    }

    public function saveResults(int $eventId, array $results): bool
    {
        try {
            $this->db->beginTransaction();

            $sql = '
                DELETE FROM results
                WHERE event_id = :event_id
            ';

            $this->db->execute($sql, [
                ':event_id' => $eventId
            ]);

            $sql = '
                INSERT INTO results (
                    event_id,
                    rider_id,
                    position
                )
                VALUES (
                    :event_id,
                    :rider_id,
                    :position
                )
            ';

            foreach ($results as $riderId => $position) {
                $this->db->execute($sql, [
                    ':event_id' => $eventId,
                    ':rider_id' => $riderId,
                    ':position' => $position,
                ]);
            }

            $this->db->commit();

            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
