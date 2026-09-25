<?php

namespace MotoGp;

use Webmin\Database;

class Admin
{
    public function __construct(
        private Database $db
    ) {
    }

    public function getDashboard(int $pendingUserExpiryDays): array
    {
        if ($pendingUserExpiryDays < 1) {
            throw new \InvalidArgumentException(
                'Pending user expiry must be at least 1 day.'
            );
        }

        $pendingUserCount = $this->getPendingUserCount();

        $stalePendingUserCount =
            $this->getStalePendingUserCount($pendingUserExpiryDays);

        $openBiddingEvent = $this->getOpenBiddingEvent();

        $hasPendingUsers = $pendingUserCount > 0;
        $hasStalePendingUsers = $stalePendingUserCount > 0;
        $noOpenBidding = $openBiddingEvent === null;

        return [
            'pendingUserCount' => $pendingUserCount,
            'hasPendingUsers' => $hasPendingUsers,

            'stalePendingUserCount' => $stalePendingUserCount,
            'hasStalePendingUsers' => $hasStalePendingUsers,

            'openBiddingEvent' => $openBiddingEvent,
            'noOpenBidding' => $noOpenBidding,

            'hasAdminTasks' =>
                $hasPendingUsers
                || $hasStalePendingUsers
                || $noOpenBidding,
        ];
    }

    private function getPendingUserCount(): int
    {
        $result = $this->db->queryOne(
            '
                select count(*) as count
                from users
                where approved_at is null
            '
        );

        return (int)$result['count'];
    }

    private function getStalePendingUserCount(int $expiryDays): int
    {
        $result = $this->db->queryOne(
            '
                select count(*) as count
                from users
                where approved_at is null
                and created_at < datetime(\'now\', :expiry)
            ',
            [
                'expiry' => "-{$expiryDays} days",
            ]
        );

        return (int)$result['count'];
    }

    private function getOpenBiddingEvent(): ?array
    {
        return $this->db->queryOne(
            '
                select
                    event_id,
                    name,
                    start_date
                from events
                where bids_open = 1
                limit 1
            '
        );
    }
}
