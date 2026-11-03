# AI workflow log

How the author and Claude Code (model Claude Opus 5.5) worked together, stage by stage. This log is the source for the "Approach" section of the README.

## Stage 0: Inventory

**Proposed:** Claude checked the local tools and current package versions, read the kit, installer and Sail sources, and presented a setup plan.

**Found before any code was written:** The kit ships Vite+ instead of ESLint and Prettier, its last tag is outdated, and ports 80 and 5432 are taken.

**Decided by the author:** Keep Vite+, keep only registration from the auth features, and use ports 8000, 5173 and 5434.

**AI mistake:** The plan ran `create-project` and then `install:features` as a separate step. Claude had missed that `create-project` already runs that command. The author executed the plan's commands and hit the problem: all features stayed and the command was gone.

**Correction:** The folder was emptied with the author's approval and the kit reinstalled with deferred hooks. The upside is that the first commit now holds the unmodified kit.

## Stage 1: Development environment

**Proposed and built:** Sail with PostgreSQL 18, Pest, Pint rules, Larastan, Composer scripts, CI, and project documentation.

**Problems found by measuring:**

- The feature script left unused code behind, including 2FA attributes and an inert `verified` middleware. Claude removed it by hand.
- `pest --drift` failed because `laravel/pao` appends arguments when it detects an AI agent. Reading its source led to `PAO_DISABLE=1`.
- Replaying the CI steps in a fresh clone showed 8 failing tests caused by a missing Vite manifest, hidden locally by an existing build. Fixed with `withoutVite()` in `TestCase`.
- The kit's `.gitignore` excluded `CLAUDE.md`. Removed that entry.
- Oxlint does not check accessibility in Vue templates, which weakens the Vite+ choice. Recorded in `docs/DECISIONS.md`.

**Rejected:** Larastan level max, because the additional findings come from framework return types and could only be silenced.

**Corrected by the author:** Nothing so far in this stage.

## Stage 2: Domain model and database

**Proposed and built:** Migrations, models, the status enum, factories, German demo data and tests, written test-first and committed per concept.

**Decided by Claude, open to review:** A boolean admin flag, the foreign key delete rules, and a stored vote count. See `docs/DECISIONS.md`.

**AI mistakes:**

- Claude used `Collection::getOrFail()` in the seeder without checking it, and the method does not exist. The tests caught it.
- Claude added `->after('password')` to a migration, which only has an effect on MySQL. Removed before the stage ended.

**Found while designing:** Cascading votes on account deletion would make `votes_count` too high. The gap is documented and assigned to stage 3 instead of being solved outside this stage's scope.

**Corrected by the author:** Nothing so far in this stage.

## Stage 3: Business rules

**Proposed and built:** Status transitions in the enum, a policy, the actions `CastVote`, `RetractVote`, `ChangeStatus` and `DeleteUser`, all test-first, plus tests with real concurrent processes.

**Decided by the author:** Nothing new. The `DeleteUser` action was proposed at the end of stage 2 and accepted without objection.

**AI mistakes:**

- Claude's first concurrency tests looked convincing but did not test the lock. A mutation check with the lock removed stayed green. Claude had assumed the lock protected the vote count, which is protected by the atomic `increment` and Laravel's `createOrFirst`. A new test now covers what the lock really protects.
- Closures created inside a Pest test could not run in a child process, because they carry a generated class as scope. Making them `static` did not help. They now live in global functions.

**Open for the author to review:** Retracting votes stays possible on finished requests, because rules 1 and 5 do not forbid it.

## Stage 4: Interface

**Proposed and built:** Pages for products, lists, details, submitting and editing, German messages, optimistic voting and tests for all routes.

**Decided by Claude, open to review:** Public pages, removing the dashboard and sidebar, five requests per page. Kit pages such as login and settings stay English, because translating them was not part of the stage.

**Checked in the browser:** Optimistic display and rollback with a delayed connection, a vote rejected after the status changed behind the open page, validation errors with focus, escaping of HTML in user text, filters and pagination.

**Interrupted by the author:** Firefox could not open the local app in stage 3. Claude showed that a fresh Firefox profile works, and the author chose to continue without resolving the cause.

**Corrected by the author:** Nothing so far in this stage.
