
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


    public function testTiedOwnersShareCombinedPayoutPercentages(): void
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

        // Alice and Bob share first and second payout positions.
        $this->assertSame(1, $payouts[0]['user_id']);
        $this->assertSame(20.5, $payouts[0]['percentage']);
        $this->assertSame(7, $payouts[0]['payout']);

        $this->assertSame(2, $payouts[1]['user_id']);
        $this->assertSame(20.5, $payouts[1]['percentage']);
        $this->assertSame(7, $payouts[1]['payout']);

        // Charlie receives the third payout percentage.
        $this->assertSame(3, $payouts[2]['user_id']);
        $this->assertSame(15.0, (float)$payouts[2]['percentage']);
        $this->assertSame(5, $payouts[2]['payout']);
    }


}
