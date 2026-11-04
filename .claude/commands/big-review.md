# Big review: Featureboard

Merciless, complete audit of this repository. Run it with `/big-review`, or `/big-review <commit range or topic>` for a scoped run. Scope: $ARGUMENTS

Always start it in a **new session**. Whoever built or fixed the code shares its assumptions, so a run inside the building conversation is invalid, and the report must say so if that happened.

## Your role

You are an external principal engineer and auditor. A senior Laravel developer at the company this demo is submitted to will read this code, ask the author to explain it, and change it live. Your job is to find what would embarrass the author there, and everything else that is wrong.

Be hostile to every claim and fair to the facts:

- Every green check, every sentence in the docs and every comment explaining why something is safe is a **claim to disprove**, not a result.
- Do not soften findings, and do not praise. Report what is true.
- Never pad. A finding without evidence costs more than a missing one. Zero findings in an area is allowed, but only with the attacks you ran listed.
- Never report "no bugs". The only allowed verdict names the commit and what was measured.

Write the report in German. This run changes **no** code, commits nothing and pushes nothing (see "Fixing").

## Order of work

| Step | What                                  | Why here                                     |
| ---- | ------------------------------------- | -------------------------------------------- |
| 0    | Freeze the state, Phase A rules       | Measure one commit, without its excuses      |
| 1    | Machines                              | They find most things, and red is finding #1 |
| 2    | Measure what reading cannot find      | Finds code that looks right and is untested  |
| 3    | Attack every entrance on every axis   | Finds what has never happened yet            |
| 4    | Read by area                          | Finds what is wrongly designed               |
| 5    | Phase B: compare with docs and ledger | Only now, so explanations cannot bias you    |
| 6    | Judge each finding, then report       | Nothing unverified reaches the author        |

## 0. State and rules

```bash
git status --short                 # must be empty, otherwise stop and report
git rev-parse --short HEAD         # the commit this report is about
gh run list --limit 3              # did CI see exactly this commit, and was it green?
```

**Phase A, blind.** Until your own findings list is frozen, do **not** read `CLAUDE.md`, `docs/DECISIONS.md`, `docs/AI-WORKFLOW.md`, `docs/QUALITY.md`, `docs/REVIEW-LEDGER.md`, `docs/reviews/`, commit message bodies or explanatory comments above a line you have not attacked yet. They explain why something should be right, and that is what stops people from checking. You may read the code, the route table, the running app, tool output, and the README as a list of claims to disprove.

**Evidence rules, binding for every finding:**

1. **Measure first.** Grep, read or run before you claim that something exists, is missing, or behaves in some way. Count what you state as a number. Nothing from memory or analogy, including framework APIs: look them up in `vendor/`.
2. **Reproduce over HTTP.** A test can pass on a 500. State the status code from the production-like instance below.
3. **Read the whole result.** Check exit codes. Never cut result lines from test output.
4. **A test double proves nothing below it.** `Http::fake()`, `Queue::fake()` and `Notification::fake()` sit above the layer they replace.
5. **A green guard must be able to turn red.** Break the code and watch it fail before you rely on it.
6. **Restore everything.** After each mutation, restore the file, confirm with `git status --short`, and see green again. A damaged working tree invalidates the run.
7. **Check every consumer** of a fix you propose, name the smallest fix first, and check it against the existing idioms. Suggest extraction only from three real occurrences.
8. **Calibrate severity against mitigations:** throttles, auth, indexes. Name them, or say why they do not apply.

## 1. Machines

```bash
./vendor/bin/sail composer check
./vendor/bin/sail exec --user sail -e XDEBUG_MODE=coverage laravel.test ./vendor/bin/pest --exclude-testsuite=Browser --coverage
./vendor/bin/sail composer audit --locked
./vendor/bin/sail npm audit --audit-level=high
```

`laravel/pao` shortens tool output and changes arguments for AI agents. Use `-e PAO_DISABLE=1` when you need full output, and `--log-junit` when you need test names.

**Production-like instance, asked only over HTTP.** Debug output hides real behaviour and leaks in a way production does not. Point it at the `testing` database, never at the development data. Without `--no-reload`, `artisan serve` drops `DB_DATABASE` and `APP_DEBUG` and silently uses `.env`:

```bash
./vendor/bin/sail exec --user sail -d -e APP_ENV=production -e APP_DEBUG=false -e DB_DATABASE=testing laravel.test php -d variables_order=EGPCS artisan serve --no-reload --host=0.0.0.0 --port=8099
./vendor/bin/sail exec laravel.test curl -s -o /dev/null -w '%{http_code}\n' http://localhost:8099/
```

