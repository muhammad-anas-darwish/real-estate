---
name: plan-generator
description: Generate one or more detailed, numbered implementation plans from a report file under docs/reports/. One plan per major issue (not one plan per report). Use when the user provides a report filename (e.g. 04-security-audit.md) and wants the report broken into actionable plans in docs/plans/ with matching entries in docs/plans/README.md.
license: MIT
compatibility: opencode
metadata:
  workflow: docs/plans
  audience: maintainers
  pattern: one-plan-per-major-issue
---

## What I do

I take a single report file under `docs/reports/` (an audit, analysis, or review) and break it into **one plan per major issue** — matching the existing repo pattern (e.g. `04-security-audit.md` produced plans #16, #19, #23 — not one mega-plan).

Each plan:
- Gets its own sequential number (continuing from the last).
- Lives at `docs/plans/<NN>-<slug>.md`.
- Is registered as its own line in `docs/plans/README.md` under a section named after the source report.

I do **not** generate one plan per report. I do **not** generate one plan per sub-bullet. I group by **independence + severity** so each plan is self-contained and shippable on its own.

## When to use me

Use me when the user says any of:
- "create plans for `<file>.md`"
- "build a plan for `<file>.md`"
- "generate plans for `<file>.md`"
- "اعمل plans لملف ..."
- "فكك تقرير X إلى خطط"

The source file is expected to live at `docs/reports/<file>.md` unless the user gives a full path.

## Workflow

### 1. Identify the source report

- Resolve the file. If only a filename is given, look in `docs/reports/`.
- Read the whole file. Do not skip sections.

### 2. Find the next plan number

- List files in `docs/plans/` matching `NN-*.md` (NN = 2-digit number).
- The next number is `max(existing) + 1`. Do not reset when gaps exist.
- If multiple plans will be generated, allocate a contiguous block: `next`, `next+1`, ..., `next+k-1`.
- Each plan gets a unique kebab-case slug derived from the issue (not from the source report).

Examples:
- `24-mass-assignment-guarded-empty.md` (not `24-security-audit.md`).
- `25-sanctum-token-expiration.md`.
- `26-permission-cache-ttl.md`.

### 3. Split the report into major issues

This is the heart of the skill. A **major issue** is a self-contained, shippable unit of work. Heuristics:

#### A major issue is...

- A top-level `##` section in the report that:
  - Rates a finding at **HIGH** or **CRITICAL** severity, **or**
  - Affects **multiple files / locations / models**, **or**
  - Requires its **own refactor / migration / config change**, **or**
  - Has its **own tests** independent of other findings.
- A group of LOW/MEDIUM findings that **share the same root cause** and can be fixed in one PR.

#### NOT a major issue (fold into a parent, or skip)...

- A LOW-severity finding whose fix is a **one-line change** (e.g. flip `$guarded` to `['id']`) — fold it into a "misc small fixes" plan only if there are 3+ such items; otherwise note it as a small item inside a related plan.
- A sub-bullet that is a **consequence** of another finding (e.g. "missing `whereRaw` parameter binding" is its own plan; "this causes the rating endpoint to return wrong results" is a symptom, not its own plan).
- A section marked "no issues" / "all good" / "future work" / "out of scope" — **skip**.
- A section that is **purely informational** (e.g. project size summary, methodology) — **skip**.

#### Special case: small / weak findings

If the report contains findings that are weak, exaggerated, or have no meaningful impact (per `report-cleanup`'s criteria), and the user has not asked me to keep them, I:
- Drop them silently (do not generate a plan, do not include in any plan).
- Mention them in the final report-back so the user knows they were skipped.

If unsure, ask the user with a short list of borderline findings **before** allocating plan numbers.

#### Existing plans overlap

Before allocating numbers, check `docs/plans/` for plans that already cover issues from the source report. Examples:
- `04-security-audit.md` already has #16, #19, #23 → skip those issues.
- A new plan #24 should only cover issues **not** already covered by #16, #19, #23.

If an issue is partially covered, reference the existing plan and only generate a new one for the uncovered parts.

### 4. Produce the plan split proposal (BEFORE writing files)

Show the user the proposed split before any file is created:

```
## خطة التقسيم المقترحة لتقرير `04-security-audit.md`

| # | الخطة | الأولوية | السبب (لماذا خطة منفصلة) |
|---|-------|:---:|-------|
| 24 | mass-assignment-guarded-empty | 🟡 low | 2 نماذج، تغيير بسيط لكن منفصل، اختبار مستقل |
| 25 | set-env-whitelist | 🔴 critical | كود حساس، EnvWriterService جديد، اختبارات RBAC |
| 26 | sanctum-token-expiration | 🔴 high | تغيير config + migration للرموز القديمة |
| 27 | permission-cache-ttl | 🟡 medium | تغيير config + مسح كاش + اختبارات TTL |
| 28 | login-otp-rate-limit | 🟡 medium | RateLimiter جديد + 2 routes |
| 29 | file-upload-max-size-and-lfi | 🟠 high | FormRequest rules + إزالة file_get_contents |
| 30 | misc-low-fixes | 🟢 low | تجميع 3 إصلاحات صغيرة |

**تم استبعاد:**
- `whereRaw` SQL injection → موجود في خطة #16
- `XSS` في Blade → موجود في خطة #19
- `catch` فارغة → موجود في خطة #23
- `Sanctum token_prefix` → ضمن #26
- `APP_DEBUG` للإنتاج → out of scope (devops)

**هل تريد تعديل هذه القائمة قبل ما أكتب الملفات؟**
```

**Wait for confirmation** before proceeding.

### 5. Draft each plan

For each plan in the approved split, follow the repo's established format (see `docs/plans/16-...md`, `19-...md`, `23-...md` for reference). Each plan MUST contain:

- Title: `# المهمة #<NN>: <short Arabic title>`
- Header block: الحالة, الأولوية, الجهد المقدّر, الاعتمادية, راجع (with line references into the source report).
- **"الوضع الحالي"** section summarizing the problem in the report.
- **Phased breakdown** (مرحلة 1, 2, ...) — each phase has a time estimate, concrete files, and code samples that match the codebase style.
- **"المهام التي يمكن تنفيذها بالتوازي"** section — explicitly list which phases/tasks can be run concurrently and why.
- **"معايير القبول"** checklist.
- A closing line stating: `> يجب تحديث هذا الملف (تغيير ✅ / ❌) عند إكمال كل مرحلة من مراحل الخطة.`
- State status as `❌ لم يبدأ` initially.

If the user has chosen a single plan (Option A — kept for backward compatibility), the split table is just one row.

### 6. Code samples must be realistic

- Use namespaces already present in the repo (`App\Services`, `App\Helpers`, `App\Repositories`, ...).
- Prefer extending work already in `docs/plans/16`, `19`, `23` where overlap exists — reference them instead of duplicating.
- Reference real file paths from the source report.

### 7. Update `docs/plans/README.md`

For each new plan, add one line. Group all new lines under a single new section for the source report:

```md
### 04-security-audit
* [plan24-mass-assignment-guarded-empty.md](24-mass-assignment-guarded-empty.md)... ❌
* [plan25-set-env-whitelist.md](25-set-env-whitelist.md)... ❌
* [plan26-sanctum-token-expiration.md](26-sanctum-token-expiration.md)... ❌
* [plan27-permission-cache-ttl.md](27-permission-cache-ttl.md)... ❌
* [plan28-login-otp-rate-limit.md](28-login-otp-rate-limit.md)... ❌
* [plan29-file-upload-max-size-and-lfi.md](29-file-upload-max-size-and-lfi.md)... ❌
* [plan30-misc-low-fixes.md](30-misc-low-fixes.md)... ❌
```

- If a section for that report already exists (with the older table format from a prior run), **replace its body** with the new simple list — keep the `### <source-report-name>` header.
- Do **not** touch other sections; preserve all existing content.
- The simple list format (header + bullets) is the canonical format going forward.

### 8. Report back to the user

After writing all files, return:

1. The list of created plan paths and numbers.
2. A 1-line summary per plan.
3. Which plans can be parallelized (cross-plan parallelism).
4. The exact README diff added.
5. A list of items the user might want to know were dropped or folded.

## Numbering rules

- Plans are 2-digit zero-padded (`01`, `02`, ..., `24`, `25`).
- Continue from the highest existing number, never restart.
- All plans from the same report get **contiguous** numbers.
- If `docs/plans/` has both `02-repositories-instance.md` and `02-repository-instance-conversion.md`, that is a known historical collision — pick the max.

## Concurrency section template

```md
## ⚡ المهام التي يمكن تنفيذها بالتوازي

| المرحلة | لماذا يمكن تشغيلها بالتوازي | تعتمد على |
|---------|------------------------------|-----------|
| مرحلة 1 | ... | لا شيء |
| مرحلة 3 + 4 | ... | مرحلة 2 |
```

For **cross-plan** parallelism, the report-back should explicitly state:

```
**Plans that can be executed in parallel:**
- #24 (mass-assignment) + #27 (cache-ttl) + #28 (rate-limit) — no dependencies
- #25 (set-env) must come first — other plans consume EnvWriterService
```

## Update-on-completion footer template

Append at the very end of every generated plan:

```md
---

> **⚠️ تعليمات الصيانة:** يجب تحديث هذا الملف (تغيير ✅ / ❌) فور إكمال كل مرحلة من مراحل الخطة، وتحديث `docs/plans/README.md` ليعكس الحالة الجديدة.
```

## Example: what the split looks like for `04-security-audit.md`

If the user runs this skill today on `04-security-audit.md` (last plan = #23), the proposed split would cover the **remaining** issues (after subtracting what's already in #16, #19, #23):

| # | Plan | Severity | Why its own plan |
|---|------|:---:|---|
| 24 | set-env-whitelist | 🔴 critical | New `EnvWriterService`, RBAC tests, 3 controller call-sites |
| 25 | sanctum-token-expiration-and-prefix | 🟠 high | Config + migration for old tokens + prefix rotation |
| 26 | permission-cache-ttl | 🟡 medium | Config change + cache invalidation strategy + tests |
| 27 | login-otp-rate-limit | 🟡 medium | New `RateLimiter::for(...)` blocks + tests |
| 28 | file-upload-max-size-and-lfi | 🟠 high | FormRequest rule + remove `file_get_contents` from Blade |
| 29 | mass-assignment-guarded-empty | 🟢 low | 2 lines, but folded alone (trivial) |
| 30 | misc-low-fixes (coupon, token_prefix, etc.) | 🟢 low | 3 trivial one-liners bundled |

The user can drop #29 and #30 if they want a leaner set, or merge them.

## What I do NOT do

- I do not implement the plans — only produce the markdown.
- I do not modify code in `app/`, `database/`, or `tests/`.
- I do not delete or rewrite existing plan files.
- I do not generate a plan for an issue already covered by an existing plan — I cross-reference instead.
- I do not skip the confirmation step. The split table is shown first; the user approves before any file is written.
- I do not invent issues. If the report does not contain a finding, no plan for it.
