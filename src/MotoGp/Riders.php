<?php

namespace MotoGp;

class Riders {

    private $db;
    public function __construct($db) {
            $this->db = $db;
    }

    public function getRiders(): array
    {
        $sql = 'select r.* from riders r order by r.name';
        return $this->db->query($sql);
    }

    public function getRiderById(int $riderId): ?array
    {
        $sql = 'select r.* from riders r where r.rider_id = :rider_id';
        return $this->db->queryOne($sql, [':rider_id' => $riderId]);
    }

    public function updateRider(int $riderId, array $data): bool
    {
        $params = [
            ':name' => $data['name'],
            ':team' => $data['team'],
            ':active' => $data['active'] ? 1 : 0,
            ':rider_id' => $riderId
        ];
        $sql = '
        update riders
        set name = :name
            team = :team
            active = :active
        where rider_id = :rider_id
        ';
        return $this->db->execute($sql, $params);
    }
}