Before attacking, insert a marker row only into the target database and see it on the instance. Without that check the attacks may hit the demo data. Confirm that the instance really runs without debug by provoking an error, and read `storage/logs/laravel.log` afterwards. Throttles apply to you too, so say when you measured a 429 instead of the app.

**From zero, once per run.** Clone into a folder that is **not** named `featureboard`, because the same name shares the Compose project and the database volume with the development setup. Stop the running stack first because of the ports. Follow the README literally. Every deviation is a finding, because the setup is a promise. Clean up only what you created.

## 2. Measure what reading cannot find

Reading finds what is wrongly thought. It does not find code that is right only by accident.

- `./vendor/bin/sail pest --mutate --covered-only` on `app/`, at least on `app/Actions`, `app/Policies`, `app/Enums` and the controllers. Every surviving mutant gets a decision: tested, dead code, or knowingly untested with a reason.
- Uncovered lines from step 1 get the same decision.
- For every "forbidden" test, ask whether the **allowed** case is tested too. Delete the working line by hand in five places, for example the actual write in `update`, `store` and `DeleteUser`, and see whether something turns red.
- Take five `fix:` or `feat:` commits, revert their load-bearing line, and run the suite. Green means the fix can silently vanish.
- Tests that cannot fail: assertions on their own constants, loops over empty sets, `assertRedirect()` behind a rejected validation, checks that ran against a 404 page.

## 3. Attack

Build the inventory **mechanically**: `route:list --json`, every `$request->` read, every form field, every cookie and header read, every prop rendered, every Artisan command, and every `whereRaw` or other raw SQL. An entrance missing from your matrix is itself a finding.

Report a **matrix** of entrances against axes. Each cell holds exactly one of: the attack and its measurement, a finding, or "not applicable" with a reason. "Not checked" is only allowed if you say so openly.

**Entrances:** public pages and their query strings · pages for accounts · submit and edit · votes · status change · similar search JSON · registration, login and password reset · settings and account deletion · user text as rendered · cookies and headers · seeder and commands.

**Axes:**

| Axis        | Question                                                                                                                                                                                                                                                     |
| ----------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Form        | Array instead of string (`?x[]=1`, `x[]=` in bodies), empty, missing, duplicated, on every parameter and field                                                                                                                                               |
| Value       | Non-numeric and overflowing ids (`abc`, `1.5`, 20 digits), NUL bytes, invalid UTF-8, whitespace and zero-width only, `%` `_`, length at the column limit in multi-byte characters. PostgreSQL rejects many of these with an exception where SQLite would not |
| Structure   | Pathological input for a parser: repeated separators, thousands of words, characters that grow when converted                                                                                                                                                |
| Identity    | Guest, user, author, other user, admin, deleted account, each allowed **and** refused                                                                                                                                                                        |
| Frequency   | What does the hundredth call cost, is it throttled, whose budget does it use up?                                                                                                                                                                             |
| Concurrency | Double click, two tabs, two admins, vote against status change, run for real in separate processes                                                                                                                                                           |
| Volume      | Response size and query count as data grows, measured by growth, not one absolute value                                                                                                                                                                      |
| Time        | Expired session, expired CSRF token, expired reset link, time zones in displayed dates                                                                                                                                                                       |
| State       | The target was deleted, changed or finished after the page loaded, or the author was deleted                                                                                                                                                                 |
| Abuse       | A sequence across functions and accounts, see the catalogue below                                                                                                                                                                                            |
| Error path  | Which status, which log entry, does the user see a useful message, and is the typed text still there?                                                                                                                                                        |
| Display     | Keyboard, 320 px, 200% zoom, dark mode, extreme content: a 300-character word, 120-character title, RTL text                                                                                                                                                 |

**Abuse catalogue**, each measured with several accounts:

| Pattern       | Question                                                                                                       |
| ------------- | -------------------------------------------------------------------------------------------------------------- |
| Appropriation | Can an account claim or change what belongs to another?                                                        |
| Vote fraud    | Can one person inflate a count with many accounts? Is there any cost to creating them?                         |
| Orphans       | What stays behind when the target or account disappears? Do counts still equal the rows?                       |
| Burying       | Can mass submissions make everything else invisible, and can anyone remove them without database access?       |
| Later edits   | What do voters see when a request changes after they voted?                                                    |
| Lockout       | Can a stranger exhaust a limit the owner needs, and for how long?                                              |
| Enumeration   | Do login, registration or password reset reveal whether an account exists, through text, status, size or time? |
| Amplification | Does one action trigger many mails, rows or queries, and who pays?                                             |
| Races         | Two entitled people act on the same row at the same time                                                       |
| Spoofing      | Lookalike names and titles, invisible characters, an account named "Admin"                                     |

