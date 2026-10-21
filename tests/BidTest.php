<?php

use MotoGp\Bid;
use PHPUnit\Framework\TestCase;
use Webmin\Database;

final class BidTest extends TestCase
{
    private Database $db;

    protected function setUp(): void
    {
        $this->db = new Database('sqlite::memory:');

        $schema = file_get_contents(
            __DIR__ . '/../db/schema.sql'
        );

        $this->db->getConnection()->exec($schema);
    }

    public function testBidsCannotBeResolvedWhileOpen(): void
    {
        $this->db->execute(
            "
                insert into events (
                    event_id,
                    start_date,
                    name,
                    bids_open
                )
                values (1, '2026-10-19', 'Test Race', 1)
            "
        );

        $bidModel = new Bid($this->db);

        $this->assertFalse($bidModel->resolveBids(1));

        $event = $this->db->queryOne(
            'select bids_resolved_at from events where event_id = 1'
        );

        $this->assertNull($event['bids_resolved_at']);
    }


    public function testResolvingBidsDeductsOnlyWinningBids(): void
    {
        $this->db->execute(
            "
                insert into users (user_id, username, email, password)
                values
                    (1, 'Alice', 'alice@example.com', 'test'),
                    (2, 'Bob', 'bob@example.com', 'test')
            "
        );

        $this->db->execute(
            "
                insert into riders (rider_id, race_number, name)
                values (1, 10, 'Test Rider')
            "
        );

        $this->db->execute(
            "
                insert into events (event_id, start_date, name, bids_open)
                values (1, '2026-10-19', 'Test Race', 0)
            "
        );

        $this->db->execute(
            "
                insert into bids (
                    user_id, rider_id, event_id, bid_number, amount
                )
                values
                    (1, 1, 1, 1, 8),
                    (2, 1, 1, 1, 5)
            "
        );

        $bidModel = new Bid($this->db);

        $this->assertTrue($bidModel->resolveBids(1));

        $users = $this->db->query(
            'select user_id, balance from users order by user_id'
        );

        $this->assertSame(12, (int)$users[0]['balance']);
        $this->assertSame(20, (int)$users[1]['balance']);

        $bids = $this->db->query(
            'select user_id, won from bids order by user_id'
        );

        $this->assertSame(1, (int)$bids[0]['won']);
        $this->assertSame(0, (int)$bids[1]['won']);

        $transactions = $this->db->query(
            '
                select user_id, transaction_type, amount
                from balance_transactions
                order by transaction_id
            '
        );

        $this->assertCount(1, $transactions);
        $this->assertSame(1, (int)$transactions[0]['user_id']);
        $this->assertSame('winning_bid', $transactions[0]['transaction_type']);
        $this->assertSame(-8, (int)$transactions[0]['amount']);

        // Resolving again must not change balances or transactions.
        $this->assertFalse($bidModel->resolveBids(1));

        $this->assertSame($users, $this->db->query(
            'select user_id, balance from users order by user_id'
        ));

        $this->assertSame($transactions, $this->db->query(
            '
                select user_id, transaction_type, amount
                from balance_transactions
                order by transaction_id
            '
        ));
    }


