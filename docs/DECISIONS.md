# Architecture decisions

## 1. Stack

**Context:** The app is a demo that Laravel developers will read. It needs a modern, idiomatic stack and PostgreSQL full-text search later on.

**Decision:** Laravel 13, Inertia 3, Vue 3 with TypeScript, Tailwind 4 and PostgreSQL 18 on Laravel Sail with PHP 8.5, based on the official `laravel/vue-starter-kit`.

**Reason:** These are the current stable versions, verified on 2026-11-03. The kit is the maintained first-party starting point, so reviewers will recognise its structure. Its last tag is from February 2025 and outdated, so it is installed from the main branch with `--stability=dev`, as the official installer does. `composer.lock` pins the result.

## 2. Vite+ instead of ESLint and Prettier

**Context:** The original plan named ESLint and Prettier. The kit now ships Vite+ (`vp check`), which runs Oxlint and Oxfmt and also replaces the `vite` package.

**Decision:** Keep Vite+.

**Reason:** It is the kit default and avoids a second, overlapping toolchain.

**Trade-off:** Measured with Oxlint 1.89.0, `jsx-a11y/alt-text` reports an `<img>` without `alt` in a `.jsx` file but not in a `.vue` template. Linting therefore does not cover template accessibility, and this must be checked separately in stage 7.

## 3. Keep only registration from the kit's auth features

**Context:** The kit includes email verification, two-factor authentication, passkeys and password confirmation.

**Decision:** Keep registration, login and password reset, and remove the rest.

**Reason:** The domain does not need them, and the author must be able to explain every line. Less code also means less attack surface.

## 4. Tests run on PostgreSQL

**Context:** The kit tests on in-memory SQLite.

**Decision:** Local tests and CI use a real PostgreSQL 18 database called `testing`.

**Reason:** Full-text search, generated columns and concurrent votes depend on PostgreSQL behaviour that SQLite cannot reproduce.

## 5. Larastan level 9 without a baseline

**Context:** The kit uses level 7. Measured on this code base, level 8 reports 7 errors, level 9 reports 8 and level max reports 11.

**Decision:** Use level 9, fix all findings and use no baseline.

**Reason:** All level 9 findings were real type gaps with clean fixes, such as `#[CurrentUser]` instead of the nullable request user. The extra level max findings come from `Route::inertia()` returning `mixed` in the framework, which could only be silenced, not fixed.

## 6. Pint with strict rules

**Decision:** Use the `laravel` preset plus `declare_strict_types`, `strict_comparison`, `fully_qualified_strict_types` and `global_namespace_import`.

**Reason:** Strict types and strict comparisons surface type errors early, which suits business rules.

## 7. Host ports

**Decision:** Sail publishes the app on 8000 and PostgreSQL on 5434, configurable through `APP_PORT` and `FORWARD_DB_PORT`.

**Reason:** Ports 80 and 5432 are taken by local services on the development machine, and Sail's defaults would fail to start.

## 8. Admin role as a boolean

**Context:** The app has two roles, and the brief asks for a simple solution without a permissions framework.

**Options:** A boolean `is_admin`, a `role` column with an enum, or a package such as spatie/laravel-permission.

**Decision:** A boolean `is_admin` that is not mass assignable.

**Reason:** A boolean models exactly two roles, and a third role is not planned. It can become an enum later with a small migration.

## 9. Delete rules for foreign keys

| Foreign key                   | Rule     | Reason                                                                                       |
| ----------------------------- | -------- | -------------------------------------------------------------------------------------------- |
| `feature_requests.product_id` | restrict | Deleting a product with requests would destroy user content, so it must be a conscious step. |
| `feature_requests.user_id`    | set null | Account deletion must work, and other users' votes on the request must survive it.           |
| `votes.feature_request_id`    | cascade  | A vote has no meaning without its request.                                                   |
| `votes.user_id`               | cascade  | Account deletion removes the person's votes.                                                 |

The cascade from `votes.user_id` removes vote rows without lowering `feature_requests.votes_count`. The `DeleteUser` action corrects the counts, see decision 13.

## 10. Stored vote count

**Decision:** `feature_requests.votes_count` stores the number of votes, with a check that rejects negative values.

**Reason:** The list sorts and paginates by votes, which a stored and indexable column supports directly. The price is that the count must be kept in sync with the `votes` table, and stage 3 is responsible for that.

## 11. Rules the database cannot enforce

The unique vote, valid statuses and non-negative counts are database constraints. "No vote for your own request", "no vote on finished requests" and the allowed status transitions compare several rows or the previous value, so they belong in the application layer in stage 3.

## 12. Concurrency strategy for votes

**Context:** Votes must stay correct under concurrent requests, and a vote must not slip in while an admin declines the request.

