---
name: launch-checklist
description: Generate a tailored pre-launch verification checklist for a Laravel feature, endpoint, refactor, or release. Covers code, config, security, performance, observability, deployment, and rollback. Use when the user says "اعمل checklist للإطلاق", "launch checklist for X", "جاهز للنشر؟", or "pre-flight check".
license: MIT
compatibility: opencode
metadata:
  workflow: release
  audience: developers
---

## What I do

I produce a **pre-launch checklist** for a specific feature, endpoint, refactor, or release. The checklist is **tailored to the change**, not a generic template. I scan the diff, the affected files, the routes, the migrations, the tests, and the config, then I emit only the items that actually matter for *this* change.

Two variants:
- **Standard** (default) — covers everything below at a moderate depth.
- **Hotfix** — drops to a 30-minute smoke test for an emergency deploy.

## When to use me

Use me when the user:
- Says "اعمل checklist للإطلاق" / "launch checklist" / "pre-flight".
- Asks "هل أنا جاهز للنشر؟" / "ready to ship?".
- Is about to deploy a refactor, a new feature, a security fix, or a migration.
- Wants a paper trail of what was verified.

## Workflow

### 1. Identify the change

The user may give:
- A branch name → read `git diff main...HEAD --stat`.
- A list of files / paths.
- A feature name (e.g. "the new flash sale endpoint").
- "the work I just did" → fall back to `git diff --stat` (unstaged + staged).

If still unclear, ask **one** question.

### 2. Decide the variant

| User signal | Variant |
|-------------|---------|
| "hotfix", "urgent", "production down", "asap" | Hotfix (lean) |
| Default, or "full", "thorough" | Standard |
| "release", "v1.2.3", "client demo" | Standard + extra polish |

### 3. Read the change

- `git diff --stat` — file list.
- `git diff` — code changes.
- Read the modified files fully if they are short; otherwise focus on the public surface (controllers, services, routes, migrations, config, tests).
- Check `phpunit.xml` / `package.json` for test scripts.
- Check `database/migrations/` for new migrations.
- Check `routes/` for new routes.
- Check `config/` for new config keys.
- Check `lang/` for new strings (i18n regression risk).
- Check `resources/views/` and `resources/js/` for new UI surfaces.

### 4. Compose the checklist

I always include the categories below. **I skip a category only if the change provably does not touch it.** Each item is a checkbox `- [ ]` so the user can copy-paste it into a tracker.

#### A. Code & tests

