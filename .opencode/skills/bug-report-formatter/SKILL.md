---
name: bug-report-formatter
description: Take a raw bug description (error text, stack trace, screenshot caption, or user complaint) and turn it into a structured bug file under docs/bugs/. The file captures: status (open/resolved), date appeared, tags, top 3 most relevant files, root cause hypothesis, scenario / repro steps. The docs/bugs/ folder is gitignored — these are personal / local notes. Use the companion `bug-resolver` skill after the fix to update the file with resolution metadata. Use when the user says "سجّل bug", "احفظ هذا الخطأ", "bug report for X", or pastes an error.
license: MIT
compatibility: opencode
metadata:
  workflow: bug-tracking
  audience: developers
  storage: docs/bugs/  (gitignored)
---

## What I do

I take a **raw, possibly messy bug report** (error message, stack trace, customer complaint, screenshot caption, Slack paste) and turn it into a **structured markdown file** stored under `docs/bugs/`.

Each bug file captures:

| Field | Required | Description |
|-------|:---:|-------------|
| `id` | ✅ | Short slug, e.g. `BUG-2026-06-25-001` |
| `title` | ✅ | One-line summary, imperative |
| `status` | ✅ | `open` / `in-progress` / `resolved` / `wontfix` |
| `severity` | ✅ | `critical` / `high` / `medium` / `low` |
| `date_appeared` | ✅ | `YYYY-MM-DD` (today by default) |
| `date_resolved` | optional | `YYYY-MM-DD`, set when status flips to `resolved` |
| `reported_by` | optional | "user", "QA", "monitoring", or a name |
| `tags` | ✅ | List of short tags (kebab-case) |
| `scenario` | ✅ | Plain-language description of what the user was doing |
| `repro_steps` | ✅ | Numbered list of steps to reproduce |
| `expected` | ✅ | What should happen |
| `actual` | ✅ | What actually happens |
| `error_text` | optional | Raw error message, stack trace, log line |
| `related_files` | ✅ | **Top 3 most likely files** (or 1 if obvious) — best guess from the error, refined after investigation |
| `root_cause_hypothesis` | ✅ | Best current guess, with confidence (`high` / `medium` / `low`) |
| `resolution` | optional | Filled in later by the `bug-resolver` skill |
| `commits` | optional | List of commit hashes that fixed it |
| `notes` | optional | Free-form notes |

The bug file is created **without making me touch the code**. The companion `bug-resolver` skill does the actual fix and fills in the resolution block.

## When to use me

Use me when the user:
- Pastes an error message and says "سجّل bug" / "احفظ الخطأ" / "سجل هذا" / "وثّق هذا".
- Says "bug report: <text>".
- Reports a problem from QA, a customer, or monitoring.
- Wants to track an issue locally without filing it on GitHub / Jira.

## Workflow

### 1. Resolve the storage location

- Default folder: `docs/bugs/`.
- The folder is already in `.gitignore` (created in the repo setup).
- I do **not** push, commit, or stage these files. They are local notes.
- File naming: `BUG-YYYY-MM-DD-<NNN>-<slug>.md` where `NNN` is a 3-digit zero-padded sequence for that day, found by listing existing `BUG-YYYY-MM-DD-*.md` files.
- Slug: kebab-case, ≤ 40 chars, derived from the title.

Example: `docs/bugs/BUG-2026-06-25-001-paypal-webhook-404.md`

### 2. Extract the fields from the raw input

Be **liberal** in what you accept. The user might give:

- A stack trace only.
- A one-line complaint ("paypal payment fails sometimes").
- A screenshot description.
- A Slack message with multiple symptoms.
- A log line.

I infer the fields as best I can. For anything I cannot infer with confidence, I write `"<unknown — please fill>"` so the user can complete it.

#### Inference rules

| Field | How to infer |
|-------|--------------|
| `title` | First 60 chars of the user's text, cleaned up, imperative voice. |
| `severity` | `critical` if money / auth / data loss; `high` if user-facing feature broken; `medium` if degraded UX; `low` if cosmetic. If unclear, default `medium`. |
| `tags` | From keywords: `paypal`, `sanctum`, `xss`, `n+1`, `rbac`, `queue`, `cache`, `blade`, `vue`, `i18n`, `mobile`, `webhook`, `migration`, `rider`, `seller`, `admin`, `shop`, `api`, etc. |
| `scenario` | Restate the user's words in 1–2 sentences, in third person. |
| `repro_steps` | If the user gave steps, copy them as a numbered list. Otherwise: "1. Perform the action described in `scenario`." |
| `expected` | Infer from the feature's normal behavior; if unknown, write `<unknown — please fill>`. |
| `actual` | The exact symptom / error text. |
| `error_text` | The raw error / stack trace / log line, in a fenced block. |
| `related_files` | Grep the codebase for keywords from the error / class names / function names. Return the **top 1–3** most likely files. If I have no signal, return an empty list and note "to be determined after investigation". |
| `root_cause_hypothesis` | My best guess, with `confidence: high/medium/low`. If I have no signal, write "Not yet investigated." |
| `date_appeared` | Today by default. The user can override. |

