---
name: report-cleanup
description: Review a report under docs/reports/ against the actual codebase, then remove items that are already implemented, weak, unimportant, exaggerated, or have no meaningful impact. Use when the user says "نظف تقرير X", "احذف المنفذ من تقرير X", "راجع تقرير X مقابل الكود", or "trim report X".
license: MIT
compatibility: opencode
metadata:
  workflow: docs/reports
  audience: maintainers
---

## What I do

I take a report file (typically under `docs/reports/`) and bring it back in sync with reality:

1. **Cross-check the report against the actual code** using `grep`, `read`, and `glob`.
2. **Delete** items that have already been implemented.
3. **Delete** items that are weak, exaggerated, or have no meaningful impact on the system.
4. **Keep** items that are still real, actionable, and have a clear security/quality value.
5. **Update** the report's date and produce a summary of what was removed and why.

## When to use me

Use me when the user says any of:
- "نظف تقرير `<file>.md`"
- "احذف المنفذ من تقرير `<file>.md`"
- "راجع تقرير `<file>.md` مقابل الكود"
- "trim report `<file>.md`"
- "تقرير X فيه أشياء منتهية / مبالغ فيها — احذفها"

## Workflow

### 1. Resolve and read the report

- Default location: `docs/reports/<file>.md`. If a full path is given, use it as-is.
- Read the entire file. Do not skip sections.
- Detect the report's structure:
  - Numbered top-level sections (`## 1.`, `## 2.`, ...) or thematic sections.
  - Subsections with severity tags (e.g. `### 1.1 خطورة: مرتفع`).
  - Tables listing affected files/lines.
  - A final action plan / "خطة العمل" section.

### 2. Build an item inventory

For every concrete, checkable claim, extract:
- `id` (section number)
- `claim` (short paraphrase in the user's language)
- `severity` (if stated)
- `evidence` (file path + line range, code snippet, or config key)
- `category` (one of: `security`, `performance`, `quality`, `structure`, `style`)

Skip meta sections (ملخص تنفيذي, خاتمة, خطة العمل) — they are not items to verify.

### 3. Verify each item against the codebase

For every item, run targeted checks. Examples:

| نوع البند | كيف تتحقق |
|-----------|-----------|
| كتلة `catch` فارغة في `path:lines` | `grep -n "catch" path` واقرأ الأسطر |
| استخدام `whereRaw` في `ProductController` | `grep -n "whereRaw\|havingRaw\|orderByRaw" app/Http/Controllers/API/ProductController.php` |
| `$guarded = []` في نموذج | `grep -n "guarded" app/Models/X.php` |
| `Sanctum expiration = null` | `grep -n "expiration" config/sanctum.php` |
| `XSS` عبر `{!!` | `rg "\{!!" resources/views/` |
| `setEnv` يكتب لـ `.env` | `grep -rn "setEnv\|EnvWriterService" app/` |
| فهارس قاعدة بيانات | اقرأ `database/migrations/` الحديثة |

Classify the result:
- ✅ **Implemented** → candidate for deletion.
- ❌ **Still present** → keep in report.
- ⚠️ **Partially fixed** → keep but note in summary; ask user.
- 🟡 **Disputed** → keep and ask user.

### 4. Identify weak / exaggerated items

Mark an item for deletion if **at least one** of these is true (and be conservative — when in doubt, keep it):

- **Severity mismatch:** rated `حرج` or `مرتفع` but the actual blast radius is small (e.g. an admin-only endpoint, an unused code path, a config key that is overridden in production).
- **Already mitigated upstream:** framework or package already handles it (e.g. Laravel's built-in CSRF for web routes, Sanctum's default stateful guard, Symfony's HTTP client validation).
- **Stylistic / cosmetic:** purely a code-style preference with no measurable impact (variable naming, comment style, file ordering).
- **Speculative:** "could be abused if X" with no realistic X in this codebase.
- **Duplicated:** same root cause already covered by another item or by a plan that has been completed.
- **Out of scope for this report:** e.g. an item in a *security* report that is actually a performance observation.

**Do NOT** delete:
- Items rated `حرج` or `مرتفع` unless you have hard evidence they are mitigated.
- Anything that affects authentication, authorization, payments, or PII handling.
- Anything flagged by the user as "keep" in the past.

When unsure, **ask the user** with a short list of borderline items. Do not auto-delete them.

### 5. Produce a removal summary

Before writing, show the user a table like:

```
| # | البند | السبب |
|---|-------|-------|
| 1 | $guarded = [] في PaypalPayment | تم إصلاحه — السطر 12 الآن ['id'] |
| 2 | Sanctum token_prefix فارغ | تم ضبطه في .env |
| 3 | تحذير cache طويل للصلاحيات | مبالغ فيه — الـ Spatie cache يُمسح يدوياً عند تغيير الدور |
| ... |
```

Then **wait for confirmation** before mutating the file. (Use `question` if there are > 5 borderline items.)

### 6. Rewrite the report

- Remove deleted items from their sections.
- Renumber the remaining items so the numbering is still continuous (1, 2, 3, ...).
- Update the "ملخص النتائج" table at the end (drop deleted rows).
- Update the "خطة العمل" / action plan section: drop items that are no longer applicable, keep the rest with their checkmarks.
- Update the report's date to today (`التاريخ: <today>`).
- Add a short "سجل التنظيف" appendix at the very end:

```md
---

## سجل التنظيف

**التاريخ:** <today>

| # | البند المحذوف | السبب |
|---|---------------|-------|
| 1 | ... | ✅ منفذ في ... |
| 2 | ... | 🗑️ مبالغ فيه / ضعيف التأثير |
```

### 7. Update related plans (optional)

If a `docs/plans/<NN>-*.md` exists for this report, ask the user whether to:
- Mark related plan items as `✅` and update its `docs/plans/README.md` entry, or
- Leave the plan files untouched.

Default: **leave plans untouched** unless the user asks.

## Output to the user

After finishing, return:

1. The new report path (same path, overwritten).
2. A 1-line count: "حُذف X بند (Y منفذ + Z مبالغ فيه / ضعيف)".
3. The full removal table.
4. A list of items that were borderline and that you kept (for transparency).
5. A confirmation prompt: "هل تريد تطبيق التغييرات على ملف `<file>.md` الآن؟" if step 5 was skipped.

## What I do NOT do

- I do not modify code in `app/`, `database/`, `tests/`, or `config/`.
- I do not silently delete `حرج` or `مرتفع` items — I always ask first.
- I do not touch reports outside `docs/reports/` unless the user gives an explicit path.
- I do not invent evidence. If I cannot verify an item in the code, I keep it and flag it as "unverified".
- I do not change the report's overall structure, language, or tone — only the items inside it.

## Tooling tips

- Use `grep` with `path` and `include` filters to scope searches.
- Use `read` with `offset`/`limit` for large files.
- Use `bash` with `rg` for fast repo-wide checks (e.g. `rg "\{!!" resources/views`).
- Always read the file in the location the user said, not a copy.
- Prefer a single `git diff` of the report at the end so the user can review.
