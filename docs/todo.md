# TODO

Deferred work and ideas that are worth retaining but are not part of the
current task.

## Bidding lockout

Improve protection against bidding remaining open too close to or after a race.

- Add a configurable warning period before bidding should close, for example:
  `bidding_close_warning_hours = 24`.
- Add an admin dashboard warning when bidding is still open inside the warning
  period.
- Define a hard bidding cutoff based on the event date.
- Enforce the cutoff server-side when placing or changing bids, regardless of
  the stored `bids_open` value.
- Consider midnight at the start of race day as the default hard cutoff.
- Do not rely on automatically changing `bids_open`; calculate whether bidding
  is effectively allowed when validating a bid.
- Consider an admin exception warning if `bids_open` remains set after the
  hard cutoff.

## Admin dashboard

Possible future refinements after the current dashboard has been used.

- Distinguish "no race open for bidding" from "no upcoming races configured".
- Avoid permanent "no race open" warnings during the off-season.
- Review dashboard wording and usefulness after real-world use.

## Later cleanup

- Review deferred database indexes if the database grows enough to warrant
  them.
