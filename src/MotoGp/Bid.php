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

    public function getLatestResolvedWinningBids(): ?array
    {
        $event = $this->db->queryOne(
            '
                select event_id, name
                from events
                where bids_resolved_at is not null
                order by start_date desc, event_id desc
                limit 1
            '
        );

        if ($event === null) {
            return null;
        }

        $event['bids'] = $this->db->query(
            'select
                u.username,
                r.name,
                r.race_number,
                b.amount
            from bids b
            join users u on u.user_id = b.user_id
            join riders r on r.rider_id = b.rider_id
            where b.event_id = :event_id
            and b.won = 1
            order by
                r.race_number,
                u.username',
            [':event_id' => $event['event_id']]
        );

        return $event;
    }

    public function hasUnsettledEarlierEvents(int $eventId): bool
    {
        $sql = '
            select 1
            from events e
            join events current_event
                on current_event.event_id = :event_id
            where (
                e.start_date < current_event.start_date
                or (
                    e.start_date = current_event.start_date
                    and e.event_id < current_event.event_id
                )
            )
            and e.payouts_settled_at is null
            and exists (
                select 1
                from bids b
                where b.event_id = e.event_id
            )
            limit 1
        ';

        return $this->db->queryOne($sql, [
            ':event_id' => $eventId,
        ]) !== null;
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
                || $this->hasUnsettledEarlierEvents($eventId)
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

            // Calculate each player's total bids.
            $totals = [];

            foreach ($bids as $bid) {
                $userId = (int)$bid['user_id'];

                $totals[$userId] =
                    ($totals[$userId] ?? 0) + (int)$bid['amount'];
            }

            // Reduce bids proportionately where necessary.
            foreach ($bids as &$bid) {
                $userId = (int)$bid['user_id'];
                $total = $totals[$userId];

                $user = $this->db->queryOne(
                    '
                        select balance
                        from users
                        where user_id = :user_id
                    ',
                    [':user_id' => $userId]
                );

                if ($user === null) {
                    throw new \RuntimeException('Bid user not found.');
                }

                $balance = (int)$user['balance'];

                if ($total > $balance && $total > 0) {
                    $bid['amount'] = (int)floor(
                        (int)$bid['amount'] * $balance / $total
                    );

                    $this->db->execute(
                        '
                            update bids
                            set amount = :amount
                            where bid_id = :bid_id
                        ',
                        [
                            ':amount' => $bid['amount'],
                            ':bid_id' => $bid['bid_id'],
                        ]
                    );
                }
            }

            unset($bid);

            $highestBids = [];

            foreach ($bids as $bid) {
                $riderId = (int)$bid['rider_id'];
                $amount = (int)$bid['amount'];

                $highestBids[$riderId] = max(
                    $highestBids[$riderId] ?? 0,
                    $amount
                );
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

    public function refundWinningBids(int $eventId): void
    {
        $transactions = $this->db->query(
            '
                select
                    user_id,
                    bid_id,
                    -amount as refund
                from balance_transactions
                where event_id = :event_id
                and transaction_type = \'winning_bid\'
            ',
            [':event_id' => $eventId]
        );

        foreach ($transactions as $transaction) {
            $this->db->execute(
                '
                    update users
                    set balance = balance + :amount
                    where user_id = :user_id
                ',
                [
                    ':amount' => $transaction['refund'],
                    ':user_id' => $transaction['user_id'],
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
                        \'bid_refund\',
                        :amount
                    )
                ',
                [
                    ':user_id' => $transaction['user_id'],
                    ':event_id' => $eventId,
                    ':bid_id' => $transaction['bid_id'],
                    ':amount' => $transaction['refund'],
                ]
            );
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

        $payouts = [];

        foreach ($bids as $bid) {
            if (
                $bid['status'] !== 'classified'
                || $bid['position'] === null
            ) {
                continue;
            }

            $position = (int)$bid['position'];

            // Only the first eight finishing positions receive payouts.
            if (!isset($percentages[$position - 1])) {
                continue;
            }

            $percentage = $percentages[$position - 1];

            $payouts[] = [
                'bid_id' => (int)$bid['bid_id'],
                'user_id' => (int)$bid['user_id'],
                'username' => $bid['username'],
                'rider_id' => (int)$bid['rider_id'],
                'name' => $bid['name'],
                'race_number' => $bid['race_number'],
                'finish_position' => $position,
                'percentage' => $percentage,
                'payout' => (int)ceil(
                    $pool * $percentage / 100
                ),
            ];
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
            select count(distinct position) as positions_entered
            from results
            where event_id = :event_id
            and status = \'classified\'
            and position between 1 and 8
        ',
            [
                ':event_id' => $eventId,
            ]
        );

        return (int)$result['positions_entered'] === 8;
    }

}
