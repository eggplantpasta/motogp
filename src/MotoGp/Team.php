<?php

namespace MotoGp;

use Psr\Log\LoggerInterface;


class Team {

    private $db;
    private ?LoggerInterface $logger;

    public function __construct($db, ?LoggerInterface $logger = null) {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function getTeams(): array
    {
        try {
            $sql = '
            SELECT t.*
            FROM teams t
            ORDER BY t.team_name
            ';
            $results = $this->db->query($sql);
            if ($this->logger) {
                $this->logger->info('Fetched teams', ['count' => count($results)]);
            }
            return !empty($results) && $results[0] ? $results : []; // return results or empty array
        } catch (\PDOException $e) {
            $this->logger?->error("Failed to fetch teams: " . $e->getMessage());
            throw $e;
        }
        return !empty($results) && $results[0] ? $results : []; // return results or empty array
    }
}