**Decision:** `CastVote`, `RetractVote` and `ChangeStatus` lock the feature request row with `lockForUpdate` in a transaction and check the policy against the locked row. The count changes through an atomic `increment`.

**Reason, measured by mutation:** With the lock removed, the tests for concurrent votes stayed green, because `increment` is atomic and `firstOrCreate` handles unique conflicts. Only replacing `increment` with read-modify-write as well lost an update (count 1 instead of 2). The lock protects something else: without it, a vote arriving during a decline was accepted. A dedicated test now covers that case.

## 13. Account deletion through an action

**Options:** A `DeleteUser` action, a PostgreSQL trigger on `votes`, or keeping votes anonymously with a nullable `user_id`.

**Decision:** A `DeleteUser` action, used by the profile settings.

**Reason:** It sits next to the other actions and is easy to read, test and change. A trigger would be more robust but hides logic in the database, and anonymous votes would weaken the unique rule.

**Trade-off:** Code that deletes a user without this action, such as a direct `$user->delete()`, leaves the counts too high.

## 14. Retracting votes on finished requests

**Context:** Rule 1 says a vote can be retracted. Rule 5 only forbids voting on finished or declined requests.

**Decision:** Retracting stays possible in every status.

**Reason:** This follows the rules literally, and a person keeps control over their own vote. The count of a finished request can therefore still go down.

## 15. Actions enforce the rules themselves

**Decision:** Actions call `Gate::forUser()->authorize()` on the locked row instead of leaving authorization to controllers.

**Reason:** The check must see the state inside the lock. A check in the controller would read the row earlier and could be outdated when the write happens.

The rule that the last admin stays follows the same idea. A review showed that two admins leaving at once both passed a validator, so `DeleteUser` now locks all admin rows in a fixed order and decides there.

## 16. Public pages and the header layout

**Decision:** Products, lists and details are public. Submitting, editing and voting need an account. The kit's sidebar, dashboard and welcome page are removed, and `/` shows the products.

**Reason:** A feature board should be readable without an account, and the roadmap in stage 6 has to be public anyway. A placeholder dashboard would only distract from the actual app.

## 17. Optimistic voting with Inertia's own option

**Options:** Local component state with manual rollback, or the `optimistic` visit option of Inertia 3.

**Decision:** The `optimistic` option.

**Reason:** Inertia restores the previous props when a visit fails, and a successful visit replaces them with the server's state. Own rollback code would duplicate this. Measured in the browser with a delayed connection: the count changed from 3 to 2 at once and returned to 3 after a simulated network error. A vote rejected by the server showed a toast and the true state.

## 18. Forbidden Inertia visits become a toast

**Decision:** `bootstrap/app.php` turns a 403 on an Inertia visit into a redirect back with a German toast. Direct page loads keep the plain 403 response.

**Reason:** Inertia would otherwise show the error page in a modal. This case only happens when the state changed after the page loaded, because the interface does not offer forbidden actions.

## 19. Tolerant filters without a form request

**Decision:** List filters read plain query strings, and unknown or malformed values fall back to the defaults. The `pageNumber` method treats the page the same way.

**Reason:** A broken link should still show the list rather than a validation redirect. Form requests are used wherever data is written.

## 20. Stored enum labels in PHP

**Decision:** German status and sort labels live in the PHP enums, and the frontend receives them as props.

**Reason:** Status rules and labels stay in one place, and the frontend cannot drift from the backend.

## 21. Similar requests with PostgreSQL full-text search

**Context:** While typing a title, the form should show existing requests of the same product, in German and with different word forms.

| Option           | Strength                                                                   | Weakness                                                                         |
| ---------------- | -------------------------------------------------------------------------- | -------------------------------------------------------------------------------- |
| Full-text search | Handles word forms and umlauts, ranks by relevance, needs no extra service | Does not split compound words, finds no typos                                    |
| `pg_trgm`        | Tolerates typos and partial words                                          | Knows no word forms, and scores depend on text length                            |
| Laravel Scout    | Common API, with Meilisearch or Typesense also typo tolerance              | Another package, and for good results another service, for a single small search |

Measured with `pg_trgm` 1.6: "Mahnung" against "Mahnungen in mehreren Stufen" scores 0.26, and "Grösse" against "Größere Logos" scores 0.17, below the default threshold of 0.3. Full-text search matches both.

**Decision:** A generated `tsvector` column with the `german` configuration, weighted with title A and description B, a GIN index and `ts_rank` for the order.

**Reason:** It solves the actual problem without new infrastructure, and the generated column cannot get out of sync. Measured stems: "Größe", "Groesse" and "Grösse" all become `gross`, and "Rechnung" and "Rechnungen" both become `rechnung`.