### 3. Investigate (lightweight)

Use `grep` and `read` to look for the suspected files. The user expects at least:

- A **list of 1–3 candidate files**, not 10.
- A **root cause hypothesis** that is one or two sentences, not a full essay.
- **Confidence** stated explicitly.

If the error text mentions a specific class / function / route, find it. If not, search by the symptom (e.g. "order cancel" → search for `cancel` in `app/Services/OrderService.php`).

### 4. Output format (the file content)

```md
---
id: BUG-2026-06-25-001
title: PayPal webhook returns 404 in production
status: open
severity: high
date_appeared: 2026-06-25
date_resolved: 
reported_by: monitoring
tags: [paypal, webhook, routing, production]
---

## Scenario
Customer completes a PayPal payment; the IPN callback from PayPal hits our server with HTTP 404 instead of being processed.

## Steps to reproduce
1. Create a test order in staging with PayPal sandbox.
2. Complete the payment in the PayPal popup.
3. Wait for the IPN callback.

## Expected
Order is marked paid, customer receives confirmation email, stock is reduced.

## Actual
IPN POST returns 404. Order stays in "pending payment" state. Customer sees no confirmation.

## Error / log
```
[2026-06-25 14:22:01] production.ERROR: PayPal IPN endpoint not found
  POST /api/webhooks/paypal → 404
```

## Related files (most likely)
- `routes/api.php` — webhook route registration
- `app/Http/Controllers/Webhook/PayPalController.php` — webhook handler
- `app/Providers/RouteServiceProvider.php` — route loading order

## Root cause hypothesis
`confidence: medium` — the webhook route may not be registered under the `api` middleware group because of how `routes/api.php` is split into `api_*.php` files (per plan #13 — split Vue router — applied similarly to backend). Need to verify whether `api_webhooks.php` is loaded.

## Resolution
*(to be filled by the `bug-resolver` skill after the fix)*

## Commits
*(list of commit hashes that fixed it)*

## Notes
- First seen in production on 2026-06-25 14:22 UTC.
- Affects all PayPal orders since the last deploy.
```

### 5. Save the file

```bash
# 1. Confirm docs/bugs/ exists and is gitignored
ls docs/bugs/                      # OK
git check-ignore docs/bugs/        # should print: docs/bugs/

# 2. Write the file
write docs/bugs/BUG-YYYY-MM-DD-NNN-<slug>.md
```

### 6. Also maintain a tiny `docs/bugs/README.md` index

So the user can see all open bugs at a glance. Append a row:

```
| ID | Title | Severity | Status | Date |
|----|-------|:---:|:---:|:---:|
| BUG-2026-06-25-001 | PayPal webhook 404 | 🟠 high | 🟡 open | 2026-06-25 |
```

The index is also gitignored.

### 7. Report back

Reply with:

```
✅ Bug recorded: docs/bugs/BUG-2026-06-25-001-paypal-webhook-404.md

Summary:
- Severity: 🟠 high
- Top suspect: routes/api.php (route not registered under api middleware)
- Next step: use `bug-resolver` to investigate and fix.
```

## Codebase-specific notes

- The project is **Laravel 11 + Vue 3**. Bugs may touch backend (PHP), frontend (Vue), database (migrations), or infra (env, queue).
- I bias toward Laravel-specific tags: `middleware`, `route`, `controller`, `service`, `repository`, `event`, `listener`, `job`, `notification`, `mail`, `blade`, `validation`, `auth`, `rbac`, `sanctum`, `rate-limit`, `queue`, `cache`, `session`, `migration`, `seeder`, `factory`, `eloquent`, `relation`, `scope`, `observer`, `policy`, `form-request`, `pagination`, `eager-load`, `n+1`, `xss`, `csrf`, `rce`, `lfi`, `xxe`, `env`, `config`.
- I check the active plans in `docs/plans/` — if the bug is in a known refactor area (e.g. `OrderRepository` for plan #18), I tag it accordingly and add a cross-reference in the `notes` field.

## What I do NOT do

- I do not modify code. Investigation is read-only.
- I do not commit, push, or stage anything in `docs/bugs/`.
- I do not file the bug on GitHub / Jira / Linear — local only.
- I do not fill in `resolution` or `commits` — that's `bug-resolver`'s job.
- I do not delete old bug files. The user can do that manually.
- I do not auto-increase severity based on a single occurrence; I let the user override.
