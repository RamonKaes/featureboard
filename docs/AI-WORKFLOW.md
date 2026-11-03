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

## Stage 5: Similar requests

**Proposed and built:** Generated `tsvector` column, GIN index, ranked search, a JSON endpoint and a debounced hint in the form.

**Measured before building:** German stems for word forms and umlauts, trigram scores, and available Laravel and Inertia APIs. This replaced raw SQL with `tsvector()->storedAs()` and a hand-written request with `useHttp`.

**Honest result:** The GIN index is only used for selective words. Common words and small products take other plans, which is correct but less impressive than "the index is used". Both are documented.

**Corrected by the author:** Nothing so far in this stage.

## Stage 6: Admin functions and roadmap

**Proposed and built:** Status buttons on the detail page, a status endpoint, a public roadmap per product, and tests for rights and display.

**Checked in the browser:** The full workflow as admin from new to done, a rejected click on an outdated page, and the roadmap as a guest.

**Decided by Claude, open to review:** No confirmation before final statuses, and no pagination on the roadmap.

**Corrected by the author:** Nothing so far in this stage.

## Stage 7: Quality

**Proposed and built:** An accessibility audit, translated kit pages, a new logo, rate limits, route protection tests, and browser tests. See `docs/QUALITY.md`.

**AI mistakes:**

- The first accessibility tests used fixed ids like `/requests/1`. Sequences survive rolled back tests, so those paths were 404 pages and passed axe without testing the real page. Tests now check the page content first.
- A bad regular expression corrupted the logo component and broke the build.
- `assertSeeIn` does not wait for Vue to render, and two wrong attempts cost about ten minutes of test runs before reading the plugin source.
- `querySelector('#status-changer-heading, h1')` returns the first match in the document, so the focus always went to the `h1`. Claude first read a failed test as passing, because it had cut the result line from the output. Repeating the test revealed the failure.

**Found by calculating instead of trusting a tool:** Two contrast failures that axe did not report.

**Decided without asking:** Translating the kit pages and replacing the logo, both announced in the reports of earlier stages.

**Corrected by the author:** Nothing so far in this stage.

## Stage 8: README and submission

**Proposed and built:** The README, screenshots and a final check from a fresh clone.

**Final check:** A fresh clone was set up with only the README's commands, with its own database volume. All steps worked on the first try, the documented accounts could log in, and `composer check` was green with the browser installed as described.

**Not covered by the final check:** The Sail image was already built on this machine, so the first image build was not repeated. The clone used a local path instead of the GitHub URL, under another folder name to keep its database separate.

**AI mistakes:**

- A failed file write went unnoticed, so the first commit claimed a README but held only screenshots. Claude amended the unpushed commit.
- The first clone used the same folder name as the project, so it shared the Compose project and database volume with the development setup. Claude noticed before migrating and repeated the check with another name.

**Corrected by the author:** Nothing in this stage.

## After stage 8: Hostile input and the review prompt

**Requested by the author:** A merciless review prompt, and a look at which test guards would help this project.

**Found while measuring:** Claude tried the guard idea against the running app before recommending it, and anonymous requests ended in HTTP 500. The new guard then found more: array passwords and emails, and the routes of a removed feature.

**AI mistakes:**

- All tests sent valid input for seven stages, and the audit in `docs/QUALITY.md` did not ask for anything else.
- Its first probe script rolled back its own test data, so its 404 results meant nothing. Claude discarded them and measured again.
- The new guard stopped seeing `sort` after the fix changed how the name was read. Its own coverage test caught that.

**Decided by the author:** Fix the errors first, review afterwards in a fresh session, and invite reviewers only after reading the code.

## After stage 8: First big review

**Run:** The review ran in a fresh session with Claude Opus 5.5, not with the model the author asked for. Claude passed the requested model, did not check the result, and only noticed it minutes later. The author chose to keep the run.

**Result:** 4 blockers, 6 high, 16 medium and 8 low findings, with every machine green. Claude reproduced the worst ones before reporting them.

**AI mistakes it found:** Typing a title erased the description since stage 5. Sentences in the README claimed more than was measured. A second English heading came from an accessibility fix in stage 7. The new input guard missed cookies and values just below the overflow it tested.

**Decided by the author:** Fix all blockers and high findings. Medium findings stay open for now.
