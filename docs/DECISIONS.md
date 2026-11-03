# Architecture decisions

## 1. Stack

**Context:** The app is an application demo for Laravel developers. It needs a modern, idiomatic stack and PostgreSQL full-text search later on.

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