    public function testTiedHighestBidsBothWin(): void
    {
        $this->db->execute(
            "
                insert into users (user_id, username, email, password)
                values
                    (1, 'Alice', 'alice@example.com', 'test'),
                    (2, 'Bob', 'bob@example.com', 'test'),
                    (3, 'Charlie', 'charlie@example.com', 'test')
            "
        );

        $this->db->execute(
            "
                insert into riders (rider_id, race_number, name)
                values (1, 10, 'Test Rider')
            "
        );

        $this->db->execute(
            "
                insert into events (event_id, start_date, name, bids_open)
                values (1, '2026-10-19', 'Test Race', 0)
            "
        );

        $this->db->execute(
            "
                insert into bids (
                    user_id, rider_id, event_id, bid_number, amount
                )
                values
                    (1, 1, 1, 1, 8),
                    (2, 1, 1, 1, 8),
                    (3, 1, 1, 1, 5)
            "
        );

        $bidModel = new Bid($this->db);

        $this->assertTrue($bidModel->resolveBids(1));

        $bids = $this->db->query(
            'select user_id, won from bids order by user_id'
        );

        $this->assertSame([1, 1, 0], array_map(
            static fn (array $bid): int => (int)$bid['won'],
            $bids
        ));

        $users = $this->db->query(
            'select balance from users order by user_id'
        );

        $this->assertSame([12, 12, 20], array_map(
            static fn (array $user): int => (int)$user['balance'],
            $users
        ));

        $transactions = $this->db->query(
            "
                select user_id, amount
                from balance_transactions
                where transaction_type = 'winning_bid'
                order by user_id
            "
        );

        $this->assertSame(
            [
                ['user_id' => 1, 'amount' => -8],
                ['user_id' => 2, 'amount' => -8],
            ],
            array_map(
                static fn (array $transaction): array => [
                    'user_id' => (int)$transaction['user_id'],
                    'amount' => (int)$transaction['amount'],
                ],
                $transactions
            )
        );
    }


    public function testBidsAreReducedProportionatelyToAvailableBalance(): void
    {
        $this->db->execute(
            "
                insert into users (
                    user_id, username, email, password, balance
                )
                values (1, 'Alice', 'alice@example.com', 'test', 10)
            "
        );

        $this->db->execute(
            "
                insert into riders (rider_id, race_number, name)
                values
                    (1, 10, 'Rider A'),
                    (2, 20, 'Rider B')
            "
        );

        $this->db->execute(
            "
                insert into events (event_id, start_date, name, bids_open)
                values (1, '2026-10-19', 'Test Race', 0)
            "
        );

        $this->db->execute(
            "
                insert into bids (
                    user_id, rider_id, event_id, bid_number, amount
                )
                values
                    (1, 1, 1, 1, 11),
                    (1, 2, 1, 2, 7)
            "
        );

        $bidModel = new Bid($this->db);

        $this->assertTrue($bidModel->resolveBids(1));

        $bids = $this->db->query(
            'select amount, won from bids order by bid_number'
        );

        $this->assertSame([6, 3], array_map(
            static fn (array $bid): int => (int)$bid['amount'],
            $bids
        ));

        $this->assertSame([1, 1], array_map(
            static fn (array $bid): int => (int)$bid['won'],
            $bids
        ));

        $user = $this->db->queryOne(
            'select balance from users where user_id = 1'
        );

        $this->assertSame(1, (int)$user['balance']);

        $transactions = $this->db->query(
            "
                select amount
                from balance_transactions
                where transaction_type = 'winning_bid'
                order by bid_id
            "
        );

        $this->assertSame([-6, -3], array_map(
            static fn (array $transaction): int => (int)$transaction['amount'],
            $transactions
        ));
    }


    public function testLaterRaceCannotResolveUntilEarlierRaceIsSettled(): void
    {
        $this->db->execute(
            "
                insert into users (user_id, username, email, password)
                values (1, 'Alice', 'alice@example.com', 'test')
            "
        );

        $this->db->execute(
            "
                insert into riders (rider_id, race_number, name)
                values (1, 10, 'Test Rider')
            "
        );

        $this->db->execute(
            "
                insert into events (
                    event_id, start_date, name, bids_open
                )
                values
                    (1, '2026-10-01', 'Race 1', 0),
                    (2, '2026-10-08', 'Race 2', 0)
            "
        );

        $this->db->execute(
            "
                insert into bids (
                    user_id, rider_id, event_id, bid_number, amount
                )
                values
                    (1, 1, 1, 1, 5),
                    (1, 1, 2, 1, 8)
            "
        );

        $bidModel = new Bid($this->db);

        // Race 1 has bids but its payouts are not settled.
        $this->assertTrue($bidModel->hasUnsettledEarlierEvents(2));
        $this->assertFalse($bidModel->resolveBids(2));

        $event = $this->db->queryOne(
            'select bids_resolved_at from events where event_id = 2'
        );

        $this->assertNull($event['bids_resolved_at']);

        $user = $this->db->queryOne(
            'select balance from users where user_id = 1'
        );

        $this->assertSame(20, (int)$user['balance']);

        // Mark Race 1 as settled.
        $this->db->execute(
            "
                update events
                set payouts_settled_at = current_timestamp
                where event_id = 1
            "
        );

        $this->assertFalse($bidModel->hasUnsettledEarlierEvents(2));
        $this->assertTrue($bidModel->resolveBids(2));

        $user = $this->db->queryOne(
            'select balance from users where user_id = 1'
        );

        $this->assertSame(12, (int)$user['balance']);
    }


