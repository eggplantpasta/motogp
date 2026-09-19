<?php

namespace MotoGp;

use Webmin\Database;

class Event
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getNextEventId(): ?int
    {
        $sql = '
            select event_id
            from events
            where date(start_date) > date("now")
            order by start_date
            limit 1
        ';

        $result = $this->db->queryOne($sql);
        return $result ? (int)$result['event_id'] : null;

    }

    public function getLastEventId(): ?int
    {
        $sql = '
            select event_id
            from events
            where date(start_date) <= date("now")
            order by start_date desc
            limit 1
        ';

        $result = $this->db->queryOne($sql);
        return $result ? (int)$result['event_id'] : null;
    }

    public function getNextEvent(): ?array
    {
        $sql = '
            select
                e.*,
                c.name as country_name,
                lower(c.alpha_2) as alpha_2
            from events e
            left join countries c on e.country_code = c.country_code
            where date(e.start_date) > date("now")
            order by e.start_date
            limit 1
        ';

        return $this->db->queryOne($sql);
    }

    public function getEventById(int $eventId): ?array
    {
        $sql = '
            select
                e.*, c.name as country_name,
                lower(c.alpha_2) as alpha_2
            from events e
            left join countries c on e.country_code = c.country_code
            where e.event_id = :event_id
        ';

        return $this->db->queryOne($sql, [':event_id' => $eventId]);
    }

    public function getEvents(): array
    {
        $sql = '
            select
                e.*,
                c.name as country_name,
                lower(c.alpha_2) as alpha_2
            from events e
            left join countries c on e.country_code = c.country_code
            order by e.start_date
        ';

        return $this->db->query($sql);
    }

    public function updateEvent(int $eventId, array $data): bool
    {
        try {
            $params = [
                ':event_id' => $eventId,
                ':start_date' => $data['start_date'],
                ':name' => $data['name'],
                ':circuit' => $data['circuit'],
                ':country_code' => $data['country_code'],
                ':bids_open' => $data['bids_open'] ? 1 : 0,
            ];

            $this->db->beginTransaction();

            $sql = '
                update events
                set start_date = :start_date,
                    name = :name,
                    circuit = :circuit,
                    country_code = :country_code,
                    bids_open = :bids_open
                where event_id = :event_id
            ';
            $this->db->execute($sql, $params);

            // Only one event can have bids open at a time.
            if ($data['bids_open']) {
                $sql = '
                    update events
                    set bids_open = 0
                    where event_id != :event_id
                ';
                $this->db->execute($sql, [':event_id' => $eventId]);
            }

            $this->db->commit();

            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function createEvent(array $data): ?int
    {
        try {
            $this->db->beginTransaction();

            $sql = '
                insert into events (
                    start_date,
                    name,
                    circuit,
                    country_code,
                    bids_open
                )
                values (
                    :start_date,
                    :name,
                    :circuit,
                    :country_code,
                    :bids_open
                )
            ';

            $this->db->execute($sql, [
                ':start_date' => $data['start_date'],
                ':name' => $data['name'],
                ':circuit' => $data['circuit'],
                ':country_code' => $data['country_code'],
                ':bids_open' => $data['bids_open'] ? 1 : 0,
            ]);

            $eventId = (int)$this->db->getConnection()->lastInsertId();

            // Only one event can have bids open at a time.
            if ($data['bids_open']) {
                $sql = '
                    update events
                    set bids_open = 0
                    where event_id != :event_id
                ';

                $this->db->execute($sql, [
                    ':event_id' => $eventId
                ]);
            }

            $this->db->commit();

            return $eventId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return null;
        }
    }

    public function hasBids(int $eventId): bool
    {
        $sql = '
            select 1
            from bids
            where event_id = :event_id
            limit 1
        ';

        return $this->db->queryOne($sql, [
            ':event_id' => $eventId
        ]) !== null;
    }

    public function hasResults(int $eventId): bool
    {
        $sql = '
            select 1
            from results
            where event_id = :event_id
            limit 1
        ';

        return $this->db->queryOne($sql, [
            ':event_id' => $eventId
        ]) !== null;
    }

    public function deleteEvent(int $eventId): bool
    {
        if ($this->hasBids($eventId) || $this->hasResults($eventId)) {
            return false;
        }

        $sql = '
            delete from events
            where event_id = :event_id
        ';

        return $this->db->execute($sql, [
            ':event_id' => $eventId
        ]) === 1;
    }

}
