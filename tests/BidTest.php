
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


}
