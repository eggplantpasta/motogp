<?php

namespace MotoGp;

use Psr\Log\LoggerInterface;

class Riders {

    private $db;
    private ?LoggerInterface $logger;

    public function __construct($db, ?LoggerInterface $logger = null) {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function getRiders(): array
    {
        try {
            $sql = '
                select r.*, t.team_name
                from riders r
                left join teams t on r.team_id = t.team_id
                order by r.name
            ';
            return $this->db->query($sql);
        } catch (\PDOException $e) {
            $this->logger?->error("Failed to fetch riders: " . $e->getMessage());
            throw $e;
        }
    }

    public function getRiderById(int $riderId): ?array
    {
        try {
            $sql = '
                select r.*, t.team_name
                from riders r
                left join teams t on r.team_id = t.team_id
                where r.rider_id = :rider_id
            ';
            return $this->db->queryOne($sql, [':rider_id' => $riderId]);
        } catch (\PDOException $e) {
            $this->logger?->error("Failed to fetch rider ID " . $riderId . ": " . $e->getMessage());
            throw $e;
        }
    }

    public function updateRider(int $riderId, array $data): int
    {
        try {
            $params = [
                ':name' => $data['name'],
                ':team_id' => $data['team_id'] ?? null,
                ':active' => $data['active'] ? 1 : 0,
                ':rider_id' => $riderId
            ];
            $sql = '
            update riders
            set name = :name
                , team_id = :team_id
                , active = :active
            where rider_id = :rider_id
            ';
            $result = $this->db->execute($sql, $params);
            $this->logger?->info("Rider updated: ID " . $riderId, ['data' => $data]);
            return $result;
        } catch (\PDOException $e) {
            $this->logger?->error("Failed to update rider ID " . $riderId . ": " . $e->getMessage());
            throw $e;
        }
    }

    public function createRider(array $data): int
    {
        try {
            $params = [
                ':name' => $data['name'],
                ':team_id' => $data['team_id'] ?? null,
                ':active' => $data['active'] ? 1 : 0
            ];
            $sql = '
            insert into riders (name, team_id, active)
            values (:name, :team_id, :active)
            ';
            $result = $this->db->execute($sql, $params);
            $this->logger?->info("Rider created: ", $params);
            return $result;
        } catch (\PDOException $e) {
            $this->logger?->error("Failed to create rider: " . $e->getMessage(), ['data' => $data]);
            throw $e;
        }
    }

    public function deleteRider(int $riderId): int
    {
        try {
            $sql = 'delete from riders where rider_id = :rider_id';
            $result = $this->db->execute($sql, [':rider_id' => $riderId]);
            $this->logger?->info("Rider deleted:", ['rider_id' => $riderId]);
            return $result;
        } catch (\PDOException $e) {
            $this->logger?->error("Failed to delete rider:" , ['rider_id' => $riderId, 'message' => $e->getMessage()]);
            throw $e;
        }
    }

}
