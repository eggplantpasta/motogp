
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
}
