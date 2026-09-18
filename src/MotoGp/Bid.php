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

    public function getEventBids(int $eventId): array
    {
        $sql = '
            SELECT
                b.bid_id,
                b.user_id,
                b.rider_id,
                b.amount,
                b.won,
                u.username,
                r.name AS rider_name,
                r.race_number
            FROM bids b
            JOIN users u ON u.user_id = b.user_id
            JOIN riders r ON r.rider_id = b.rider_id
            WHERE b.event_id = :event_id
            ORDER BY
                r.race_number,
                b.amount DESC,
                u.username
        ';

        return $this->db->query(
            $sql,
            ['event_id' => $eventId]
        );
    }

    public function resolveBids(int $eventId): bool
    {
        try {
            $this->db->beginTransaction();

            $event = $this->db->queryOne(
                '
                    SELECT
                        bids_open,
                        bids_resolved_at
                    FROM events
                    WHERE event_id = :event_id
                ',
                [':event_id' => $eventId]
            );

            if (
                $event === null
                || (bool)$event['bids_open']
                || $event['bids_resolved_at'] !== null
            ) {
                $this->db->rollBack();
                return false;
            }

            $bids = $this->db->query(
                '
                    SELECT
                        bid_id,
                        user_id,
                        rider_id,
                        amount
                    FROM bids
                    WHERE event_id = :event_id
                    ORDER BY rider_id, amount DESC
                ',
                [':event_id' => $eventId]
            );

            if (empty($bids)) {
                $this->db->rollBack();
                return false;
            }

            $highestBids = [];

            foreach ($bids as $bid) {
                $riderId = (int)$bid['rider_id'];

                if (!isset($highestBids[$riderId])) {
                    $highestBids[$riderId] = (int)$bid['amount'];
                }
            }

            foreach ($bids as $bid) {
                $won =
                    (int)$bid['amount'] ===
                    $highestBids[(int)$bid['rider_id']];

                $this->db->execute(
                    '
                        UPDATE bids
                        SET won = :won
                        WHERE bid_id = :bid_id
                    ',
                    [
                        ':won' => $won ? 1 : 0,
                        ':bid_id' => $bid['bid_id'],
                    ]
                );

                if ($won) {
                    $this->db->execute(
                        '
                            UPDATE users
                            SET balance = balance - :amount
                            WHERE user_id = :user_id
                        ',
                        [
                            ':amount' => $bid['amount'],
                            ':user_id' => $bid['user_id'],
                        ]
                    );
                }
            }

            $this->db->execute(
                '
                    UPDATE events
                    SET bids_resolved_at = current_timestamp
                    WHERE event_id = :event_id
                ',
                [':event_id' => $eventId]
            );

            $this->db->commit();

            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

}
