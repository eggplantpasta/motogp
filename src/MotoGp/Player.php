<?php

namespace MotoGp;

use Psr\Log\LoggerInterface;
use Webmin\Database;

class Player
{
    public function __construct(
        private Database $db,
        private LoggerInterface $logger
    ) {
    }

    public function getBalanceTransactions(int $userId): array
    {
        return $this->db->query(
            '
                select
                    bt.transaction_id,
                    bt.transaction_type,
                    bt.amount,
                    bt.created_at,
                    e.name as event_name,
                    r.name as rider_name,
                    r.race_number
                from balance_transactions bt
                left join events e
                    on e.event_id = bt.event_id
                left join bids b
                    on b.bid_id = bt.bid_id
                left join riders r
                    on r.rider_id = b.rider_id
                where bt.user_id = :user_id
                order by
                    bt.created_at,
                    bt.transaction_id
            ',
            [':user_id' => $userId]
        );
    }

    public function getBalance(int $userId): ?int
    {
        $result = $this->db->queryOne(
            'select balance from users where user_id = :user_id',
            ['user_id' => $userId]
        );

        if ($result === null) {
            return null;
        }

        return (int)$result['balance'];
    }

    public function hasBids(int $userId): bool
    {
        $result = $this->db->queryOne(
            'select 1 from bids where user_id = :user_id limit 1',
            ['user_id' => $userId]
        );

        return $result !== null;
    }


    public function adjustBalance(int $userId, int $balance): bool
    {
        if ($balance < 0) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $user = $this->db->queryOne(
                '
                    select balance
                    from users
                    where user_id = :user_id
                ',
                ['user_id' => $userId]
            );

            if ($user === null) {
                $this->db->rollBack();
                return false;
            }

            $currentBalance = (int)$user['balance'];
            $adjustment = $balance - $currentBalance;

            /*
            * Nothing has changed, so there is nothing to record.
            */
            if ($adjustment === 0) {
                $this->db->rollBack();
                return true;
            }

            $this->db->execute(
                '
                    update users
                    set balance = :balance
                    where user_id = :user_id
                ',
                [
                    ':balance' => $balance,
                    ':user_id' => $userId,
                ]
            );

            $this->db->execute(
                '
                    insert into balance_transactions (
                        user_id,
                        transaction_type,
                        amount
                    )
                    values (
                        :user_id,
                        \'admin_adjustment\',
                        :amount
                    )
                ',
                [
                    ':user_id' => $userId,
                    ':amount' => $adjustment,
                ]
            );

            $this->db->commit();

            $this->logger->info(
                'User balance adjusted.',
                [
                    'user_id' => $userId,
                    'old_balance' => $currentBalance,
                    'new_balance' => $balance,
                    'adjustment' => $adjustment,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();

            $this->logger->error(
                'User balance adjustment failed: ' . $e->getMessage(),
                ['user_id' => $userId]
            );

            return false;
        }
    }

    public function getLadder(?int $limit = null): array
    {
        $sql = '
            select
                user_id,
                username,
                balance
            from users
            where approved_at is not null
            and disabled_at is null
            order by balance desc, username
        ';

        if ($limit !== null) {
            $sql .= ' limit ' . (int)$limit;
        }

        return $this->db->query($sql);
    }

    public function create(int $userId): bool
    {
        try {
            $this->db->execute(
                '
                update users
                set balance = 20
                where user_id = :user_id
            ',
                [
                    ':user_id' => $userId,
                ]
            );

            $this->db->execute(
                '
                insert into balance_transactions (
                    user_id,
                    transaction_type,
                    amount
                )
                values (
                    :user_id,
                    \'opening_balance\',
                    20
                )
            ',
                [
                    ':user_id' => $userId,
                ]
            );

            return true;
        } catch (\PDOException $e) {
            $this->logger->error(
                'Player creation failed: ' . $e->getMessage(),
                ['user_id' => $userId]
            );

            return false;
        }
    }
}
