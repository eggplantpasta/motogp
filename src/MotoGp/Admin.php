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

    /*
    * Administrative race states:
    *
    * open             Bidding is open.
    * closed           Bidding is closed but no action is currently due.
    * ready_to_resolve Bids can now be resolved.
    * results_pending  Winning riders are missing results.
    * ready_to_settle  Results are complete and payouts can be settled.
    * complete         No further administration is required.
    *
    * Results may be entered before bids are resolved, so these states do
    * not form a strictly linear sequence.
    */
    public function getEventAdminStates(): array
    {
        $events = $this->db->query(
            '
            select
                e.event_id,
                e.start_date,
                e.bids_open,
                e.bids_resolved_at,
                e.payouts_settled_at,
                exists (
                    select 1
                    from bids b
                    where b.event_id = e.event_id
                ) as has_bids,
                exists (
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
                ) as has_unsettled_earlier_event,
                exists (
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
                ) as has_missing_results
            from events e
            order by e.start_date, e.event_id
        '
        );

        $states = [];

        foreach ($events as $event) {
            $states[(int)$event['event_id']] =
                $this->getEventAdminState($event);
        }

        return $states;
    }

    private function getEventAdminState(array $event): string
    {
        $pastOrToday =
            $event['start_date'] <= date('Y-m-d');

        if ($event['payouts_settled_at'] !== null) {
            return 'complete';
        }

        if (
            $pastOrToday
            && !(bool)$event['has_bids']
        ) {
            return 'complete';
        }

        if ((bool)$event['bids_open']) {
            return 'open';
        }

        if ($event['bids_resolved_at'] !== null) {
            if ((bool)$event['has_missing_results']) {
                return 'results_pending';
            }

            return 'ready_to_settle';
        }

        if (
            $pastOrToday
            && (bool)$event['has_bids']
            && !(bool)$event['has_unsettled_earlier_event']
        ) {
            return 'ready_to_resolve';
        }

        return 'closed';
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
