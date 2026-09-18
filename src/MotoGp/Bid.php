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

                    $this->db->execute(
                        '
                            INSERT INTO balance_transactions (
                                user_id,
                                event_id,
                                bid_id,
                                transaction_type,
                                amount
                            )
                            VALUES (
                                :user_id,
                                :event_id,
                                :bid_id,
                                \'winning_bid\',
                                :amount
                            )
                        ',
                        [
                            ':user_id' => $bid['user_id'],
                            ':event_id' => $eventId,
                            ':bid_id' => $bid['bid_id'],
                            ':amount' => -(int)$bid['amount'],
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

    public function calculatePayouts(int $eventId): array
    {
        $bids = $this->db->query(
            '
                SELECT
                    b.bid_id,
                    b.user_id,
                    b.rider_id,
                    b.amount,
                    u.username,
                    r.name AS rider_name,
                    r.race_number,
                    res.position,
                    res.status
                FROM bids b
                JOIN users u
                    ON u.user_id = b.user_id
                JOIN riders r
                    ON r.rider_id = b.rider_id
                LEFT JOIN results res
                    ON res.event_id = b.event_id
                    AND res.rider_id = b.rider_id
                WHERE b.event_id = :event_id
                AND b.won = 1
                ORDER BY
                    res.position,
                    r.race_number,
                    u.username
            ',
            [':event_id' => $eventId]
        );

        if (empty($bids)) {
            return [
                'pool' => 0,
                'winning_bids' => 0,
                'payouts' => [],
            ];
        }

        /*
        * Every winning bid contributes:
        *
        *     bid amount + 1 new point
        *
        * to the prize pool.
        */
        $winningAmount = 0;

        foreach ($bids as $bid) {
            $winningAmount += (int)$bid['amount'];
        }

        $winningBidCount = count($bids);
        $pool = $winningAmount + $winningBidCount;

        /*
        * Percentage paid by payout position.
        */
        $percentages = [
            23,
            18,
            15,
            12,
            11,
            8,
            7,
            6,
        ];

        /*
        * Group classified winning bids by rider.
        *
        * Multiple owners of the same rider occupy consecutive
        * payout positions and share their combined percentages.
        */
        $riders = [];

        foreach ($bids as $bid) {
            if (
                $bid['status'] !== 'classified'
                || $bid['position'] === null
            ) {
                continue;
            }

            $riderId = (int)$bid['rider_id'];

            if (!isset($riders[$riderId])) {
                $riders[$riderId] = [
                    'rider_id' => $riderId,
                    'rider_name' => $bid['rider_name'],
                    'race_number' => $bid['race_number'],
                    'position' => (int)$bid['position'],
                    'owners' => [],
                ];
            }

            $riders[$riderId]['owners'][] = $bid;
        }

        usort(
            $riders,
            fn ($a, $b) => $a['position'] <=> $b['position']
        );

        $payouts = [];
        $slot = 0;

        foreach ($riders as $rider) {
            $ownerCount = count($rider['owners']);

            if ($slot >= count($percentages)) {
                break;
            }

            $combinedPercentage = 0;
            $firstSlot = $slot + 1;

            for ($i = 0; $i < $ownerCount; $i++) {
                if ($slot >= count($percentages)) {
                    break;
                }

                $combinedPercentage += $percentages[$slot];
                $slot++;
            }

            $paidOwnerCount = min(
                $ownerCount,
                count($percentages) - $firstSlot + 1
            );

            if ($paidOwnerCount < 1) {
                break;
            }

            /*
            * All owners of a tied rider share the percentages
            * occupied by that rider.
            */
            $percentagePerOwner =
                $combinedPercentage / $ownerCount;

            foreach ($rider['owners'] as $owner) {
                $payouts[] = [
                    'bid_id' => (int)$owner['bid_id'],
                    'user_id' => (int)$owner['user_id'],
                    'username' => $owner['username'],
                    'rider_id' => $rider['rider_id'],
                    'rider_name' => $rider['rider_name'],
                    'race_number' => $rider['race_number'],
                    'finish_position' => $rider['position'],
                    'percentage' => $percentagePerOwner,
                    'payout' => (int)ceil(
                        $pool * $percentagePerOwner / 100
                    ),
                ];
            }
        }

        return [
            'pool' => $pool,
            'winning_bids' => $winningBidCount,
            'payouts' => $payouts,
        ];
    }

    public function settlePayouts(int $eventId): bool
    {
        try {
            $this->db->beginTransaction();

            $event = $this->db->queryOne(
                '
                    SELECT
                        bids_resolved_at,
                        payouts_settled_at
                    FROM events
                    WHERE event_id = :event_id
                ',
                [':event_id' => $eventId]
            );

            if (
                $event === null
                || $event['bids_resolved_at'] === null
                || $event['payouts_settled_at'] !== null
            ) {
                $this->db->rollBack();
                return false;
            }

            if (!$this->resultsCompleteForPayout($eventId)) {
                $this->db->rollBack();
                return false;
            }

            $calculation = $this->calculatePayouts($eventId);

            if (empty($calculation['payouts'])) {
                $this->db->rollBack();
                return false;
            }

            foreach ($calculation['payouts'] as $payout) {
                $this->db->execute(
                    '
                        UPDATE users
                        SET balance = balance + :amount
                        WHERE user_id = :user_id
                    ',
                    [
                        ':amount' => $payout['payout'],
                        ':user_id' => $payout['user_id'],
                    ]
                );

                $this->db->execute(
                    '
                        INSERT INTO balance_transactions (
                            user_id,
                            event_id,
                            bid_id,
                            transaction_type,
                            amount
                        )
                        VALUES (
                            :user_id,
                            :event_id,
                            :bid_id,
                            \'payout\',
                            :amount
                        )
                    ',
                    [
                        ':user_id' => $payout['user_id'],
                        ':event_id' => $eventId,
                        ':bid_id' => $payout['bid_id'],
                        ':amount' => $payout['payout'],
                    ]
                );
            }

            $this->db->execute(
                '
                    UPDATE events
                    SET payouts_settled_at = current_timestamp
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

    public function resultsCompleteForPayout(int $eventId): bool
    {
        $result = $this->db->queryOne(
            '
                SELECT COUNT(DISTINCT b.rider_id) AS missing_results
                FROM bids b
                LEFT JOIN results r
                    ON r.event_id = b.event_id
                    AND r.rider_id = b.rider_id
                WHERE b.event_id = :event_id
                AND b.won = 1
                AND r.rider_id IS NULL
            ',
            [
                ':event_id' => $eventId,
            ]
        );

        return (int)$result['missing_results'] === 0;
    }

}