    public function testCalculatePayoutsUsesPoolAndFinishPositions(): void
    {
        $this->db->execute(
            "
                insert into users (user_id, username, email, password)
                values
                    (1, 'Alice', 'alice@example.com', 'test'),
                    (2, 'Bob', 'bob@example.com', 'test')
            "
        );

        $this->db->execute(
            "
                insert into riders (rider_id, race_number, name)
                values
                    (1, 10, 'Rider A'),
                    (2, 20, 'Rider B')
            "
        );

        $this->db->execute(
            "
                insert into events (event_id, start_date, name, bids_open)
                values (1, '2026-10-19', 'Test Race', 0)
            "
        );

        $this->db->execute(
            "
                insert into bids (
                    user_id, rider_id, event_id,
                    bid_number, amount, won
                )
                values
                    (1, 1, 1, 1, 12, 1),
                    (2, 2, 1, 1, 8, 1)
            "
        );

        $this->db->execute(
            "
                insert into results (event_id, rider_id, position)
                values
                    (1, 1, 1),
                    (1, 2, 2)
            "
        );

        $bidModel = new Bid($this->db);
        $calculation = $bidModel->calculatePayouts(1);

        $this->assertSame(22, $calculation['pool']);
        $this->assertSame(2, $calculation['winning_bids']);

        $payouts = $calculation['payouts'];

        $this->assertCount(2, $payouts);

        $this->assertSame(1, $payouts[0]['user_id']);
        $this->assertSame(1, $payouts[0]['finish_position']);
        $this->assertSame(23, $payouts[0]['percentage']);
        $this->assertSame(6, $payouts[0]['payout']);

        $this->assertSame(2, $payouts[1]['user_id']);
        $this->assertSame(2, $payouts[1]['finish_position']);
        $this->assertSame(18, $payouts[1]['percentage']);
        $this->assertSame(4, $payouts[1]['payout']);
    }


    public function testPayoutsUseActualRacePositions(): void
    {
        $this->db->execute(
            "
                insert into users (user_id, username, email, password)
                values
                    (1, 'Alice', 'alice@example.com', 'test'),
                    (2, 'Bob', 'bob@example.com', 'test'),
                    (3, 'Charlie', 'charlie@example.com', 'test')
            "
        );

        $this->db->execute(
            "
                insert into riders (rider_id, race_number, name)
                values
                    (1, 10, 'Rider A'),
                    (2, 20, 'Rider B'),
                    (3, 30, 'Rider C')
            "
        );

        $this->db->execute(
            "
                insert into events (event_id, start_date, name, bids_open)
                values (1, '2026-10-19', 'Test Race', 0)
            "
        );

        $this->db->execute(
            "
                insert into bids (
                    user_id, rider_id, event_id,
                    bid_number, amount, won
                )
                values
                    (1, 1, 1, 1, 12, 1),
                    (2, 2, 1, 1, 8, 1),
                    (3, 3, 1, 1, 5, 1)
            "
        );

        $this->db->execute(
            "
                insert into results (event_id, rider_id, position)
                values
                    (1, 1, 4),
                    (1, 2, 8),
                    (1, 3, 9)
        "
        );

        $bidModel = new Bid($this->db);
        $calculation = $bidModel->calculatePayouts(1);

        $this->assertSame(28, $calculation['pool']);
        $this->assertSame(3, $calculation['winning_bids']);

        $payouts = $calculation['payouts'];

        // Only fourth and eighth place receive payouts.
        $this->assertCount(2, $payouts);

        $this->assertSame(4, $payouts[0]['finish_position']);
        $this->assertSame(12, $payouts[0]['percentage']);
        $this->assertSame(4, $payouts[0]['payout']);

        $this->assertSame(8, $payouts[1]['finish_position']);
        $this->assertSame(6, $payouts[1]['percentage']);
        $this->assertSame(2, $payouts[1]['payout']);

        // The unused pool is not redistributed.
        $this->assertSame(
            6,
            array_sum(array_column($payouts, 'payout'))
        );
    }