- [ ] All modified files compile (PHP `php -l`, JS `npm run build` if applicable).
- [ ] New code has tests; modified code has updated tests.
- [ ] Test suite passes locally (`php artisan test` or the project's script).
- [ ] No new linter / static analysis warnings (`phpstan`, `eslint`, `pint`).
- [ ] No commented-out code, no `dd()`, no `var_dump`, no `console.log` left in.
- [ ] No TODO / FIXME without an associated ticket reference.

#### B. Database

- [ ] Migrations are **additive** (no destructive changes without a clear rollback plan).
- [ ] Indexes added for any new `where` / `orderBy` columns (cross-check with `query-reviewer`).
- [ ] Foreign keys have proper `onDelete` / `onUpdate`.
- [ ] Seeders are idempotent (can run multiple times safely).
- [ ] Backfill scripts (if any) are documented and tested on a snapshot of production data.
- [ ] Migration has a working `down()` (or is explicitly part of a squash).

#### C. Security (always include — even if change is "just a UI fix")

- [ ] No new `whereRaw` / `havingRaw` / `orderByRaw` with user input.
- [ ] No new `{!! !!}` on user-controlled strings.
- [ ] New routes have the right middleware (auth, role, throttle, CSRF where applicable).
- [ ] New FormRequests validate everything; no `nullable` on sensitive fields by accident.
- [ ] No new secret / key / password in code or `.env.example` (use `config()`).
- [ ] Authorization checked at the policy or service level, not just middleware.
- [ ] `APP_DEBUG=false` confirmed for production env.

#### D. Performance

- [ ] No N+1 introduced (cross-check with `query-reviewer` output if available).
- [ ] Hot endpoints have `with()` / `withCount()` for the relations they render.
- [ ] No `->get()` / `->all()` on tables that can grow without pagination.
- [ ] No `Cache::remember` with user-specific keys that would leak across users.
- [ ] Queue is used for any operation > 100ms or with third-party I/O.

#### E. Configuration & environment

- [ ] New config keys have a default in `config/*.php`.
- [ ] New env keys are added to `.env.example` (with a safe default, **never a real value**).
- [ ] No hard-coded URLs / paths that should be config.
- [ ] `php artisan config:cache` does not error.
- [ ] `php artisan route:cache` does not error (if using closure-free routes).

#### F. Observability

- [ ] New failure paths emit a `Log::warning` / `Log::error` (no empty catch blocks — see plan #23).
- [ ] New external calls have a timeout and a retry strategy.
- [ ] Important business events fire a domain event (OrderCreated, PaymentFailed, ...).
- [ ] New admin / shop actions appear in the audit log (if such a log exists).
- [ ] Sentry / error tracker is wired (or explicitly out of scope).

#### G. UI / i18n

- [ ] New user-facing strings are in `lang/`, not hardcoded.
- [ ] RTL layout verified for Arabic (mirror the layout, alignment, icons).
- [ ] Form errors display in the active locale.
- [ ] Email / notification templates render in both languages.
- [ ] No broken images / 404 assets (run `npm run build` and check `public/build`).

#### H. Deployment & rollback

- [ ] Migration is forward-compatible (can run before code deploy).
- [ ] Feature flag in place if the change is high-risk (Laravel `feature()` helper, env flag, or config toggle).
- [ ] Rollback plan written: "to revert, run migration X down + redeploy previous tag".
- [ ] Database backup taken immediately before deploy.
- [ ] Deploy window scheduled outside peak hours (or justified otherwise).
- [ ] On-call notified.

#### I. Smoke test (run after deploy, in production)

- [ ] Health check returns 200 (`/up`).
- [ ] Login / register flow works.
- [ ] The changed endpoint(s) respond as expected.
- [ ] A representative user journey from start to finish works (e.g. browse → cart → checkout → pay).
- [ ] No 5xx in the error tracker for 15 minutes post-deploy.
- [ ] No spike in `Log::error` count for 15 minutes post-deploy.

#### J. Communication

- [ ] Changelog / release notes updated.
- [ ] Stakeholders notified (Slack, email, status page — whichever applies).
- [ ] Support team has a one-liner about the change.
- [ ] Customer-facing docs updated (if user-visible).

### 5. Tailor it

The user does not need 100 items for a 5-line CSS fix. I:

- Remove entire categories the change provably does not touch.
- Keep only the items that have a non-trivial chance of biting.
- Add a custom **"Watch out for"** section at the end with anything specific to *this* change (e.g. "this migration runs ~3 minutes on a 10M-row table — schedule accordingly").

### 6. Output

```
# Launch Checklist — <change name>

**Variant:** Standard | Hotfix
**Branch / scope:** <git branch or path>
**Generated:** <YYYY-MM-DD>
**Risk level:** 🟢 low | 🟡 medium | 🔴 high

## Risk assessment
<one paragraph: what could go wrong, and the biggest unknown>

## Pre-deploy

### Code & tests
- [ ] ...

### Database
- [ ] ...

### Security
- [ ] ...

### Performance
- [ ] ...

### Configuration & environment
- [ ] ...

### Observability
- [ ] ...

### UI / i18n
- [ ] ...

### Deployment & rollback
- [ ] ...

## Post-deploy smoke test (run in production)
- [ ] ...

## Watch out for
- ...

## Rollback plan
<one paragraph: the exact steps to revert if something goes wrong>

## Sign-off
- [ ] Dev
- [ ] Reviewer
- [ ] QA (if applicable)
- [ ] On-call
```

## Codebase-specific notes

- The project uses **Laravel 11 + Vue 3 + MySQL + Sanctum**. I bake these in by default.
- The project is undergoing heavy refactor (plans #01–#23). I cross-check the change against the active plans and flag if the change conflicts with an in-flight refactor.
- The project has `.env.testing` and `phpunit.xml`. I assume the test command is `php artisan test` and the lint command is whatever the user has in `composer.json` (`pint`, `phpstan`, etc. — I detect it).
- The project uses `config/sanctum.php`, `config/cache.php`, `config/queue.php`. I always include the related config check when the change touches auth / caching / queues.
- The project has feature flags in some places (e.g. `CheckSubscription` middleware). I do not assume one specific flag system.

## Hotfix variant

When the user signals urgency, I drop the categories to a minimum:

```
# Hotfix Checklist — <change name>

## Before deploy
- [ ] Diff reviewed by ≥ 1 other person
- [ ] Test added for the bug, even if minimal
- [ ] No DB migration unless absolutely required
- [ ] If migration required: backup taken, rollback script ready
- [ ] `php -l` on changed PHP files
- [ ] `php artisan test` passes

## After deploy
- [ ] Bug reproduction case is fixed
- [ ] No new errors in Sentry / logs for 10 minutes
- [ ] Rollback plan ready: <one line>
```

## What I do NOT do

- I do not run the deploy. I prepare the checklist and the rollback plan.
- I do not run the smoke test in production. I list what to check; the user runs it.
- I do not sign off on my own checklist. The user (and ideally a reviewer) ticks the boxes.
- I do not include items that are obviously irrelevant just to fill space. A short, sharp list beats a long, generic one.
- I do not invent "watch out for" items. If I have nothing specific to add, I omit the section.
