# Featureboard: working rules

Feature request and roadmap board, built as a job application demo. Experienced Laravel developers will read this code, and the author must explain and change it live.

Talk to the author in German. Code, comments, commit messages and the README are in English.

## Rules

- **Measure, never guess.** Verify versions, package names, commands and config before using them. Say so openly when something cannot be verified.
- **Best solution, not the cheapest.** Prefer idiomatic Laravel. When several good options exist, name them briefly and recommend one.
- **Work in stages and stop.** After each stage, report and wait. Start the next stage only after "weiter" or explicit approval. Never merge stages or work ahead.
- **Scope is sacred.** Build only what the current stage asks for. Put other ideas in `docs/IDEAS.md`.
- **Business rules are test-driven.** Write a failing Pest test first, then the implementation.
- **Everything is green after each stage.** That means tests, Pint, Larastan, frontend checks and the build.
- **Small commits.** Use Conventional Commits, several per stage. Push only when told to.
- **Ask before risky steps.** That covers global installs, changes outside this folder, deletions and resetting Docker volumes.
- **No secrets in the repo.** `.env` stays out, `.env.example` stays complete.
- **Accessibility from the start.** Use semantic HTML, labels, focus states and keyboard support. Check the WCAG 2.2 AA target by measuring it.

## Commands

```bash
./vendor/bin/sail up -d                # start the app on http://localhost:8000
./vendor/bin/sail artisan migrate      # migrate the development database
./vendor/bin/sail artisan migrate:fresh --seed  # reset the database and load demo data
./vendor/bin/sail composer test        # Pest
./vendor/bin/sail composer lint        # Pint, Larastan, Vite+ check, vue-tsc
./vendor/bin/sail composer format      # apply Pint and Vite+ fixes
./vendor/bin/sail composer test:browser  # Pest browser tests, needs npm run browsers:install once
./vendor/bin/sail composer check       # lint, test, build and browser tests
./vendor/bin/sail npm run dev          # Vite dev server
```

Run PHP checks through Sail. `DB_HOST=pgsql` only resolves inside the Sail network.

## Reviews

`/big-review` runs the full audit in `.claude/commands/big-review.md`. Start it in a new session: code gets audited by someone who did not build it. Reports go to `docs/reviews/`, which is not committed.

## Lessons learned

Each rule comes from a concrete case.

- **Starter kit features are chosen once.** `composer create-project` ran `install:features` with defaults, and the command then deleted itself, so all auth features stayed. To choose features, set `LARAVEL_INSTALLER_DEFER_HOOKS=1` during `create-project`.
- **`laravel/pao` changes tool arguments for AI agents.** `pest --drift` failed with an argument error because pao appends `--no-output` when it detects an agent. Set `PAO_DISABLE=1` when a tool needs its exact arguments or full output.
- **Local green is not enough.** Eight tests passed locally but failed on a fresh clone because an old `public/build` hid a missing Vite manifest. Replay CI steps in a fresh clone when changing the toolchain.
- **Check framework APIs before using them.** `Collection::getOrFail()` was used from memory in a seeder, and it does not exist. Look methods up in `vendor/`.
- **Prove that a test can fail.** Concurrency tests stayed green with the row lock removed. Remove the protection temporarily and watch the test turn red before trusting it.
- **Never assume database ids in tests.** Sequences survive rolled back tests. Accessibility checks on `/requests/1` tested a 404 page and passed. Look ids up and assert the page content first.
- **Read the full test result.** A failing test looked green because the result line was cut from the output. Check the exit code.
- **A green tool is not proof.** axe missed two contrast failures, one in `aria-hidden` text and one on input borders. Calculate what the tool does not cover.
- **Test hostile input, not only valid input.** `?status[]=done` answered visitors with HTTP 500 after hundreds of green tests. Read the routes from the router, so that new ones are attacked too.
- **Verify after starting something.** A session asked to use another model ran on the default, and nobody checked until minutes later. Read back what was really set.
- **Bind every form field the same way.** A `:value` next to a `v-model` in one form slot erased the description on every keystroke in the title, and only one fill order was tested.
- **Check the kit's `.gitignore`.** It ignored `CLAUDE.md`, which this project must commit.
