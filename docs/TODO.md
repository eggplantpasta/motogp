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

- [ ] Add a unique constraint/index for `users.username`.
- [ ] Add a unique constraint/index for `users.email`.
- [ ] Add an approval field to `users`, e.g. `approved_at datetime null`.
- [ ] Consider adding account status fields if useful, e.g. `disabled_at datetime null`.
- [ ] Decide the expiry period for unapproved accounts.
- [ ] Update registration validation so duplicate email addresses are rejected cleanly.
- [ ] Update username validation so account editing does not treat the current user's own username as a duplicate.
- [ ] Add email uniqueness validation when editing an account.
- [ ] Make database-level uniqueness errors return useful form errors rather than generic failures.

## Phase 2 — Registration and login approval flow

- [ ] Keep public self-registration available.
- [ ] New registrations should default to **unapproved**.
- [ ] Prevent unapproved users from logging in.
- [ ] Display a clear login message such as "Your account is awaiting approval."
- [ ] Distinguish an unapproved account from an invalid username/password without exposing unnecessary account information.
- [ ] Decide whether disabled accounts should receive a separate message.
- [ ] Regenerate the PHP session ID after successful login.
- [ ] Review logout behaviour and session-cookie cleanup.

## Phase 3 — User account editing

- [ ] Make Edit Account genuinely optional-field based:
  - blank username = leave unchanged
  - blank email = leave unchanged
  - blank password = leave unchanged
- [ ] Allow username changes.
- [ ] Allow email changes.
- [ ] Allow password changes independently of username/email.
- [ ] Update the session copy of the user immediately after username/email changes.
- [ ] Only redirect after a successful database update.
- [ ] Surface database/update errors on the form.
- [ ] Remove the `Utility::dump($data)` debug output from `public/user/edit-account.php`.
- [ ] Fix the password label markup in `templates/user/edit-account.mustache`.
- [ ] Replace the current reset-style Cancel control with a link/button back to the account page.
- [ ] Add CSRF protection to account-changing forms.

## Phase 4 — Admin user-management page

Create an admin-only user management area under `public/admin`.

### User list

- [ ] Add `/admin/users.php`.
- [ ] Add a corresponding Mustache template.
- [ ] Restrict access using `User::isAdmin()`.
- [ ] Show:
  - username
  - email
  - approval status
  - admin status
  - balance
  - registration date
  - account age
- [ ] Make pending/unapproved users easy to identify.
- [ ] Optionally provide filters for:
  - pending
  - approved
  - disabled
  - admins

### Admin actions

- [ ] Approve a pending user.
- [ ] Revoke/disable access for an approved user.
- [ ] Promote a user to admin.
- [ ] Remove admin status.
- [ ] Edit a user's balance.
- [ ] Delete a user.
- [ ] Require confirmation for destructive actions.
- [ ] Prevent an administrator from accidentally deleting or demoting the final admin account.
- [ ] Decide whether admins should be able to edit username/email directly.

### Security

- [ ] Require POST for all state-changing admin actions.
- [ ] Add CSRF protection to all admin actions.
- [ ] Verify admin permission server-side for every admin endpoint.
- [ ] Never rely only on hiding admin controls in templates.
- [ ] Log important admin actions.

## Phase 5 — Expiry of unapproved accounts

- [ ] Choose the unapproved-account lifetime.
- [ ] Implement a cleanup mechanism for expired unapproved users.
- [ ] Ensure approved users are never affected by this cleanup.
- [ ] Decide whether deletion should be immediate or preceded by a disabled/expired state.
- [ ] Log expired-account deletion.
- [ ] Document how cleanup runs in production.

Possible implementation:

- a small PHP CLI script under `bin/`
- executed periodically by cron/systemd timer
- deletes users where:
  - `approved_at IS NULL`
  - registration date is older than the configured expiry period
  - account is not an administrator

## Phase 6 — Database migration / deployment

The project currently uses `db/schema.sql`, but existing deployed databases will need schema changes applied safely.

- [ ] Decide on a lightweight migration approach using plain SQL/scripts where practical; do not add a migration framework unless it becomes necessary.
- [ ] Create migration SQL for user approval/status and unique constraints.
- [ ] Check existing production data for duplicate usernames/emails before adding constraints.
- [ ] Document migration/deployment steps.
- [ ] Ensure `db/reset.sh` still produces the correct development schema.

## Phase 7 — Tests

Add focused tests around user behaviour before extending the rest of the application.

Prefer a lightweight test approach that does not introduce a large testing framework unless the project grows to justify one. Simple PHP test scripts and disposable SQLite databases are acceptable.

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
- [ ] Delete expired pending users.
- [ ] Ensure approved users are not removed by expiry cleanup.

---

## Suggested implementation order

1. Database fields and uniqueness.
2. Registration validation.
3. Approval-aware login.
4. Fix self-service account editing.
5. Admin user list.
6. Admin approval and account actions.
7. Expiry cleanup job.
8. Security hardening and CSRF.
9. Lightweight automated tests.
10. Deployment/migration documentation.

---

## Decisions still required

- [ ] How long should an unapproved account remain before expiry?
- [ ] Should expired pending accounts be permanently deleted, or first marked expired/disabled?
- [ ] Should an administrator be able to manually create accounts?
- [ ] Should administrators be able to edit another user's username/email?
- [ ] Should disabling an account preserve its bids/history indefinitely?
- [ ] Should account deletion be blocked when the user has historical bids/results that should be retained?

