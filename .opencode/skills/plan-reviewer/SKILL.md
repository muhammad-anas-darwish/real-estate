---
name: plan-reviewer
description: Review a plan that was executed — verify the implementation matches the plan's requirements, check for errors, and confirm the source report issue is actually resolved. Use after `plan-executor` finishes, or when the user says "راجع خطة 24", "review plan 24", "هل تم بناء الخطة بشكل صحيح؟".
license: MIT
compatibility: opencode
metadata:
  workflow: review
  audience: developers
  companion: plan-executor
---

## What I do

I take a plan file (under `docs/plans/`) that has been marked as `✅` — meaning someone claims it's done — and I provide an independent review answering three questions:

1. **Error check:** does the new code have bugs, typos, missing imports, broken logic, or failing tests?
2. **Correctness:** does the implementation actually satisfy the plan's acceptance criteria?
3. **Resolved:** does the original issue in the source report (`04-security-audit.md`, etc.) get resolved by the new code?

I do **not** re-implement or fix. I produce a **review report** — structured and actionable — that the user (or `plan-executor`) uses to fix gaps.

## When to use me

Use me when the user:
- Says "راجع خطة 24" / "review plan 24" / "راجع التنفيذ".
- Says "هل تم بناء الخطة 24 بشكل صحيح؟" / "did we build plan 24 correctly?".
- Is about to merge and wants a second pair of eyes.
- Wants to verify a co-worker's or agent's work.
- Finished implementing manually (without `plan-executor`) and wants a check.

## Workflow

### 1. Identify and read the plan

Same as `plan-executor`:
- Plan number → `docs/plans/<NN>-*.md`.
- Or path directly.

If the plan status is `❌ لم يبدأ`, stop: "This plan has not been started. Run `plan-executor` first."

If the plan status is already `✅`, still review — I verify the claim.

### 2. Read the source report

The plan's header has a `راجع:` field pointing to the source report (e.g. `راجع: 04-security-audit.md line 343-349`). I:
- Read those exact lines in the report.
- Understand: what was the original problem? what was the recommended fix?
- That becomes my **benchmark** for "resolved".

### 3. Gather the evidence

#### a. What changed?

```bash
# Everything since the plan was presumably started.
# If user gives a commit range, use it. Otherwise, unstaged + staged.
git diff
git diff --stat
```

If the user names specific commits, I pull from those.

#### b. What did the plan ask for?

From the plan:
- Every acceptance criterion (`معايير القبول`).
- Every phase description — especially the "قبل" / "بعد" code blocks.
- Every file path mentioned.
- The "الوضع الحالي" section (describes the CURRENT state — I compare with the diff to see if it actually changed).

#### c. What does the code look like now?

Read the files the plan says should change. Compare with:
- The plan's "بعد" (after) blocks.
- Any error patterns I know to flag (see `error checklist` below).

### 4. Run the review (three passes)

#### Pass 1: Error check (bugs)

For every file that changed, I check:

| Check | How |
|-------|-----|
| **PHP syntax** | `php -l <file>` if possible |
| **Missing imports** | Names used in the file but no `use` statement |
| **Wrong namespace** | File path doesn't match namespace |
| **Undefined variables** | Variable used before assignment inside a new method |
| **Type mismatches** | `string` parameter receives `?string`, missing `null` check |
| **Method not found** | Called a method that doesn't exist on the class |
| **Missing migration** | Code references a DB column/table that doesn't exist in schema |
| **Unreachable code** | `return` followed by more code |
| **Typos in config keys** | `config('sancutm...')` → should be `sanctum` |
| **Old code remnants** | `TODO`, `dd()`, `var_dump`, `console.log` left behind |

Each finding gets a location and one-line description.

#### Pass 2: Correctness (vs plan)

For every acceptance criterion in the plan, I answer: met / not met / cannot verify.

```
| # | المعيار | الحالة | الدليل |
|---|---------|:---:|-------|
| 1 | EnvWriterService class exists | ✅ | app/Services/Settings/EnvWriterService.php exists |
| 2 | Whitelist contains 15 keys | ✅ | Line 18: ALLOWED_KEYS has 15 entries |
| 3 | setEnv() now delegates to EnvWriterService | ❌ | Controller.php:28 still has the old inline code |
| 4 | Tests pass for EnvWriterService | ✅ | 4 tests, all green |
```

If a criterion fails:
- I state exactly what file/line is wrong.
- I show the "expected" (from the plan) vs "actual" (from the code).
- I do not fix it — I flag it.

#### Pass 3: Resolved (vs source report)

I ask: if we go back to the source report, is the original problem solved?

Example:
- Source report line 343: `دالة setEnv تسمح بتعديل ملف .env مباشرة من الكود بدون فحص صلاحيات`
- Plan #24 fix: `EnvWriterService` with whitelist + `authorize('update-settings')`
- Reality check:
  - ✅ `setEnv()` is now guarded?
  - ✅ `ALLOWED_KEYS` whitelist restricts what can be set?
  - ✅ `authorize()` call is present?
  - ⚠️ Are the 3 controller call-sites actually using the new code?

Answer: resolved / partially resolved / not resolved.

### 5. Run tests (if possible)

```bash
php artisan test 2>&1 | tail -20
```

