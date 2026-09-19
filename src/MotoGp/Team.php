<?php

namespace MotoGp;

use Webmin\Database;

class Team
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getTeams(): array
    {
        $sql = '
            select t.*
            from teams t
            order by t.team_name
        ';

        return $this->db->query($sql);
    }

    public function createTeam(array $data): int
    {
        $sql = '
            insert into teams (
                team_name,
                short_team_name,
                manufacturer
            )
            values (
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
            update teams
            set team_name = :team_name,
                short_team_name = :short_team_name,
                manufacturer = :manufacturer
            where team_id = :team_id
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
            select 1
            from riders
            where team_id = :team_id
            limit 1
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
            delete from teams
            where team_id = :team_id
        ';

        return $this->db->execute($sql, [
            ':team_id' => $teamId
        ]) === 1;
    }
}
