<?php

namespace MotoGp;

use Webmin\Database;
use Psr\Log\LoggerInterface;

class Riders
{
    private Database $db;
    private ?LoggerInterface $logger;

    public function __construct(Database $db, ?LoggerInterface $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function getRiders(): array
    {
        $sql = '
            select r.*, t.team_name
            from riders r
            left join teams t on r.team_id = t.team_id
            order by r.name
        ';
        return $this->db->query($sql);
    }

    public function getActiveRiders(): array
    {
        $sql = '
            SELECT r.*, t.team_name
            FROM riders r
            LEFT JOIN teams t ON r.team_id = t.team_id
            WHERE r.active = 1
            ORDER BY r.name
        ';
        return $this->db->query($sql);
    }

    public function getRiderById(int $riderId): ?array
    {
        $sql = '
            select r.*, t.team_name
            from riders r
            left join teams t on r.team_id = t.team_id
            where r.rider_id = :rider_id
        ';
        return $this->db->queryOne($sql, [':rider_id' => $riderId]);
    }

    public function updateRider(int $riderId, array $data): int
    {
        $params = [
            ':race_number' => $data['race_number'],
            ':name' => $data['name'],
            ':team_id' => $data['team_id'] ?? null,
            ':active' => $data['active'] ? 1 : 0,
            ':rider_id' => $riderId
        ];
        $sql = '
        update riders
        set race_number = :race_number,
            name = :name,
            team_id = :team_id,
            active = :active
        where rider_id = :rider_id
        ';
        $result = $this->db->execute($sql, $params);
        $this->logger?->info("Rider updated: ID " . $riderId, ['data' => $data]);
        return $result;
    }

    public function createRider(array $data): int
    {
        $params = [
            ':race_number' => $data['race_number'],
            ':name' => $data['name'],
            ':team_id' => $data['team_id'] ?? null,
            ':active' => $data['active'] ? 1 : 0
        ];
        $sql = '
        insert into riders (
            race_number,
            name,
            team_id,
            active
        )
        values (
            :race_number,
            :name,
            :team_id,
            :active
        )';
        $result = $this->db->execute($sql, $params);
        $this->logger?->info("Rider created: ", $params);
        return $result;
    }

    public function deleteRider(int $riderId): int
    {
        if ($this->hasBids($riderId) || $this->hasResults($riderId)) {
            return 0;
        }

        $sql = '
            DELETE FROM riders
            WHERE rider_id = :rider_id
        ';

        return $this->db->execute($sql, [
            ':rider_id' => $riderId
        ]);
    }

    public function hasBids(int $riderId): bool
    {
        $sql = '
            SELECT 1
            FROM bids
            WHERE rider_id = :rider_id
            LIMIT 1
        ';

        return $this->db->queryOne($sql, [
            ':rider_id' => $riderId
        ]) !== null;
    }

    public function hasResults(int $riderId): bool
    {
        $sql = '
            SELECT 1
            FROM results
            WHERE rider_id = :rider_id
            LIMIT 1
        ';

        return $this->db->queryOne($sql, [
            ':rider_id' => $riderId
        ]) !== null;
    }

}
