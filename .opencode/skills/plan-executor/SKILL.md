---
name: plan-executor
description: Execute a plan file (under docs/plans/) phase by phase — read the plan, implement the code, mark phases as done, run tests, and flip the README entry from ❌ to ✅. Use when the user says "نفذ خطة 24", "implement plan 24", "ابنِ خطة X", or "build plan X".
license: MIT
compatibility: opencode
metadata:
  workflow: implementation
  audience: developers
  companion: plan-reviewer
---

## What I do

I take a single plan from `docs/plans/<NN>-<slug>.md` and execute it **phase by phase**. After each phase I:

1. Implement the code changes described in that phase.
2. Mark the phase as `✅` in the plan file.
3. After all phases, flip the README line from `❌` to `✅`.
4. Offer to commit each phase with the `commit-message` skill.

I do **not** skip phases. I do **not** implement phases in a different order than the plan specifies unless the plan explicitly allows it. I do **not** implement multiple plans at once unless the user explicitly asks.

## When to use me

Use me when the user:
- Says "نفذ خطة 24" / "implement plan 24" / "ابنِ خطة 24".
- Says "build plan 24" / "execute plan 24" / "اعمل خطة 24".
- Names a plan by its file: `docs/plans/24-set-env-whitelist.md`.
- Says "نفذ هذه الخطة" right after `plan-generator` finished.

## Workflow

### 1. Identify and read the plan

The user gives me:
- A plan number (`24` → look up `docs/plans/24-*.md`).
- A full path or slug.

Find the file, read it fully. If not found, list `docs/plans/` and ask which one.

From the plan I extract:
- `الحالة` (status) — must be `❌ لم يبدأ` or `🟡 جزئياً`. If already `✅`, tell the user and stop.
- `الأولوية` (priority).
- `الاعتمادية` (dependencies).
- The phases (مرحلة 1, 2, ...) with their code samples.
- `معايير القبول` (acceptance criteria).

### 2. Pre-flight checks

- **Dependencies:** if the plan says `يعتمد على #17`, verify #17 is ✅. If not, warn the user and ask whether to proceed or execute the dependency first.
- **Existing plans:** check `docs/plans/README.md` for the plan's status. If it's already ✅, stop.
- **Clean working tree?** Not required — the user may have ongoing work. Warn if the tree is dirty but do not block.
- **Read the source report:** the `راجع:` field in the plan header points to the source report. Read the relevant lines so I understand the original problem — not just the fix.

### 3. Execute — one phase at a time

For each phase in order:

#### a. Announce the phase
```
🟡 Starting phase 1: <phase name> (est. <time>)
```

#### b. Read the files I am about to change
- Use `read` to see the current state.
- If the code already matches the "after" example in the plan → skip this phase, mark as `✅` with a note `(already in place)`.

#### c. Make the changes
- Use `edit` for each file change.
- If the plan provides code samples, follow them closely — but adapt to the actual current state of the file (line numbers in the plan may be stale).
- Create new files with `write` where the plan calls for them.
- If the plan says "composer require X", run the command.
- If the plan says "php artisan migrate", run the command (with user confirmation for destructive migrations).
- If the plan says "npm install X", run it.

#### d. Mark the phase done in the plan file

Find the checkbox for this phase's acceptance criterion and flip it:
```
- [ ] مرحلة 1: إنشاء EnvWriterService   →   - [x] مرحلة 1: إنشاء EnvWriterService ✅
```

If the plan uses a different checkbox format, match it.

#### e. Pause for confirmation on high-risk changes

If the phase involves:
- Database migrations (especially `down()` or data deletion).
- Changes to auth / Sanctum / config keys.
- Changes that touch payment / money flows.
- Changes that modify `.env` writing.

→ **Ask the user** before applying. Show a 1-line summary and wait for "go".

#### f. If the plan says "run tests after this phase"

```
php artisan test --filter=TheRelevantTest
```

Report the result. If tests fail:
- Show which tests fail.
- Fix the code if it's my fault.
- If the test was already broken, note it but do not fix pre-existing failures.

