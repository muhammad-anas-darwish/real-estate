---
name: commit-message
description: Fast git commit workflow. Auto-stages all changes, analyzes the diff, and produces a single Conventional Commits message in English. Use when the user says "commit", "اعمل commit", "احفظ التغييرات", or "stage and commit".
license: MIT
compatibility: opencode
metadata:
  workflow: git
  audience: developers
  speed: fast
---

## What I do

A **3-step** workflow that takes seconds:

1. `git add -A` — stage everything in the repo.
2. `git diff --cached --stat` + `git diff --cached` — analyze what changed.
3. Produce **one** commit with a Conventional Commits message in **English**, and run `git commit -m "..."`.

No interactive back-and-forth. No menus. One output, one commit.

## When to use me

Use me when the user says:
- "commit" / "اعمل commit" / "احفظ التغييرات"
- "commit this" / "احفظ هذا"
- "stage and commit" / "جهّز للكومت"

Do **not** use me if the user said "just stage" or "don't commit" — in that case, stop at `git add -A` and report.

## Workflow

### 1. Pre-flight checks (silent, fast)

```bash
git rev-parse --is-inside-work-tree        # must be a repo
git status --porcelain                     # anything to commit?
git log -1 --pretty=%s                     # for style reference
```

If not a repo → stop, tell the user.
If nothing to commit → stop, tell the user "working tree clean".

### 2. Stage

```bash
git add -A
```

Never ask the user what to stage. They invoked this skill to commit *everything* pending.

### 3. Analyze the staged diff (fast, capped)

```bash
git diff --cached --stat
git diff --cached | head -400
```

- The `--stat` gives the file list (used for the commit footer / scope).
- The `head -400` caps the diff at ~400 lines — enough to derive a message without burning context.
- If the diff is larger than the cap, say so in the commit body ("large refactor across N files").

### 4. Derive the commit message

#### Format (Conventional Commits, English only)

```
<type>(<scope>): <subject>

<body — optional, only when non-obvious>

<footer — optional, only for breaking changes or refs>
```

**Allowed types** (pick one):

| type | when |
|------|------|
| `feat` | new user-facing feature |
| `fix` | bug fix |
| `refactor` | restructuring with no behavior change |
| `perf` | performance improvement |
| `test` | tests only |
| `docs` | docs only |
| `chore` | tooling, deps, config, .gitignore |
| `style` | formatting, whitespace only |
| `build` | build system / CI |

**Scope** is the most-affected top-level area, lowercased:
- `orders`, `products`, `users`, `auth`, `cart`, `checkout`, `payments`, `admin`, `shop`, `api`, `migrations`, `config`, `deps`, `ci`, `repo` (for repo-level work).

If the change spans 3+ unrelated areas, omit the scope: `refactor: ...`.

**Subject** rules:
- Imperative mood ("add", "fix", "refactor" — not "added", "fixes").
- ≤ 72 chars.
- Lowercase.
- No trailing period.
- One-line summary of the **dominant** change, not a list.

**Body** rules:
- Only added if the subject alone is ambiguous.
- Wrap at 100 chars.
- Explain **why**, not what.
- If listing items, use bullets.
- Never paste code blocks.

**Footer** rules:
- `BREAKING CHANGE: <one line>` when applicable.
- `Refs: #123` for issue / plan references.
- `Refs: docs/plans/24-...md` when the commit closes a plan phase.

#### Type inference rules (fast)

Look at the file list from `--stat`:

| File pattern | Likely type |
|--------------|-------------|
| `app/Models/...` (new) | `feat` |
| `app/Http/Controllers/...` (new methods) | `feat` |
| `app/Http/Controllers/...` (bug-fix edits) | `fix` |
| `app/Repositories/...` (restructuring) | `refactor` |
| `database/migrations/...` | `feat` (new schema) or `chore` (squash/dump) |
| `database/migrations/...` (additive, no data change) | `feat` |
| `database/migrations/...` (data change) | `feat` with `BREAKING CHANGE` |
| `tests/...` only | `test` |
| `*.md` only (docs/, *.md) | `docs` |
| `composer.json`, `package.json`, `yarn.lock` only | `chore(deps)` or `build(deps)` |
| `.github/...`, `Dockerfile`, `vite.config.js` | `ci` or `build` |
| `config/...` (security-related) | `fix` |
| `config/...` (tuning only) | `refactor` |
| `resources/views/...` (new view) | `feat` |
| `resources/views/...` (typo / style) | `style` |
| Mixed `app/` + `tests/` | type from the dominant `app/` change; tests are implied |

When in doubt between `refactor` and `feat`: if the user is adding a **new public method, route, or table**, it's `feat`. If they are restructuring existing code with the same behavior, it's `refactor`.

### 5. Commit

Show the user the message in the chat **once**, then commit. Do not ask for confirmation — the user invoked the skill to commit.

```bash
git commit -m "<subject>" -m "<body if any>" -m "<footer if any>"
```

If the commit hook (e.g. `commitlint`, `php-cs-fixer --dry-run`) fails, surface the failure to the user and **do not** amend / force / skip hooks.

### 6. Report

Reply with:

```
✅ Committed: <hash short> — <subject>

<one-line stat: N files, +X / -Y>
```

If the commit failed, reply with the error and the staged message so the user can retry.

## Examples

**Diff:** new `app/Services/NotificationService.php`, 2 controllers updated to use it, 1 test.

```
feat(notifications): introduce notification service for order events

- Centralize push / email dispatch behind a single service.
- Replace direct event dispatches in OrderController.
- Add unit coverage for the service.
```

**Diff:** `config/sanctum.php` (set expiration), `.env.example` (add prefix), nothing else.

```
fix(security): set sanctum token expiration and prefix
```

**Diff:** `OrderRepository.php` split into 4 smaller repos, no behavior change, 1 test file moved.

```
refactor(orders): split OrderRepository into focused repositories

Decompose the 488-line OrderRepository into OrderReadRepository,
OrderWriteRepository, OrderPaymentRepository, and OrderShippingRepository.
No behavior change; all existing tests pass.
Refs: docs/plans/18-decouple-order-repository.md
```

## What I do NOT do

- I do not push. `git push` is the user's call.
- I do not amend a previous commit unless the user explicitly says "amend".
- I do not skip hooks (`--no-verify`).
- I do not run `git add -p` / interactive staging.
- I do not write the message in Arabic. The message is **always** in English. (The user can ask for Arabic in a follow-up if they want.)
- I do not squash, rebase, or rewrite history.
- I do not commit if the working tree is clean — I tell the user and stop.