    public function testTiedOwnersReceiveFullPayoutPercentage(): void
    {
        $this->db->execute(
            "
                insert into users (user_id, username, email, password)
                values
                    (1, 'Alice', 'alice@example.com', 'test'),
                    (2, 'Bob', 'bob@example.com', 'test'),
                    (3, 'Charlie', 'charlie@example.com', 'test')
            "
        );

        $this->db->execute(
            "
                insert into riders (rider_id, race_number, name)
                values
                    (1, 10, 'Rider A'),
                    (2, 20, 'Rider B')
            "
        );

        $this->db->execute(
            "
                insert into events (event_id, start_date, name, bids_open)
                values (1, '2026-10-19', 'Test Race', 0)
            "
        );

        $this->db->execute(
            "
                insert into bids (
                    user_id, rider_id, event_id,
                    bid_number, amount, won
                )
                values
                    (1, 1, 1, 1, 10, 1),
                    (2, 1, 1, 1, 10, 1),
                    (3, 2, 1, 1, 8, 1)
            "
        );

        $this->db->execute(
            "
                insert into results (event_id, rider_id, position)
                values
                    (1, 1, 1),
                    (1, 2, 2)
            "
        );

        $bidModel = new Bid($this->db);
        $calculation = $bidModel->calculatePayouts(1);

        $this->assertSame(31, $calculation['pool']);
        $this->assertSame(3, $calculation['winning_bids']);

        $payouts = $calculation['payouts'];

        $this->assertCount(3, $payouts);

        // Alice and Bob each receive the full first-place percentage.
        $this->assertSame(1, $payouts[0]['user_id']);
        $this->assertSame(23, $payouts[0]['percentage']);
        $this->assertSame(8, $payouts[0]['payout']);

        $this->assertSame(2, $payouts[1]['user_id']);
        $this->assertSame(23, $payouts[1]['percentage']);
        $this->assertSame(8, $payouts[1]['payout']);

        // Charlie receives the second-place percentage.
        $this->assertSame(3, $payouts[2]['user_id']);
        $this->assertSame(18, $payouts[2]['percentage']);
        $this->assertSame(6, $payouts[2]['payout']);
    }



