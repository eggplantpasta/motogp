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
            SELECT
                r.*,
                p.name AS rider_name,
                p.race_number
            FROM results r
            JOIN riders p ON r.rider_id = p.rider_id
            WHERE r.event_id = :event_id
            ORDER BY
                CASE r.status
                    WHEN \'classified\' THEN 0
                    WHEN \'dnf\' THEN 1
                    WHEN \'dns\' THEN 2
                    WHEN \'dsq\' THEN 3
                    ELSE 4
                END,
                r.position ASC,
                p.name
        ';

        return $this->db->query($sql, [
            ':event_id' => $eventId
        ]);
    }

    public function saveResults(int $eventId, array $results): bool
    {
        try {
            $this->db->beginTransaction();

            $this->db->execute(
                '
                    DELETE FROM results
                    WHERE event_id = :event_id
                ',
                [':event_id' => $eventId]
            );

            $sql = '
                INSERT INTO results (
                    event_id,
                    rider_id,
                    position,
                    status
                )
                VALUES (
                    :event_id,
                    :rider_id,
                    :position,
                    :status
                )
            ';

            foreach ($results as $riderId => $result) {
                $this->db->execute($sql, [
                    ':event_id' => $eventId,
                    ':rider_id' => $riderId,
                    ':position' => $result['position'],
                    ':status' => $result['status'],
                ]);
            }

            $this->db->commit();

            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getRidersForEventResults(int $eventId): array
    {
        $sql = '
            SELECT
                r.rider_id,
                r.race_number,
                r.name AS rider_name,
                r.active,
                res.position,
                res.status
            FROM riders r
            LEFT JOIN results res
                ON res.rider_id = r.rider_id
                AND res.event_id = :event_id
            ORDER BY
                CASE
                    WHEN res.status = \'classified\' THEN 0
                    WHEN res.status IS NOT NULL THEN 1
                    ELSE 2
                END,
                res.position,
                r.active DESC,
                r.name
        ';

        return $this->db->query($sql, [
            ':event_id' => $eventId
        ]);
    }

}
