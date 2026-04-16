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
            $sql = 'select r.* from riders r order by r.name';
            return $this->db->query($sql);
        } catch (\PDOException $e) {
            $this->logger?->error("Failed to fetch riders: " . $e->getMessage());
            throw $e;
        }
    }

    public function getRiderById(int $riderId): ?array
    {
        try {
            $sql = 'select r.* from riders r where r.rider_id = :rider_id';
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
                ':team' => $data['team'],
                ':active' => $data['active'] ? 1 : 0,
                ':rider_id' => $riderId
            ];
            $sql = '
            update riders
            set name = :name
                , team = :team
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
                ':team' => $data['team'],
                ':active' => $data['active'] ? 1 : 0
            ];
            $sql = '
            insert into riders (name, team, active)
            values (:name, :team, :active)
            ';
            $result = $this->db->execute($sql, $params);
            $this->logger?->info("Rider created: " . $data['name']);
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
            $this->logger?->info("Rider deleted: ID " . $riderId);
            return $result;
        } catch (\PDOException $e) {
            $this->logger?->error("Failed to delete rider ID " . $riderId . ": " . $e->getMessage());
            throw $e;
        }
    }

}