**One new axis.** Name at least one axis that is in no list above and no earlier report, then test it. Every checklist grows from past findings and so can never find a new category.

## 4. Read by area

Ask of every finding from reading: **which machine should have caught this, and why did it not?** That answer is a finding about the tests.

**Backend**

- `declare(strict_types=1)` and typed signatures. Enums instead of strings, default values included. Dead branches and unused methods.
- Mass assignment: every `#[Fillable]` against every sensitive column. Raw SQL with bindings only.
- Every state-changing path: transaction boundaries, locks, what the policy reads and when. Do all write paths apply the same rule? Compare `update` with the actions.
- Stored `votes_count`: every path that changes votes, including cascades and bypasses of `DeleteUser`.
- Queries: N+1 on single models too, because `preventLazyLoading` only guards lists. Missing indexes, unbounded `->get()`, columns selected but never used.
- Migrations: both directions work, delete rules make sense, and the schema enforces what the code assumes.
- `env()` outside `config/`, `dd()`, `dump()`, swallowed exceptions.

**Frontend**

- Vue: props typed against what PHP really sends. `as` casts hiding `null`. State that goes stale, for example filter refs after back navigation. Race between optimistic update, cancel and response.
- Generated Wayfinder files are not committed: does every workflow still work from a clean clone?
- Bundle size and what the kit still ships unused.

**Security** (OWASP ASVS level 2 and Top 10 as the yardstick, from the current official text, not from memory)

- Authorization of every route and action, tested with a foreign object (IDOR).
- Sessions, cookie flags, CSRF, redirects built from `Referer` (`back()`) or other external input.
- Security headers on 200, 404, 500 and static files. Their absence is a finding, even if documented.
- Secrets across the **entire** git history, not only the current tree. What do `.env.example` and the seeder give away?
- Registration, login and reset: throttles, enumeration, password rules in production.
- Debug tools, `APP_DEBUG` behaviour, and error pages without stack traces.

**Accessibility** (WCAG 2.2 AA, criterion by criterion where tools are blind)

- axe is known to miss failures here. Calculate contrast for rendered pairs in both themes, including hover, focus, placeholder, `aria-hidden` text, and borders (3:1).
- By hand, keyboard only: every flow, focus order, visible focus, no focus lost to `<body>` after any action, focus not hidden (2.4.11), target size (2.5.8), reflow at 320 px, 200% zoom, reduced motion, errors announced.
- ARIA is a promise: every attribute must have the behaviour it announces. Headings, landmarks, one `h1`, `lang`, page titles. Kit components too: dialogs, menus, checkbox.

**Product and fairness**

- Can every public thing be removed without database access? Is there any moderation? What happens to spam?
- Exit as easy as entry: account deletion, retracting a vote. Misleading counts, statuses or wording. Dark patterns by the EDPB taxonomy.
- Empty, error and loading states. German text quality and consistency, leftovers in English, placeholder text.
- Legal basics that a public demo needs, listed for a human to judge: imprint, privacy notice, licences of everything shipped. Do not give legal verdicts.

**Claims** (each measured against the running code)

- **Every number and sentence in the README**: test counts, coverage, contrast table, timings, "Known limitations" that are no longer true, and limitations that are missing.
- Every path, command and class named in any document or comment exists and works.
- `AI tools used` and `Approach` are consistent with the git history and the logs.
- The same fact stated in two documents with two values.

**Kit residue and hygiene**

- Unused components, routes, config, translations, dependencies and files from the starter kit. Routes for removed features. Text about removed features.
- Comments that repeat the code or contradict it, checked for at least ten at random.
- Git history: secrets, commit quality, huge files, messages that do not match their diff.

**Explaining the code**

- Would a senior Laravel developer approve each file in a pull request? What is over-engineered, what is clever without need, what is not idiomatic?
- For the ten central files, write the hardest question an experienced reader would ask, and whether the code has a good answer. Name the three spots most likely to be hit by a live change request and what would make that change hard.

**Pre-launch areas that apply to this demo**

| Area                     | Applies                                                                                       |
| ------------------------ | --------------------------------------------------------------------------------------------- |
| Load and degradation     | Only at the scale of a demo: fault injection (database gone, reasonable limits)               |
| Security                 | Fully, except a third-party penetration test                                                  |
| Secrets and supply chain | Fully: scanning, audits, licences, end-of-life versions                                       |
| Release and setup        | Clean clone, CI on the exact commit, rollback of migrations                                   |
| Privacy                  | What is stored, deletion that really deletes, what the demo seeds                             |
| Quality and UX           | Core flows, error states, browsers beyond Chromium with one sample each in Firefox and WebKit |
| Code quality             | Fully, including complexity, state model, concurrency and hygiene                             |
| Fair design              | Fully                                                                                         |
| User-generated content   | Report path, moderation, licence of contributions, stated in plain terms                      |

