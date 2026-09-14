<?php

namespace MotoGp;

class Bid
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getUserBids(int $userId, int $eventId): array
    {
        $sql = '
            SELECT *
            FROM bids
            WHERE user_id = :user_id
              AND event_id = :event_id
            ORDER BY bid_number
        ';

        return $this->db->query($sql, [
            ':user_id' => $userId,
            ':event_id' => $eventId,
        ]);
    }

    public function saveUserBids(
        int $userId,
        int $eventId,
        array $bids
    ): bool {
        try {
            $this->db->beginTransaction();

            $this->db->execute(
                '
                    DELETE FROM bids
                    WHERE user_id = :user_id
                      AND event_id = :event_id
                ',
                [
                    ':user_id' => $userId,
                    ':event_id' => $eventId,
                ]
            );

            foreach ($bids as $bidNumber => $bid) {
                if ($bid['rider_id'] === '') {
                    continue;
                }

                $this->db->execute(
                    '
                        INSERT INTO bids (
                            user_id,
                            rider_id,
                            event_id,
                            bid_number,
                            amount
                        )
                        VALUES (
                            :user_id,
                            :rider_id,
                            :event_id,
                            :bid_number,
                            :amount
                        )
                    ',
                    [
                        ':user_id' => $userId,
                        ':rider_id' => $bid['rider_id'],
                        ':event_id' => $eventId,
                        ':bid_number' => $bidNumber,
                        ':amount' => $bid['amount'],
                    ]
                );
            }

            $this->db->commit();

            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }
}