### 4. After all phases

#### a. Run the full test suite (quick subset)

```bash
php artisan test --profile 2>&1 | tail -40
```

If it fails, fix my changes — do not leave a broken suite.

#### b. Mark the plan as complete

Edit the plan file: change `الحالة:` from `❌ لم يبدأ` (or `🟡 جزئياً`) to `✅ مكتمل`.

#### c. Update docs/plans/README.md

Find the row for this plan and flip `❌` → `✅`:

```
* [plan24-...md](24-...md)... ✅
```

#### d. Offer to commit

Show the user a summary and ask: "commit now?".

If yes, I invoke the conventions of the `commit-message` skill:
- Type: `feat` (new feature), `fix` (security/regression fix), `refactor` (structural), `chore` (config/deps).
- Scope: derived from the plan slug (e.g. `set-env-whitelist` → `env`).
- Message: `<type>(<scope>): <Arabic or English title from the plan>`

Commit, but **do not push**.

### 5. Report back

```
✅ Plan #24 finished: <plan title>

Phases completed:
1. ✅ Create EnvWriterService (20 min)
2. ✅ Update Controller::setEnv (10 min)
3. ✅ Update 3 controller call-sites (15 min)
4. ✅ Tests + README (10 min)

Files changed: 5 created, 3 modified
Tests: 4 new, all passing

README updated: docs/plans/README.md — #24 now ✅
```

## Interaction with other skills

- **`commit-message`** — I follow its conventions for commit messages. I do **not** load it unless the user says "commit".
- **`test-case-generator`** — if the plan says "generate tests for X" and does not provide test code, I invoke the `test-case-generator` conventions (happy + edge + failure + auth).
- **`tech-advisor`** — if I encounter a design decision the plan does not cover, I pause and ask. I do **not** auto-decide.
- **`query-reviewer`** — if the plan adds new queries, I apply the query-reviewer checks mentally (no N+1, eager load, parameter binding).
- **`plan-reviewer`** — the user can run this skill **after** me to verify my work.

## Concurrency within a plan

If the plan has a `المهام التي يمكن تنفيذها بالتوازي` table, I ask the user:

```
This plan allows phases 1 + 2 to be parallel. Do you want me to run them in parallel now, or one at a time?
```

If they say parallel, I do them together — but I still mark them separately in the file.

## Reversibility

If the user says "تراجع" / "undo" / "rollback phase 2", I:
- Read the plan file to see what phase 2 changed.
- Use `git diff` to see the exact files.
- `git checkout -- <files>` to revert.
- Flip that phase's checkbox back to `- [ ]`.
- Do **not** touch other phases.

## Codebase-specific notes

- **Laravel 11 + Vue 3 + MySQL + Sanctum.** Plan code samples assume this stack.
- **Repository / Service split** is in place (plans #01, #02). New code goes into Services/Repositories, not controllers.
- **Plans are in Arabic** (headings, descriptions). I read Arabic; I may write code comments in Arabic if the plan uses Arabic. Commit messages are in English (per `commit-message`).
- **Tests** use the framework detected by the project (PHPUnit or Pest). Follow the existing test structure in `tests/Feature/...` and `tests/Unit/...`.
- **Routes** live in split files (`routes/{admin,shop,seller,rider,api,api_*.php}`). New routes go in the right file.
- **Config** changes go in `config/*.php` with an `.env`-backed default, never a hardcoded value.
- **Migrations** are additive, with a working `down()`. Do not edit deployed migrations.

## What I do NOT do

- I do not implement multiple plans in one go unless the user says "نفذ 24 و 25 و 26".
- I do not skip phases. If the user wants to skip, they say "تخطى مرحلة 3" — I ask for confirmation and note it in the plan.
- I do not rewrite the plan's design. I follow the plan as closely as the actual code allows.
- I do not push to remote. I stage and commit, push is separate.
- I do not change the plan's scope. If I find a related but out-of-scope issue, I tell the user and suggest filing a bug with `bug-report-formatter`.
- I do not run `composer require` or `npm install` without user confirmation if it introduces new dependencies.