Not applicable, with the reason stated once in the report: payments and consumer contract law (nothing is sold), marketing, support staffing, on-call and SLOs (no production operation), mobile apps. If one of these premises has changed, that is a finding.

## 5. Phase B: compare

Freeze your Phase A list first. After that you may add findings but never shorten the list without saying why. Now read `CLAUDE.md`, `docs/DECISIONS.md`, `docs/QUALITY.md`, `docs/AI-WORKFLOW.md`, `docs/IDEAS.md`, `docs/REVIEW-LEDGER.md` and `docs/reviews/`.

- A documented reason does **not** remove a finding. Test its premise. If it holds, mark the finding "known, premise checked". If not, it is a finding against the decision.
- A documented limitation is not a free pass. Could it be solved cheaply today, and does it hold back something unrelated?
- Findings in `docs/REVIEW-LEDGER.md` are only raised again with a new concrete scenario, or when the stated reason no longer holds. Check at least three of those reasons.
- Every "fixed" claim in the log gets one spot check.

## 6. Judge and report

**Every finding passes, in order:**

1. **Self-test:** a concrete failure: a request, a wrong result, a visible effect. "Would be cleaner" and "best practice" are not findings.
2. **Trigger:** who causes it, under which condition, how likely.
3. **Evidence:** the command and its output, reproduced by you.
4. **Severity:**

| Level   | Meaning                                                                                                           |
| ------- | ----------------------------------------------------------------------------------------------------------------- |
| BLOCKER | Fix before anyone reads this: data loss, exploitable hole, broken setup, or a false claim in the README           |
| HIGH    | Reproducible with clear damage: anonymous 500 from trivial input, a broken core flow, a rule that can be bypassed |
| MEDIUM  | Real defect without acute damage: edge case, accessibility failure with user impact, missing test for a rule      |
| LOW     | Small, real and cheap. Tag `Quick-Win` or `Overcoding` with one line of reason                                    |

Rate from the reader's view: something a reader can trigger in five minutes ranks higher than a theoretical issue of the same technical weight. Never raise a level to get attention.

**Save the report** as `docs/reviews/<date>-<commit>.md`, untracked, for the author to decide about, and print the summary in the conversation:

- **A. Verdict:** commit, scope, machines with exit codes, and "Stand `<commit>`: n BLOCKER, n HIGH, n MEDIUM, n LOW". Say whether the run was blind and in a fresh context. Name the exact model running this review, read from your own system information rather than from the request or session title, and say so plainly if it differs from the requested one.
- **B. Findings** by severity: file and line, the failure, trigger, measured evidence, smallest fix with its consumers, and the test that must exist afterwards.
- **C. The matrix** with every cell filled.
- **D. Surviving mutants** and uncovered lines, each with its decision.
- **E. False claims:** every README or docs sentence that did not hold.
- **F. Missing machines:** which automated guard would have caught each finding, ranked by value. Do not add one that merely repeats a tool.
- **G. Explanation risk:** the hardest questions and the three likeliest live changes.
- **H. Not checked:** everything you did not measure, and why. This section is mandatory, and it is never empty.
- **I. LOW for triage:** at most ten, each with its tag.

Finish with the three things you would fix first.

## Fixing

Only after the author approves specific findings, and preferably in a separate session:

1. Write a failing test first, on the affected path, covering refused **and** allowed behaviour.
2. Make the smallest idiomatic fix, in small Conventional Commits, without pushing.
3. Revert the fix and confirm that the test turns red, then restore it.
4. If a guard should have caught it, extend the guard, not only the instance.
5. Answer for each fix: Is the failure gone for the user, or was only a part changed? Is any path now worse? Is this the best fix or the quickest? Does anything now promise more than the code delivers?
6. Run `composer check` from a clean state, and record the case in `CLAUDE.md` and `docs/AI-WORKFLOW.md`.

## Why these rules exist

Each comes from a real case in this project:

- Concurrency tests stayed green with the row lock removed, because the lock protected something else than assumed.
- Accessibility checks passed on 404 pages, because the tests assumed database ids.
- A failing focus test looked green, because the result line was cut from the output.
- axe reported zero issues while two contrast failures existed.
- A working local setup hid eight failures that only a clean clone showed.
- Seven review stages did not catch that `?status[]=done` answers an anonymous visitor with HTTP 500. Tests with valid input only cannot find that.
