<?php

namespace MotoGp;

use Webmin\Database;

class Bid
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getUserBids(int $userId, int $eventId): array
    {
        $sql = '
            select *
            from bids
            where user_id = :user_id
            and event_id = :event_id
            order by bid_number
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
                    delete from bids
                    where user_id = :user_id
                    and event_id = :event_id
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
                        insert into bids (
                            user_id,
                            rider_id,
                            event_id,
                            bid_number,
                            amount
                        )
                        values (
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
            select
                b.bid_id,
                b.user_id,
                b.rider_id,
                b.amount,
                b.won,
                u.username,
                r.name as name,
                r.race_number
            from bids b
            join users u on u.user_id = b.user_id
            join riders r on r.rider_id = b.rider_id
            where b.event_id = :event_id
            order by
                r.race_number,
                b.amount desc,
                u.username
        ';

        return $this->db->query(
            $sql,
            [':event_id' => $eventId]
        );
    }

    public function resolveBids(int $eventId): bool
    {
        try {
            $this->db->beginTransaction();

            $event = $this->db->queryOne(
                '
                    select
                        bids_open,
                        bids_resolved_at
                    from events
                    where event_id = :event_id
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
                    select
                        bid_id,
                        user_id,
                        rider_id,
                        amount
                    from bids
                    where event_id = :event_id
                    order by rider_id, amount desc
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
                        update bids
                        set won = :won
                        where bid_id = :bid_id
                    ',
                    [
                        ':won' => $won ? 1 : 0,
                        ':bid_id' => $bid['bid_id'],
                    ]
                );

                if ($won) {
                    $this->db->execute(
                        '
                            update users
                            set balance = balance - :amount
                            where user_id = :user_id
                        ',
                        [
                            ':amount' => $bid['amount'],
                            ':user_id' => $bid['user_id'],
                        ]
                    );

                    $this->db->execute(
                        '
                            insert into balance_transactions (
                                user_id,
                                event_id,
                                bid_id,
                                transaction_type,
                                amount
                            )
                            values (
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
                    update events
                    set bids_resolved_at = current_timestamp
                    where event_id = :event_id
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
                select
                    b.bid_id,
                    b.user_id,
                    b.rider_id,
                    b.amount,
                    u.username,
                    r.name as name,
                    r.race_number,
                    res.position,
                    res.status
                from bids b
                join users u
                    on u.user_id = b.user_id
                join riders r
                    on r.rider_id = b.rider_id
                left join results res
                    on res.event_id = b.event_id
                    and res.rider_id = b.rider_id
                where b.event_id = :event_id
                and b.won = 1
                order by
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
                    'name' => $bid['name'],
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

            for ($i = 0; $i < $ownerCount; $i++) {
                if ($slot >= count($percentages)) {
                    break;
                }

                $combinedPercentage += $percentages[$slot];
                $slot++;
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
                    'name' => $rider['name'],
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
                    select
                        bids_resolved_at,
                        payouts_settled_at
                    from events
                    where event_id = :event_id
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
                        update users
                        set balance = balance + :amount
                        where user_id = :user_id
                    ',
                    [
                        ':amount' => $payout['payout'],
                        ':user_id' => $payout['user_id'],
                    ]
                );

                $this->db->execute(
                    '
                        insert into balance_transactions (
                            user_id,
                            event_id,
                            bid_id,
                            transaction_type,
                            amount
                        )
                        values (
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
                    update events
                    set payouts_settled_at = current_timestamp
                    where event_id = :event_id
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
                select count(distinct b.rider_id) as missing_results
                from bids b
                left join results r
                    on r.event_id = b.event_id
                    and r.rider_id = b.rider_id
                where b.event_id = :event_id
                and b.won = 1
                and r.rider_id is null
            ',
            [
                ':event_id' => $eventId,
            ]
        );

        return (int)$result['missing_results'] === 0;
    }

}
