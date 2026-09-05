# MotoGP Project TODO

## Development constraints

All work in this TODO should follow the project's deliberately simple PHP architecture.

- Prefer PHP standard library functionality and existing project classes over new Composer packages.
- Do not introduce a framework, ORM, router, dependency injection container, authentication framework, migration framework, or test framework unless there is a strong and demonstrated need.
- Where practical, keep each page as:
  - one PHP file under `public/`
  - one corresponding Mustache template under `templates/`
- Keep request handling and page flow easy to follow from the PHP file.
- Put genuinely reusable behaviour in small classes under `src/`.
- Prefer explicit SQL and parameterised queries over database abstraction layers.
- Prefer small, local changes over broad refactoring.
- Use server-rendered HTML by default and JavaScript only where it adds clear value.
- Security requirements still apply; simplicity must not mean weaker authentication, validation, session handling, CSRF protection, or SQL safety.
- If two implementations are otherwise reasonable, prefer the one that can be understood by reading the relevant PHP file and template from top to bottom.

---

## User Administration

### Agreed behaviour

- New users may register themselves, but **must be approved by an administrator before they can log in**.
- Email addresses must be **unique per account**.
- Unapproved accounts should **expire and be deleted after a defined period**.
- Administrators need a user-management page for approving and maintaining accounts.
- Existing authentication should remain simple and fit the current PHP + SQLite + Mustache architecture.

---

## Phase 1 — Account data and validation

- [x] Add a unique constraint/index for `users.username`.
- [x] Add a unique constraint/index for `users.email`.
- [x] Add an approval field to `users`, e.g. `approved_at datetime null`.
- [x] Consider adding account status fields if useful, e.g. `disabled_at datetime null`.
- [x] Decide the expiry period for unapproved accounts.
- [x] Update registration validation so duplicate email addresses are rejected cleanly.
- [x] Update username validation so account editing does not treat the current user's own username as a duplicate.
- [x] Add email uniqueness validation when editing an account.
- [x] Make database-level uniqueness errors return useful form errors rather than generic failures.

## Phase 2 — Registration and login approval flow

- [x] Keep public self-registration available.
- [x] New registrations should default to **unapproved**.
- [x] Prevent unapproved users from logging in.
- [x] Display a clear login message such as "Your account is awaiting approval."
- [x] Distinguish an unapproved account from an invalid username/password without exposing unnecessary account information.
- [x] Decide whether disabled accounts should receive a separate message.
- [x] Regenerate the PHP session ID after successful login.
- [x] Review logout behaviour and session-cookie cleanup.

## Phase 3 — User account editing

- [x] Make Edit Account genuinely optional-field based:
  - blank username = leave unchanged
  - blank email = leave unchanged
  - blank password = leave unchanged
- [x] Allow username changes.
- [x] Allow email changes.
- [x] Allow password changes independently of username/email.
- [x] Update the session copy of the user immediately after username/email changes.
- [x] Only redirect after a successful database update.
- [x] Surface database/update errors on the form.
- [x] Remove the `Utility::dump($data)` debug output from `public/user/edit-account.php`.
- [x] Fix the password label markup in `templates/user/edit-account.mustache`.
- [x] Replace the current reset-style Cancel control with a link/button back to the account page.
- [x] Add CSRF protection to account-changing forms.

## Phase 4 — Admin user-management page

Create an admin-only user management area under `public/admin`.

### User list

- [x] Add `/admin/users.php`.
- [x] Add a corresponding Mustache template.
- [x] Restrict access using `User::isAdmin()`.
- [x] Show:
  - username
  - email
  - approval status
  - admin status
  - balance
  - registration date
- [x] Make pending/unapproved users easy to identify.

### Admin actions

- [x] Approve a pending user.
- [x] Revoke/disable access for an approved user.
- [x] Promote a user to admin.
- [x] Remove admin status.
- [x] Edit a user's balance.
- [x] Prevent an administrator from accidentally deleting or demoting the final admin account.
- [x] Admins cannot edit username/email; users manage these themselves

### User deletion and cleanup

- [x] Enable SQLite foreign key enforcement in the application connection.
- [x] Review all foreign keys referencing `users`.
- [x] Add `ON DELETE CASCADE` where dependent data should be removed with a user.
- [x] Add server-side user deletion.
- [x] Prevent deletion of the final active administrator.
- [x] Detect whether a user has associated data.
- [x] Add Delete action to admin user management.
- [x] Require confirmation before deleting a user.
- [x] Show stronger confirmation when the user has associated data.
- [x] Hard-delete unapproved accounts older than 7 days.
- [ ] Log expired-account deletion.
- [ ] Document how the cleanup script is scheduled in production.

### Security

- [x] Require POST for all state-changing admin actions.
- [x] Add CSRF protection to all admin actions.
- [x] Verify admin permission server-side for every admin endpoint.
- [x] Never rely only on hiding admin controls in templates.
- [ ] Log important admin actions.

## Phase 5 — Admin event management

Keep normal event pages user-facing. Administrative event operations live
under `/admin/` and must not substantially change the normal pages based on
whether the current user is an administrator.

- [x] Add an admin landing page and admin navigation.
- [ ] Add `/admin/events.php`.
- [ ] Add corresponding `templates/admin/events.mustache`.
- [ ] Show existing events in the admin event list.
- [ ] Link each event to `/admin/edit-event.php`.
- [ ] Review the existing event-editing page and move any remaining
      administrative event behaviour out of normal user-facing pages.
- [ ] Add event creation.
- [ ] Add event deletion if required.
- [ ] Require POST and CSRF protection for state-changing event actions.
- [ ] Verify admin permission server-side for every event-management endpoint.

## Phase 6 — Database migration / deployment

The project currently uses `db/schema.sql`, but existing deployed databases
will need schema changes applied safely.

- [ ] Decide on a lightweight migration approach using plain SQL/scripts where
      practical; do not add a migration framework unless it becomes necessary.
- [ ] Create migration SQL for changes made to the user schema and foreign keys.
- [ ] Check existing production data for duplicate usernames/emails before
      adding constraints.
- [ ] Document migration/deployment steps.
- [x] Ensure `db/reset.sh` still produces the correct development schema.

## Phase 7 — Tests

Add focused tests around user and administration behaviour.

Prefer a lightweight test approach that does not introduce a large testing
framework unless the project grows to justify one. Simple PHP test scripts and
disposable SQLite databases are acceptable.

- [ ] Register a new user.
- [ ] Reject duplicate username.
- [ ] Reject duplicate email.
- [ ] Reject invalid email.
- [ ] Reject weak password.
- [ ] Prevent unapproved login.
- [ ] Allow approved login.
- [ ] Reject incorrect password.
- [ ] Edit username only.
- [ ] Edit email only.
- [ ] Edit password only.
- [ ] Leave blank edit fields unchanged.
- [ ] Prevent duplicate username/email during editing.
- [ ] Verify admin-only endpoints reject normal users.
- [ ] Approve user.
- [ ] Disable/revoke user.
- [ ] Change balance.
- [ ] Promote/demote admin.
- [ ] Prevent demotion/deletion of final active administrator.
- [ ] Delete user and cascade their bids.
- [ ] Delete expired pending users.
- [ ] Ensure approved users are not removed by expiry cleanup.

---

## Next work

1. Admin event list.
2. Event creation/editing/deletion.
3. Finish admin-action logging and production cleanup scheduling.
4. Lightweight automated tests.
5. Database migration and deployment process.