    public function testOnlyFirstEightPayoutPositionsReceivePoints(): void
    {
        $this->db->execute(
            "
            insert into events (event_id, start_date, name, bids_open)
            values (1, '2026-10-19', 'Test Race', 0)
        "
        );

        for ($position = 1; $position <= 9; $position++) {
            $this->db->execute(
                '
                    insert into users (
                        user_id, username, email, password
                    )
                    values (
                        :user_id, :username, :email, :password
                    )
                ',
                [
                    ':user_id' => $position,
                    ':username' => 'Player ' . $position,
                    ':email' => 'player' . $position . '@example.com',
                    ':password' => 'test',
                ]
            );

            $this->db->execute(
                '
                    insert into riders (rider_id, race_number, name)
                    values (:rider_id, :race_number, :name)
                ',
                [
                    ':rider_id' => $position,
                    ':race_number' => $position,
                    ':name' => 'Rider ' . $position,
                ]
            );

            $this->db->execute(
                '
                    insert into bids (
                        user_id, rider_id, event_id,
                        bid_number, amount, won
                    )
                    values (
                        :user_id, :rider_id, 1, 1, 10, 1
                    )
                ',
                [
                    ':user_id' => $position,
                    ':rider_id' => $position,
                ]
            );

            $this->db->execute(
                '
                insert into results (event_id, rider_id, position)
                values (1, :rider_id, :position)
            ',
                [
                    ':rider_id' => $position,
                    ':position' => $position,
                ]
            );
        }

        $bidModel = new Bid($this->db);
        $calculation = $bidModel->calculatePayouts(1);

        $this->assertSame(99, $calculation['pool']);
        $this->assertSame(9, $calculation['winning_bids']);

        $payouts = $calculation['payouts'];

        $this->assertCount(8, $payouts);

        $this->assertSame(
            [23, 18, 15, 12, 11, 8, 7, 6],
            array_map(
                static fn (array $payout): int => $payout['payout'],
                $payouts
            )
        );

        $this->assertSame(
            [1, 2, 3, 4, 5, 6, 7, 8],
            array_map(
                static fn (array $payout): int => $payout['finish_position'],
                $payouts
            )
        );
    }


    public function testSettlingPayoutsCreditsBalancesOnlyOnce(): void
    {
        $this->db->execute(
            "
                insert into users (
                    user_id, username, email, password, balance
                )
                values (1, 'Alice', 'alice@example.com', 'test', 10)
            "
        );

        $this->db->execute(
            "
                insert into events (
                    event_id, start_date, name,
                    bids_open, bids_resolved_at
                )
                values (
                    1, '2026-10-19', 'Test Race',
                    0, current_timestamp
                )
            "
        );

        // Eight classified results are required for settlement.
        for ($position = 1; $position <= 8; $position++) {
            $this->db->execute(
                '
                    insert into riders (rider_id, race_number, name)
                    values (:rider_id, :race_number, :name)
                ',
                [
                    ':rider_id' => $position,
                    ':race_number' => $position,
                    ':name' => 'Rider ' . $position,
                ]
            );

            $this->db->execute(
                '
                    insert into results (event_id, rider_id, position)
                    values (1, :rider_id, :position)
                ',
                [
                    ':rider_id' => $position,
                    ':position' => $position,
                ]
            );
        }

        $this->db->execute(
            "
                insert into bids (
                    user_id, rider_id, event_id,
                    bid_number, amount, won
                )
                values (1, 1, 1, 1, 10, 1)
            "
        );

        $bidModel = new Bid($this->db);

        $this->assertTrue($bidModel->settlePayouts(1));

        $user = $this->db->queryOne(
            'select balance from users where user_id = 1'
        );

        $this->assertSame(13, (int)$user['balance']);

        $transactions = $this->db->query(
            "
                select transaction_type, amount
                from balance_transactions
                where event_id = 1
                order by transaction_id
            "
        );

        $this->assertCount(1, $transactions);
        $this->assertSame('payout', $transactions[0]['transaction_type']);
        $this->assertSame(3, (int)$transactions[0]['amount']);

        $event = $this->db->queryOne(
            'select payouts_settled_at from events where event_id = 1'
        );

        $this->assertNotNull($event['payouts_settled_at']);

        // A second settlement must not credit points again.
        $this->assertFalse($bidModel->settlePayouts(1));

        $this->assertSame($user, $this->db->queryOne(
            'select balance from users where user_id = 1'
        ));

        $this->assertSame($transactions, $this->db->query(
            "
                select transaction_type, amount
                from balance_transactions
                where event_id = 1
                order by transaction_id
            "
        ));
    }

}
