<?php

namespace MotoGp;

use Psr\Log\LoggerInterface;
use Webmin\Database;
use Webmin\User;

class Scheduler
{
    public function __construct(
        private Database $db,
        private LoggerInterface $logger,
        private array $config
    ) {
    }

    public function run(): void
    {
        $this->closeBidding();
        $this->cleanupPendingUsers();
    }

    private function closeBidding(): void
    {
        $eventModel = new Event($this->db);

        $count = $eventModel->closeBiddingForDueEvents(
            date('Y-m-d')
        );

        if ($count > 0) {
            $this->logger->info(
                "Scheduler closed bidding for {$count} event(s)."
            );
        }
    }

    private function cleanupPendingUsers(): void
    {
        $userModel = new User($this->db, $this->logger);

        $expiryDays = (int)(
            $this->config['app']['pending_user_expiry_days'] ?? 7
        );

        $count = $userModel->deleteExpiredPendingUsers($expiryDays);

        if ($count > 0) {
            $this->logger->info(
                "Scheduler deleted {$count} expired pending user(s)."
            );
        }
    }
}
