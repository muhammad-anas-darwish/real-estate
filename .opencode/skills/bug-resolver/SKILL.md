---
name: bug-resolver
description: Companion to `bug-report-formatter`. Takes a bug report file (under docs/bugs/), investigates the root cause by reading the code, proposes a fix, and updates the bug file with the resolution metadata: confirmed root cause, fix description, affected files, tags, and status flipped to `resolved`. Use when the user says "احصل على bug", "fix bug BUG-2026-06-25-001", "ابحث عن السبب", or names a bug file.
license: MIT
compatibility: opencode
metadata:
  workflow: bug-fixing
  audience: developers
  storage: docs/bugs/  (gitignored)
  companion: bug-report-formatter
---

## What I do

I am the **second half** of the bug-tracking flow. The user has a bug file in `docs/bugs/` (created by `bug-report-formatter`). I:

1. **Read** the bug file.
2. **Investigate** the suspected files in the codebase.
3. **Confirm or revise** the root cause hypothesis.
4. **Propose a fix** (concrete code change).
5. **Update** the bug file with:
   - Confirmed root cause.
   - Fix description (what changed, where).
   - Final list of affected files (often 1–3, the *real* ones — supersedes the "most likely" list from the report).
   - Refined tags (added any new ones discovered).
   - Status flipped to `resolved` (or `in-progress` while I'm still working).
   - `date_resolved`.
6. **Apply the fix** if the user asks.

I do **not** auto-fix. I propose, then act on confirmation.

## When to use me

Use me when the user:
- Says "احصل على bug" / "ابحث عن سبب الخطأ في docs/bugs/BUG-...md".
- Names a specific bug file: "fix BUG-2026-06-25-001".
- Has just fixed a bug manually and wants the file updated: "سجّل الحل في ملف الـ bug".
- Says "حدث ملف bug X — تم حلها" with the resolution already known.

## Workflow

### 1. Resolve the bug file

The user gives me either:
- A bug ID: `BUG-2026-06-25-001`.
- A file path: `docs/bugs/BUG-2026-06-25-001-paypal-webhook-404.md`.

If by ID, list `docs/bugs/BUG-<date>-<id>*.md` and pick the match. If ambiguous, ask.

### 2. Read the bug file

Read the full file. Capture:
- `status` (current).
- `severity`.
- The `error_text` (raw error / stack trace).
- The `related_files` (the suspected list).
- The `root_cause_hypothesis` (the initial guess).

### 3. Investigate — read the suspected files

For each candidate file in `related_files`:
- `read` the full file.
- Cross-reference the error text / class names / function names / route names.
- If the bug text mentions a specific class / function / line, jump there.

If the initial list is empty, search the codebase using the keywords from the scenario, error, and tags.

### 4. Confirm or revise the root cause

- **Confirmed:** the suspected cause matches what the code does. State the exact line(s) and the exact mechanism.
- **Revised:** the initial guess was off. State the new cause, and explain briefly why the old guess was wrong.
- **Unresolved:** I cannot pin it down after a reasonable read. State what I tried, what I ruled out, and what additional info I need (logs, repro details, runtime context).

I always state confidence (`high` / `medium` / `low`) on the final cause.

### 5. Propose the fix

The fix proposal is concrete:
- File(s) to change.
- A code snippet (before / after) when the change is non-trivial.
- Any test that should be added or updated.
- Any migration / config / env change required.
- Any side effect on existing behavior (e.g. "this changes the response shape — front-end will need an update").

I show the user the proposal **before** applying it. If the user says "go" / "طبّق" / "اعملها", I apply.

### 6. Update the bug file

#### Status transitions

| Current | User signal | New status |
|---------|-------------|------------|
| `open` | I'm still investigating | `in-progress` |
| `open` / `in-progress` | Fix applied, verified | `resolved` |
| `open` / `in-progress` | Not actually a bug / not worth fixing | `wontfix` |

When status flips to `resolved`:
- Set `date_resolved` to today.
- Add commits if known.
- Fill in `resolution`.

#### What the `resolution` block looks like

```md
## Resolution
**status:** resolved
**date_resolved:** 2026-06-25
**root_cause:** The webhook route was registered in `routes/api_webhooks.php`, but that file was never included in the `RouteServiceProvider`'s `map()` calls after the routes split (per plan #13). PayPal's IPN hit a 404 because Laravel never knew the route existed.

**fix:**
- Added `require base_path('routes/api_webhooks.php');` to `app/Providers/RouteServiceProvider.php` inside the `mapApiRoutes()` method, wrapped in the same `api` middleware group.
- Confirmed via `php artisan route:list --path=webhooks/paypal` — route is now registered.
- Added a Feature test `tests/Feature/Webhooks/PayPalWebhookTest.php` covering: valid IPN signature → 200, invalid → 401, missing route → would now fail at boot (defense in depth).

**files_changed:**
- `app/Providers/RouteServiceProvider.php`
- `routes/api_webhooks.php` (added proper middleware group, was missing)
- `tests/Feature/Webhooks/PayPalWebhookTest.php` (new)

**tags_added:** [routing, route-service-provider, test-coverage]
```

#### Updating the frontmatter

Add / change these fields in the YAML frontmatter:

```yaml
status: resolved
date_resolved: 2026-06-25
root_cause_confirmed: true   # or false if still hypothesis
resolution_confidence: high  # high / medium / low
related_files_final:         # supersedes related_files in the body
  - app/Providers/RouteServiceProvider.php
  - routes/api_webhooks.php
  - tests/Feature/Webhooks/PayPalWebhookTest.php
tags:
  - paypal
  - webhook
  - routing
  - production
  - test-coverage
commits:
  - abc1234   # fix: register api_webhooks route group
  - def5678   # test: paypal webhook feature coverage
```

The original `related_files` in the body remains for history; the final, confirmed list goes in `related_files_final` in the frontmatter.

### 7. Update the index

If `docs/bugs/README.md` exists, update the row for this bug:
- Status icon: 🟢 resolved.
- Add `date_resolved`.

### 8. Report back

```
✅ Bug resolved: docs/bugs/BUG-2026-06-25-001-paypal-webhook-404.md

Summary:
- Root cause: webhook routes file not loaded by RouteServiceProvider.
- Fix: 3 files changed (1 prod, 1 routes, 1 test).
- Commits: abc1234, def5678.
- Status: 🟢 resolved (2026-06-25).
```

## Investigation patterns (by bug type)

I have **fast paths** for common categories. When the bug file has a tag, I jump to the right pattern.

| Tag | Where to look first |
|-----|---------------------|
| `paypal`, `stripe`, `payment-gateway` | `app/Services/Payment/`, webhook controllers, `config/services.php`. |
| `sanctum`, `auth`, `token` | `config/sanctum.php`, middleware `auth:sanctum`, `PersonalAccessToken` model. |
| `rbac`, `role`, `permission` | `app/Http/Middleware/CheckPermission.php`, Spatie config, `Role`/`Permission` models. |
| `xss`, `blade` | `resources/views/**/*.blade.php`, look for `{!! !!}`. |
| `csrf` | `app/Http/Middleware/VerifyCsrfToken.php`, `$except` list. |
| `n+1`, `performance`, `slow-query` | `app/Repositories/`, `app/Http/Controllers/`, look for `->get()` without `with()`. |
| `queue`, `job`, `failed-job` | `app/Jobs/`, `app/Console/Kernel.php`, supervisor config, `failed_jobs` table. |
| `cache` | `app/Http/Middleware/CheckPermission.php` (long TTL on permissions), `config/cache.php`. |
| `migration`, `schema` | `database/migrations/`, latest files. |
| `env`, `config` | `config/`, `.env.example`, `env()` calls. |
| `mobile`, `app` | `routes/api*.php`, `app/Http/Controllers/API/`. |
| `vue`, `frontend` | `resources/js/`, `package.json`. |
| `i18n` | `lang/`, hardcoded strings in `resources/views/` and `resources/js/`. |
| `rider` | `app/Http/Controllers/Rider/`, `routes/rider.php`. |
| `seller` | `app/Http/Controllers/Seller/`, `routes/seller.php`. |

## Codebase-specific notes

- The project is **Laravel 11 + Vue 3 + MySQL + Sanctum**.
- Active refactor (plans #01–#23) means some bugs are *symptoms* of in-flight structural changes. I cross-reference the bug with active plans and flag the cross-reference in the resolution notes.
- I do not invent commit hashes. If the user has not committed yet, I leave the `commits` field empty and remind them to fill it after committing.
- The project uses Conventional Commits. When the user says "commit the fix", the recommended message type is `fix(<scope>): <subject>` — see the `commit-message` skill.

## What I do NOT do

- I do not auto-apply the fix. I propose and wait for "go".
- I do not delete the original `related_files` in the bug body. It is preserved as history; the confirmed list goes in the frontmatter as `related_files_final`.
- I do not push, commit, or open a PR. The user (or `commit-message` skill) does that.
- I do not change the `id` of the bug.
- I do not create new bug files. If the investigation reveals a *different* bug, I tell the user to file a new one with `bug-report-formatter`.
- I do not change `severity`. If I think it should change, I propose and let the user decide.
- I do not silently flip status. I always state the new status in the chat before mutating the file.
