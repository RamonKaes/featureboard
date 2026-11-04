# Featureboard

[![tests](https://github.com/RamonKaes/featureboard/actions/workflows/tests.yml/badge.svg)](https://github.com/RamonKaes/featureboard/actions/workflows/tests.yml)

Feature request and roadmap board for software products, built with Laravel, Inertia, Vue and PostgreSQL.

## Setup

You need Git and Docker with Compose. PHP and Node run inside the containers.

```bash
git clone https://github.com/RamonKaes/featureboard.git
cd featureboard
cp .env.example .env
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
./vendor/bin/sail up -d --wait
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
```

Open <http://localhost:8000>. If a port is taken, change `APP_PORT`, `VITE_PORT` or `FORWARD_DB_PORT` in `.env`.

There is no Sail Composer image for PHP 8.5 yet, so the first install uses the 8.4 image and ignores the platform check. The app itself runs on PHP 8.5 inside the Sail container.

These steps are tested from a fresh clone on Linux x86 and ARM, and the frontend tools on macOS. They have not been tried with Docker Desktop on a real Mac.

### Demo accounts

All accounts use the password `password` and are for local use only.

| Role  | Email                     |
| ----- | ------------------------- |
| Admin | `admin@featureboard.test` |
| User  | `demo@featureboard.test`  |

To reset the demo data, run `./vendor/bin/sail artisan migrate:fresh --seed`.

### Running the checks

```bash
./vendor/bin/sail npm run browsers:install
./vendor/bin/sail composer check
```

The first command downloads a browser into `node_modules` and is needed once. `composer check` runs Pint, Larastan, the frontend checks, the tests, the build and the browser tests.

## Idea

Feature wishes arrive by email, ticket and chat, so the same wish gets written down many times and nobody sees what users really want. Featureboard gives each product one public place and tries to stop duplicates before they exist:

- While someone types a title, the form shows similar wishes, using full-text search that understands German word forms and umlauts. They can vote for one right there, and their typed text stays in the form.
- Users submit wishes and vote for those of others, one vote per account.
- Admins move wishes through a fixed workflow: `new`, `planned`, `in_progress`, `done`, with `declined` possible from the first two.
- A public roadmap shows what is planned, in progress and done, without an account.

Claude Code suggested this idea, and I kept it for three reasons: every product team knows the problem, so it needs no explanation. It runs without API keys or outside services, so anyone can start it and its tests give the same result every time. And it is small, but it has real rules to get right, such as votes arriving during a status change.

The interface is in German, and the code and documentation are in English.

![Product overview](docs/screenshots/products.png)

![Requests of a product](docs/screenshots/requests.png)

![Similar requests while typing a title](docs/screenshots/similar-requests.png)

![Status change as admin](docs/screenshots/status-change.png)

![Public roadmap](docs/screenshots/roadmap.png)

## AI tools used

- **Claude Code** in the Claude desktop app, with the model Claude Opus 5.5, wrote the code, tests and documentation and ran the commands.
- **Claude Fable 5.1**, in the same tool, ran the second review. The first review ran on Opus 5.5.
- The app's **built-in browser** was used to check the interface.
- The **a11y-enforcer** plugin provided the static accessibility scan and the contrast calculation.
- **Laravel Boost** was not used while building. Its MCP server first failed because the package was missing, and on the host it can't reach the database, which only resolves inside the Sail network. It is now set up in `.mcp.json` to start through Sail, and a direct call returned the real schema.

## Approach

I wrote a brief with nine stages and fixed rules, and Claude Code worked through it. After every stage it stopped, reported with measured results and explained the central code, and it continued only after my approval. The rules are in [CLAUDE.md](CLAUDE.md), and every stage is logged in [docs/AI-WORKFLOW.md](docs/AI-WORKFLOW.md).

**The rules:** Measure instead of guessing, test business rules first, stay within the stage's scope, and keep everything green.

**How I steered it:**

- I set the requirements and the stack, and I decided the open questions, usually by accepting a recommendation after reading its trade-offs. For example, I accepted Vite+ instead of the planned ESLint and Prettier.
- All tests were green and I still asked for a hostile review, and I had it set up as a repeatable prompt. I then decided finding by finding what gets fixed, and told Claude to avoid overcoding and to record the rest with reasons instead of building it.
- I wanted the second review on a different model than the one that wrote the code. Claude started that session on the default model and first claimed it could not be changed. I insisted, and it worked.
- I wrote no application code by hand. Claude wrote it, and the rules and the checks caught its mistakes.

**Where the AI was wrong:**

- Its setup plan ignored that `create-project` already runs the kit's feature installer, so the kit had to be reinstalled.
- Its first concurrency tests stayed green with the row lock removed, because it had assumed the lock protected the vote count. A mutation check showed that the lock protects something else, and a new test now covers that.
- Its first accessibility tests checked 404 pages, because they assumed database ids.
- It once read a failing test as passing, because it had cut the result line from the output. A similar mistake happened again later, so that rule does not make it immune.
- Requests like `?status[]=done` or `/requests/abc` ended in HTTP 500, after hundreds of green tests that only sent valid input. A guard that attacks every route found these and more.
- Reviews were wrong too. The second one proposed a fix that PostgreSQL refuses, and it overstated a timing leak that Laravel hides on an idle server. Both showed up only because its findings were reproduced before anything was fixed.

Each lesson became a rule in `CLAUDE.md`, together with the case that caused it.

## Architecture decisions

The full reasoning, with options and measurements, is in [docs/DECISIONS.md](docs/DECISIONS.md).

- **Stack:** Laravel 13, Inertia 3, Vue 3 with TypeScript, Tailwind 4, PostgreSQL 18 and Sail, based on the official Vue starter kit.
- **Status workflow:** The allowed transitions live in one enum. The interface offers only those, and the backend checks them again.
- **Rules in actions:** `CastVote`, `RetractVote` and `ChangeStatus` lock the row in a transaction and run the policy on the locked row, so a check cannot be outdated when the write happens.
- **Stored vote count:** `votes_count` makes sorting simple. Atomic increments keep it correct, and `DeleteUser` corrects it when an account is deleted.
- **Database constraints:** A unique vote per user and request, a status check, a non-negative count, and deliberate delete rules. For example, a request stays anonymously when its author is deleted.
- **Full-text search over `pg_trgm` or Scout:** A generated, weighted `tsvector` column with the `german` configuration and a GIN index handles word forms and umlauts without another service.
- **Optimistic voting:** Inertia's own `optimistic` option restores the previous state on failure, so there is no custom rollback code.
- **Only two roles:** A non-fillable `is_admin` boolean, without a permissions framework.
- **Reduced kit:** Two-factor authentication, passkeys, email verification, the dashboard and the sidebar were removed.

## Quality assurance

All numbers were measured on the final code. See [docs/QUALITY.md](docs/QUALITY.md) for the audit.

| Check           | Result                                                                                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Tests           | 355 tests with 1,349 assertions against PostgreSQL, 98.4% of lines in `app/`                                                                                 |
| Browser tests   | 107 tests with 345 assertions in Chromium                                                                                                                    |
| Static analysis | Larastan level 9 without baseline, Pint, Vite+, `vue-tsc`                                                                                                    |
| Accessibility   | axe at its strictest level on all pages with a route, light and dark, 0 issues                                                                               |
| Contrast        | Calculated for every visible text and the borders of controls, in both themes                                                                                |
| Index use       | `EXPLAIN ANALYZE` on 100,020 rows: 1.9 ms with the GIN index, 18.2 ms without                                                                                |
| Query counts    | Constant on all lists and the detail page, tested                                                                                                            |
| Rate limits     | Per minute and user: 30 votes, 60 searches, 10 submissions. Per address: 30 logins a minute, 10 registrations an hour, 6 reset requests a minute. All tested |

- **Hostile input:** A test takes every route from the router and sends arrays, NUL bytes, invalid UTF-8, overflowing ids and more, as guest and as admin. None may end in a server error.
- **Concurrency:** Separate processes vote and delete accounts at the same moment against committed rows. Removing the lock makes these tests fail, which was checked by mutation.
- **CI:** GitHub Actions runs the static checks, the tests and the browser tests on every push. A second workflow, started by hand, follows this README from a fresh clone on ARM and runs the frontend tools on macOS.

### Review

[`/big-review`](.claude/commands/big-review.md) is a hostile audit in a new session that did not build the code. It starts without the project's explanations, runs every machine and mutation tests, attacks every entrance on twelve axes (form, value, structure, identity, frequency, concurrency, volume, time, state, abuse, error path, display), tries abuse with several accounts, measures every claim in this README, and must list what it did not check.

| Run | Model            | Blocker | High | Medium | Low |
| --- | ---------------- | ------- | ---- | ------ | --- |
| 1   | Claude Opus 5.5  | 4       | 6    | 16     | 8   |
| 2   | Claude Fable 5.1 | 0       | 1    | 4      | 10  |

All checks were green before both runs, so the green checks alone would have missed every one of these. All blockers and high findings are fixed with tests, and the second run found that the sampled fixes of the first still hold. Its worst find: two admins deleting themselves at once left no admin behind in 6 of 8 attempts. What stays open is listed with reasons in [docs/REVIEW-LEDGER.md](docs/REVIEW-LEDGER.md), and no third run has confirmed the latest fixes.

These are limited reviews: both ran with the same tool, the project's `CLAUDE.md` was loaded from the start, and neither is an independent review by a person. Mutation testing scored 78.27% in the second run, before the tests added afterwards, and the survivors were decided one by one.

## Known limitations

- **Search:** Compound words are not split, so `Rechnungsexport` does not find `Export`. Some verb forms have different stems, like `versenden` and `versendet`, and typos are not found.
- **Accessibility:** No test with a real screen reader and no complete manual keyboard walk through every page. Kit dialogs and menus were only checked by axe and in part by hand.
- **Moderation:** Nothing public can be removed without database access. Rate limits slow spam down, and a misclicked `done` or `declined` cannot be undone in the interface.
- **Identity:** One vote per account, not per person, because there is no email verification. Password reset and registration reveal whether an account exists, and under heavy load so does the login time. Limits per address slow all three down.
- **Races left open:** An edit at the exact moment of a status change can still go through, because the edit form checks without a row lock. A direct `$user->delete()` that bypasses `DeleteUser` leaves vote counts too high.
- **Scale:** The roadmap has no pagination, sorting has no index yet, and the rate limits are estimates without real usage data.
- **Production:** No deployment and no Content Security Policy. The seeder creates accounts with a known password.
- **Browsers:** Tests run in Chromium only. On my machine one existing Firefox profile could not open the local app. A fresh profile worked, and I did not find the cause.
- **Accepted findings:** [docs/REVIEW-LEDGER.md](docs/REVIEW-LEDGER.md) lists the rest, such as votes that stay when an author rewrites a request.
