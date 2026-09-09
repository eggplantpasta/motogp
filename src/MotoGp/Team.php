<?php
namespace MotoGp;

use Psr\Log\LoggerInterface;

class Team
{
    private $db;
    private ?LoggerInterface $logger;

    public function __construct($db, ?LoggerInterface $logger = null)
    {
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

            $this->logger?->info('Fetched teams', [
                'count' => count($results)
            ]);

            return $results;
        } catch (\PDOException $e) {
            $this->logger?->error('Failed to fetch teams', [
                'message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    public function createTeam(array $data): int
    {
        $sql = '
            INSERT INTO teams (
                team_name,
                short_team_name,
                manufacturer
            )
            VALUES (
                :team_name,
                :short_team_name,
                :manufacturer
            )
        ';

        return $this->db->execute($sql, [
            ':team_name' => $data['team_name'],
            ':short_team_name' => $data['short_team_name'],
            ':manufacturer' => $data['manufacturer'],
        ]);
    }

    public function updateTeam(int $teamId, array $data): int
    {
        $sql = '
            UPDATE teams
            SET team_name = :team_name,
                short_team_name = :short_team_name,
                manufacturer = :manufacturer
            WHERE team_id = :team_id
        ';

        return $this->db->execute($sql, [
            ':team_name' => $data['team_name'],
            ':short_team_name' => $data['short_team_name'],
            ':manufacturer' => $data['manufacturer'],
            ':team_id' => $teamId,
        ]);
    }

    public function hasRiders(int $teamId): bool
    {
        $sql = '
            SELECT 1
            FROM riders
            WHERE team_id = :team_id
            LIMIT 1
        ';

        return $this->db->queryOne($sql, [
            ':team_id' => $teamId
        ]) !== null;
    }

    public function deleteTeam(int $teamId): bool
    {
        if ($this->hasRiders($teamId)) {
            return false;
        }

        $sql = '
            DELETE FROM teams
            WHERE team_id = :team_id
        ';

        return $this->db->execute($sql, [
            ':team_id' => $teamId
        ]) === 1;
    }
}