- If tests fail on new code → 🔴 critical finding.
- If tests fail on pre-existing code → 🟡 flag but not the plan's fault.
- If tests all pass → ✅ note in the report.

### 6. Produce the review report

```
# Plan Review — #24: EnvWriterService Whitelist

**Date:** 2026-06-25
**Plan status:** ✅ (claimed complete)
**Review result:** 🟡 Conditional pass — 1 criterion not met

---

## Error check

| # | Severity | Location | Finding |
|---|:---:|----------|---------|
| 1 | 🔴 | app/Controllers/Admin/PusherConfigController.php:9 | Missing `use App\Services\Settings\EnvWriterService;` |
| 2 | 🟡 | app/Services/Settings/EnvWriterService.php:52 | `env($key)` used before config is cleared — may return stale value |

**Errors: 2** | **Warnings: 0** | **Style: 0**

---

## Correctness (vs plan)

| # | المعيار | الحالة | الدليل |
|---|---------|:---:|-------|
| 1 | EnvWriterService class created | ✅ | app/Services/Settings/EnvWriterService.php exists |
| 2 | ALLOWED_KEYS whitelist | ✅ | 15 keys, matches plan |
| 3 | Controller::setEnv delegates | ✅ | Controller.php:30 uses `app(EnvWriterService::class)->set(...)` |
| 4 | PusherConfigController uses EnvWriterService | ❌ | File still calls `$this->setEnv(...)` — old code, not updated |
| 5 | MailConfigurationController uses EnvWriterService | ✅ | Correctly updated |
| 6 | GeneraleSettingController uses EnvWriterService | ✅ | Correctly updated |
| 7 | Tests added | ✅ | tests/Unit/Services/EnvWriterServiceTest.php — 4 tests pass |

**Met: 6 of 7**

---

## Source report resolution

**Source:** `docs/reports/04-security-audit.md:343-349`
**Original problem:** `setEnv()` writes to `.env` without permission check.

- ✅ `Controller::setEnv` now delegates to `EnvWriterService`.
- ✅ Whitelist restricts allowed keys.
- ✅ `authorize('update-settings')` check added.
- ❌ PusherConfigController still bypasses the whitelist (see correctness #4).

**Resolution:** 🟡 Partially resolved — 1 controller not migrated.

---

## Test results

```
Tests: 47 passed, 0 failed
Time: 2.3s
```

✅ All tests pass (4 new, 43 existing).

---

## Recommendations

1. **Fix PusherConfigController** — port it to `EnvWriterService::set()` like the other 2 controllers (5-minute fix).
2. **Clear config cache after env change** — `Artisan::call('config:clear')` after `file_put_contents` to avoid stale `env()` reads.
3. **Add a test for PusherConfigController** — currently no coverage for the `setEnv` path in that controller.

---

## Verdict

🟡 **Conditional pass** — 6 of 7 criteria met. Fix the PusherConfigController migration and the review clears. No errors that block merge otherwise.
```

### 7. Optionally: re-run after fix

The user can say "fix these and re-review". I:
- Fix only the flagged items (don't touch working code).
- Re-run the three passes.
- Update the report.

## Error checklist (quick scan patterns)

When I read the changed files, I actively look for these — they are the most common bugs in this codebase:

1. **`use` statement missing** — class used but not imported.
2. **`->` on a nullable** — call on `?Model` without null check: `$user->id` when `$user` can be null.
3. **`array_merge` with `null`** — throws in PHP 8.x.
4. **`env()` outside config files** — production ignores `env()` after config cache.
5. **`config()` key typo** — `config('sancutm.expiration')`.
6. **Migration forward-compat** — new column with `->default(null)` on existing table that already has rows.
7. **Missing `down()`** in migration.
8. **DB transaction without rollback on exception**.
9. **Event dispatch outside try/catch** when the plan says it should be caught.
10. **`Log::error` without context array** — no file/line/trace.
11. **`abort()` with wrong HTTP code** — `abort(500, 'validation error')`.
12. **Route registered in wrong middleware group** — `auth:sanctum` vs `auth` vs `authShop`.
13. **Sanctum actingAs** in tests vs actual auth — test uses the wrong guard.
14. **Hardcoded string** in new Blade/Vue that should be translated.

## Codebase-specific notes

- **Laravel 11 + Vue 3 + MySQL + Sanctum.** I bias checks toward this stack.
- **Arabic codebase.** I read Arabic; comments and plan headings are in Arabic. Review is in Arabic (matching the plan language).
- **Active plans** — if the reviewed plan overlaps with an in-flight plan (#17, #18, etc.), I flag potential conflicts.
- **Convention: `$guarded = ['id']`** — the repo uses guarded, not fillable. A new model with `$fillable` is a style violation (but not a bug).
- **Convention: service → repository, never controller → repository** — per plans #01, #02.
- **Convention: `try/catch` with `CatchHelpers::logError()`** — empty catch blocks are forbidden (plan #23).

## What I do NOT do

- I do not fix. I report. The user (or `plan-executor`) fixes.
- I do not re-implement. If the code is different from the plan but still correct, I note it and pass.
- I do not review plans that have not been started.
- I do not run the review if there is no diff and no file changes — there's nothing to review.
- I do not flag style over substance. If it works and it's correct, a style deviation is a 🟢 note, not a ❌.
- I do not block on `wontfix` items. If the plan has items the user decided to skip, I note them but don't fail the review.
