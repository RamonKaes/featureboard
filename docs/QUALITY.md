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

| Pair                     | Light   | Dark    |
| ------------------------ | ------- | ------- |
| Muted text on background | 5.33:1  | 7.85:1  |
| Muted text on muted      | 4.89:1  | 5.77:1  |
| Primary button           | 17.18:1 | 17.18:1 |
| Error text               | 4.77:1  | 5.20:1  |
| Badge "Neu"              | 8.24:1  | 12.08:1 |
| Badge "Geplant"          | 9.29:1  | 12.83:1 |
| Badge "In Arbeit"        | 8.13:1  | 13.47:1 |
| Badge "Erledigt"         | 8.47:1  | 13.36:1 |
| Input border (needs 3:1) | 3.23:1  | 4.30:1  |
| Focus ring (needs 3:1)   | 3.74:1  | 3.94:1  |

**Limits of the tools:** axe did not report the two contrast failures. It skips `aria-hidden` text, and it does not check borders. A green axe run is therefore not proof of conformance.

**Not verified:**

- No test with a real screen reader such as NVDA or VoiceOver.
- No complete manual keyboard walk through every page. The skip link and focus changes are tested automatically.
- Kit components based on Reka UI, such as dialogs and menus, were only checked by axe.
- Hover states were not measured.

## Security

| Area            | Result                                                                                                                                                    |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Mass assignment | `is_admin`, `status`, `votes_count`, `user_id` and `product_id` are not fillable. Tested through registration and forms.                                  |
| Authorization   | A test fails when a route is neither on the public list nor behind `auth`. Policies are checked in routes and actions.                                    |
| Rate limiting   | Votes 30 and searches 60 requests per minute per user, tested. Login uses Fortify's limit of 5 per minute.                                                |
| XSS             | No `v-html` or unescaped Blade output in the app's own code. A browser test confirms that HTML in user text does not run.                                 |
| Hostile input   | Every route, field and path parameter is attacked with arrays, NUL bytes, invalid UTF-8 and overflowing ids, as guest and as admin, with no server error. |
| SQL injection   | Search input reduced to letters and digits and passed as a binding, tested with injection strings.                                                        |
| Data exposure   | Resources list their fields explicitly, tested that other users' email addresses do not appear.                                                           |
| Dependencies    | `composer audit` and `npm audit` report no known vulnerabilities.                                                                                         |

**Not covered:** Security headers such as a Content Security Policy, and production hardening. The seeder creates accounts with a known password and is meant for local use only.

## Static analysis

Larastan runs on level 9 with no baseline, no `ignoreErrors` and no `@phpstan-ignore` comments in the analysed paths. Tests are not analysed, because Pest's `$this` needs extra setup.