**Query:** Words are combined with OR, because a hint should show partial matches. The last word also matches as a prefix from three letters on, because it may be unfinished. The query is built from letters and digits only, so user input cannot contain search operators.

**Known limits, measured:**

- Compound words are not split: `Rechnungsexport` does not match `Export`.
- Some verb forms have different stems: `versenden` becomes `versend`, `versendet` stays `versendet`.
- Typos are not found.

**Index use:** `EXPLAIN ANALYZE` on 100,020 synthetic rows, rolled back afterwards:

| Query                                | Plan                                     | Time    |
| ------------------------------------ | ---------------------------------------- | ------- |
| Rare word, 20 hits                   | Bitmap Index Scan on the GIN index       | 1.9 ms  |
| Same query with index scans disabled | Seq Scan                                 | 18.2 ms |
| Common word, about 40% of all rows   | Parallel Seq Scan, chosen by the planner | 18.7 ms |
| Small product with 1,000 rows        | Index Scan on `product_id`               | 1.1 ms  |

The planner uses the GIN index when it helps. For very common words a sequential scan is cheaper, and for small products the `product_id` index is. A test confirms that the query can use the GIN index. The index takes about 10 MB for the 28 MB table.

## 22. Status changes offer only allowed transitions, and the backend checks again

**Decision:** The detail page receives `allowedStatuses`, which is empty for everyone but admins, and shows one button per transition. `ChangeStatus` checks the permission and the transition again on the locked row.

**Reason:** The buttons are a convenience and no protection, because a request can be sent directly and a page can be outdated. `InvalidStatusTransition` renders itself as a redirect with a German toast. Measured in the browser: with the status changed behind an open page, the click was rejected and the page then showed the true status and its transitions.

**Trade-off:** `done` and `declined` are final, and there is no confirmation step, so a misclick cannot be undone in the interface.

## 23. Roadmap without pagination

**Decision:** The public roadmap shows all planned, in progress and done requests of a product, sorted by votes.

**Reason:** A roadmap is meant to be seen as a whole, and the data set is small. A product with many finished requests would need a limit on the `done` column.

## 24. Browser tests with the Pest browser plugin

**Options:** Laravel Dusk, or the Pest browser plugin with Playwright.

**Decision:** The Pest browser plugin.

**Reason:** The Laravel 13 documentation recommends it over Dusk for new projects, tests share the database transaction with the app, and it includes axe for accessibility checks. It runs inside the Sail container without extra system packages. Browsers are installed into `node_modules` through `PLAYWRIGHT_BROWSERS_PATH=0`, so nothing is installed globally. The price is about 680 MB in `node_modules`.

## 25. Rate limits per user

**Decision:** 30 votes and 60 searches per minute for each user.

**Reason:** A person will not vote 30 times a minute. The search debounce allows at most about three requests per second while typing, but real typing has far fewer pauses, so 60 leaves room without allowing scraping. The numbers are estimates without real usage data.

## 26. Malformed input

**Context:** PostgreSQL throws on an id that is not a bigint and on text with NUL bytes or invalid UTF-8, where other databases are more forgiving. Requests with such values ended in HTTP 500, and no test with valid input could find that.

**Decision:**

- The `featureRequest` route parameter only accepts up to 18 digits, so the router answers everything else with a 404.
- A global middleware answers NUL bytes, invalid UTF-8 and an array as email with a 400.
- Identity validation rules use `bail`, so `current_password` never sees an array.
- List filters read strings only, so an array falls back to the default like any other unknown value.
- The routes of the removed password confirmation answer with a 404, because Fortify registers them anyway.

**Reason:** Each fix sits at the cause. `tests/Feature/Security/InputShapeTest.php` reads the routes from the router, so a new route is attacked without anyone remembering to add it.

**Trade-off:** The attack sends about 2,000 requests and takes about 50 seconds of the test run.

## 27. Limits instead of moderation and verification

**Context:** A review measured 120 submissions in 4.6 seconds and 40 registrations in 15.6 seconds, with nobody able to remove anything without database access.

**Decision:** Rate limits for submitting and registering, and a test that every writing route has a limit or a stated exemption. A moderation feature and email verification stay out, and the README names both as limitations.

**Reason:** The limits are small and solve the worst case. Moderation is a feature of its own, and verification was removed on purpose in decision 3, so both are the author's call.

## 28. The idea

**Decision:** A feature request board. Claude Code suggested it, and the author kept it.

**Reason:** Every product team knows the problem, it runs without API keys or outside services, and its tests are deterministic. It is small but has real rules: a fixed workflow, one vote per account under concurrency, and a search that stops duplicates before they exist.

**Trade-off:** It is a common type of demo. The voting hint in the form is what sets it apart.
