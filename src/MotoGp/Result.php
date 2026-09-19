<?php

namespace MotoGp;

use Webmin\Database;

class Result
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getResultsByEventId(int $eventId): array
    {
        $sql = '
            select
                r.*,
                p.name as rider_name,
                p.race_number
            from results r
            join riders p on r.rider_id = p.rider_id
            where r.event_id = :event_id
            order by
                case r.status
                    when \'classified\' then 0
                    when \'dnf\' then 1
                    when \'dns\' then 2
                    when \'dsq\' then 3
                    else 4
                end,
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
                    delete from results
                    where event_id = :event_id
                ',
                [':event_id' => $eventId]
            );

            $sql = '
                insert into results (
                    event_id,
                    rider_id,
                    position,
                    status
                )
                values (
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
            select
                r.rider_id,
                r.race_number,
                r.name as rider_name,
                r.active,
                res.position,
                res.status
            from riders r
            left join results res
                on res.rider_id = r.rider_id
                and res.event_id = :event_id
            order by
                case
                    when res.status = \'classified\' then 0
                    when res.status is not null then 1
                    else 2
                end,
                res.position,
                r.active DESC,
                r.name
        ';

        return $this->db->query($sql, [
            ':event_id' => $eventId
        ]);
    }

}
