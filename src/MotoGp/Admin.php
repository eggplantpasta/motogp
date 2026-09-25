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

        $eventReadyToResolveBids =
            $this->getEventReadyToResolveBids();

        $eventAwaitingResults =
            $this->getEventAwaitingResults();

        $eventReadyToSettlePayouts =
            $this->getEventReadyToSettlePayouts();

        $hasEventReadyToResolveBids =
            $eventReadyToResolveBids !== null;

        $hasEventAwaitingResults =
            $eventAwaitingResults !== null;

        $hasEventReadyToSettlePayouts =
            $eventReadyToSettlePayouts !== null;

        return [
            'pendingUserCount' => $pendingUserCount,
            'hasPendingUsers' => $hasPendingUsers,

            'stalePendingUserCount' => $stalePendingUserCount,
            'hasStalePendingUsers' => $hasStalePendingUsers,

            'openBiddingEvent' => $openBiddingEvent,
            'noOpenBidding' => $noOpenBidding,

            'eventReadyToResolveBids' => $eventReadyToResolveBids,
            'hasEventReadyToResolveBids' => $hasEventReadyToResolveBids,

            'eventAwaitingResults' => $eventAwaitingResults,
            'hasEventAwaitingResults' => $hasEventAwaitingResults,

            'eventReadyToSettlePayouts' => $eventReadyToSettlePayouts,
            'hasEventReadyToSettlePayouts' => $hasEventReadyToSettlePayouts,

            'hasAdminTasks' =>
                $hasPendingUsers
                || $hasStalePendingUsers
                || $noOpenBidding
                || $hasEventReadyToResolveBids
                || $hasEventAwaitingResults
                || $hasEventReadyToSettlePayouts

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

    private function getEventReadyToResolveBids(): ?array
    {
        return $this->db->queryOne(
            '
            select
                e.event_id,
                e.name,
                e.start_date
            from events e
            where e.bids_open = 0
            and e.bids_resolved_at is null
            and exists (
                select 1
                from bids b
                where b.event_id = e.event_id
            )
            order by e.start_date
            limit 1
        '
        );
    }

    private function getEventAwaitingResults(): ?array
    {
        return $this->db->queryOne(
            '
            select
                e.event_id,
                e.name,
                e.start_date
            from events e
            where e.bids_resolved_at is not null
            and e.payouts_settled_at is null
            and exists (
                select 1
                from bids b
                where b.event_id = e.event_id
                and b.won = 1
                and not exists (
                    select 1
                    from results r
                    where r.event_id = b.event_id
                    and r.rider_id = b.rider_id
                )
            )
            order by e.start_date
            limit 1
        '
        );
    }

    private function getEventReadyToSettlePayouts(): ?array
    {
        return $this->db->queryOne(
            '
            select
                e.event_id,
                e.name,
                e.start_date
            from events e
            where e.bids_resolved_at is not null
            and e.payouts_settled_at is null
            and exists (
                select 1
                from bids b
                where b.event_id = e.event_id
                and b.won = 1
            )
            and not exists (
                select 1
                from bids b
                where b.event_id = e.event_id
                and b.won = 1
                and not exists (
                    select 1
                    from results r
                    where r.event_id = b.event_id
                    and r.rider_id = b.rider_id
                )
            )
            order by e.start_date
            limit 1
        '
        );
    }
}
