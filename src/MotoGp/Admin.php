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

        $hasPendingUsers = $pendingUserCount > 0;
        $hasStalePendingUsers = $stalePendingUserCount > 0;

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

            'eventReadyToResolveBids' => $eventReadyToResolveBids,
            'hasEventReadyToResolveBids' => $hasEventReadyToResolveBids,

            'eventAwaitingResults' => $eventAwaitingResults,
            'hasEventAwaitingResults' => $hasEventAwaitingResults,

            'eventReadyToSettlePayouts' => $eventReadyToSettlePayouts,
            'hasEventReadyToSettlePayouts' => $hasEventReadyToSettlePayouts,

            'hasAdminTasks' =>
                $hasPendingUsers
                || $hasStalePendingUsers
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
                and not exists (
                    select 1
                    from events earlier
                    where (
                        earlier.start_date < e.start_date
                        or (
                            earlier.start_date = e.start_date
                            and earlier.event_id < e.event_id
                        )
                    )
                    and earlier.payouts_settled_at is null
                    and exists (
                        select 1
                        from bids earlier_bid
                        where earlier_bid.event_id = earlier.event_id
                    )
                )
                order by e.start_date, e.event_id
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
