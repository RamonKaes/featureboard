# Quality audit

Results of the audit in stage 7, measured on 2026-11-03. Target: WCAG 2.2 AA.

## Accessibility

**How it was measured:**

- `tests/Browser/AccessibilityTest.php` runs axe through the Pest browser plugin on all pages at its strictest level, in light and dark mode, as guest, user and admin.
- A static scan of the Vue files.
- Contrast ratios calculated from the colors rendered in the browser.

**Problems found and fixed:**

| Problem                                                     | WCAG   | Found by    | Fix                                           |
| ----------------------------------------------------------- | ------ | ----------- | --------------------------------------------- |
| Muted text on muted background, 4.35:1                      | 1.4.3  | Calculation | `--muted-foreground` to 42% lightness, 4.89:1 |
| Input borders, 1.26:1 light and 1.31:1 dark                 | 1.4.11 | Calculation | `--input` raised, 3.23:1 and 4.30:1           |
| Positive `tabindex` in login and registration               | 2.4.3  | axe, scan   | Removed                                       |
| Password toggle not reachable by keyboard (`tabindex="-1"`) | 2.1.1  | Scan        | Removed                                       |
| Auth pages without `main` landmark                          | 1.3.1  | axe         | Layout uses `main`                            |
| English pages in a document declared German                 | 3.1.1  | Review      | Translated                                    |
| No way to skip the header                                   | 2.4.1  | Review      | Skip link                                     |
| Page changes silent, focus lost                             | 2.4.3  | Review      | Focus moves to `main` on a new path           |
| Focus lost after a status change                            | 2.4.3  | Review      | Focus moves to the status area or heading     |
| Form errors not announced                                   | 4.1.3  | Review      | `role="alert"` in `InputError`                |
| Required fields not marked in text                          | 3.3.2  | Review      | Note above the form                           |
| Settings pages without `h1`                                 | 1.3.1  | Review      | Heading is an `h1`                            |

**Final contrast ratios:**

| Pair                                        | Light   | Dark    |
| ------------------------------------------- | ------- | ------- |
| Muted text on background                    | 5.33:1  | 7.85:1  |
| Muted text on muted                         | 4.89:1  | 5.77:1  |
| Primary button                              | 17.18:1 | 17.18:1 |
| Error text                                  | 4.77:1  | 5.20:1  |
| Badge "Neu"                                 | 8.24:1  | 12.08:1 |
| Badge "Geplant"                             | 9.29:1  | 12.83:1 |
| Badge "In Arbeit"                           | 8.13:1  | 13.47:1 |
| Badge "Erledigt"                            | 8.47:1  | 13.36:1 |
| Field and outline button border (needs 3:1) | 3.23:1  | 4.30:1  |
| Focus ring (needs 3:1)                      | 3.74:1  | 3.94:1  |

**Limits of the tools:** axe did not report the two contrast failures. It skips `aria-hidden` text, and it does not check borders. A green axe run is therefore not proof of conformance.

**Not verified:**

- No test with a real screen reader such as NVDA or VoiceOver.
- No complete manual keyboard walk through every page. The skip link and focus changes are tested automatically.
- Kit components based on Reka UI, such as dialogs and menus, were only checked by axe.
- Hover states were not measured.

## Security

| Area            | Result                                                                                                                                                                            |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Mass assignment | `is_admin`, `status`, `votes_count`, `user_id` and `product_id` are not fillable. Tested through registration and forms.                                                          |
| Authorization   | A test fails when a route is neither on the public list nor behind `auth`. Policies are checked in routes and actions.                                                            |
| Rate limiting   | Per minute and user: 30 votes, 60 searches, 10 submissions. Per address: 10 registrations an hour, 6 reset requests a minute. Tested. Login uses Fortify's limit of 5 per minute. |
| XSS             | No `v-html` or unescaped Blade output in the app's own code. A browser test confirms that HTML in user text does not run.                                                         |
| Hostile input   | Every route, field and path parameter is attacked with arrays, NUL bytes, invalid UTF-8 and overflowing ids, as guest and as admin, with no server error.                         |
| SQL injection   | Search input reduced to letters and digits and passed as a binding, tested with injection strings.                                                                                |
| Data exposure   | Resources list their fields explicitly, tested that other users' email addresses do not appear.                                                                                   |
| Dependencies    | `composer audit` and `npm audit` report no known vulnerabilities.                                                                                                                 |

**Not covered:** A Content Security Policy, and production hardening. The seeder creates accounts with a known password and is meant for local use only.

## Static analysis

Larastan runs on level 9 with no baseline, no `ignoreErrors` and no `@phpstan-ignore` comments in the analysed paths. Tests are not analysed, because Pest's `$this` needs extra setup.

## Review of commit bf053a1

A review in a fresh session with Claude Opus 5.5 found 4 blockers, 6 high, 16 medium and 8 low findings, with all machines green. This fixed all blockers and high findings, each with a test that failed first:

| Finding                                         | Fix                                                                                |
| ----------------------------------------------- | ---------------------------------------------------------------------------------- |
| Typing a title erased the description           | `v-model` on the textarea, browser tests in either order and after an error        |
| Contrast below 4.5:1 on four spots              | Colors raised, `tests/Browser/ContrastTest.php` calculates every visible text node |
| README claims too broad or wrong                | Sentences corrected, missing page and query test added                             |
| Capitals in an email locked the account out     | Lowercased before validation, login round trip tested                              |
| Large `page` and malformed cookie gave HTTP 500 | Page limited, cookie checked against known values, guard extended                  |
| Foreign Host header poisoned the reset link     | `trustHosts()`, active outside local and test environments                         |
| No limit on submissions and registration        | Rate limits and a test that every writing route has one or a stated exemption      |

A second round fixed six medium findings, also test first:

| Finding                                                  | Fix                                                                       |
| -------------------------------------------------------- | ------------------------------------------------------------------------- |
| Focus fell to the body after a vote                      | The button takes it back after the response                               |
| Failed auth forms lost the focus and did not link errors | Focus moves to the first invalid field, linked through `aria-describedby` |
| Two `h1` on settings pages, one English                  | Removed, and the accessibility test counts one `h1` per page              |
| Long words overflowed public pages                       | `overflow-wrap: anywhere` on the body, tested at 320 and 1280 px          |
| A test could not notice a reset that saves nothing       | The test logs in with the new password and fails with the old one         |
| English error pages without a way back                   | German pages for every status                                             |

A third round fixed the rest that had a real benefit, and `docs/REVIEW-LEDGER.md` lists what was deliberately left, with reasons:

| Finding                                          | Fix                                                                |
| ------------------------------------------------ | ------------------------------------------------------------------ |
| No limit on reset requests                       | 6 per minute and address on both routes                            |
| Sessions survived a password change              | `authenticateSessions()` ends them                                 |
| Deletion text promised too much, and data stayed | Text corrected, sessions and reset tokens removed                  |
| The last admin could delete the account          | Refused with a validation error                                    |
| Bcrypt cut off long passwords silently           | More than 72 bytes are refused                                     |
| Missing security headers                         | `SecurityHeaders` middleware, globally. No Content Security Policy |
| Appearance choice did not announce its state     | `aria-pressed`                                                     |
| Pages behind the end showed contradicting text   | 404                                                                |

Moderation stays a documented limitation.
