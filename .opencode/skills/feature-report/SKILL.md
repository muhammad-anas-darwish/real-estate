---
name: feature-report
description: Take a small, simple feature idea described in plain language and turn it into a non-technical business report (overview, business value, user scenarios, business rules, workflow, edge cases, acceptance criteria) saved at docs/ideas/<feature-slug>/report.md. Use when the user has a fresh idea they want to add to the system and wants a structured business document to review BEFORE any technical planning. Pairs with plan-generator. Triggers: "لدي فكرة", "سجّل فكرة", "وثّق ميزة", "feature report", "feature brief", "اكتب تقرير عن ميزة", "عندي ميزة أريد إضافتها".
license: MIT
compatibility: opencode
metadata:
  workflow: docs/ideas
  audience: non-technical
  pattern: one-report-per-feature
  companion: plan-generator
  model-skill: daily-report
---

## What I do

I take a **small, simple feature idea** described in plain language and produce a **non-technical business report** that:

- Captures the business value, target users, and user scenarios.
- States the business rules, edge cases, and acceptance criteria explicitly.
- Is saved as `docs/ideas/<feature-slug>/report.md` for the user to review.

The report is written for a **product manager, stakeholder, or the user themselves** — never for a developer. It contains **no API, no database, no framework, no code, no class names, no file paths** (other than the report's own path). It focuses on **business logic, user scenarios, and behavior**.

When the user is satisfied with the report, they will invoke `plan-generator` on it to break it into implementation plans.

## When to use me

Use me when the user says any of:

- "لدي فكرة أريد إضافتها للنظام" / "عندي ميزة جديدة" / "أريد إضافة X"
- "سجّل فكرة" / "وثّق فكرة" / "احفظ فكرة" / "افتح بطاقة للميزة"
- "feature report" / "feature brief" / "feature spec"
- "اكتب تقرير عن ميزة" / "اكتب وصف لميزة"
- "وثّق هذه الميزة قبل ما نبدأ"

The idea should be **small and focused** — one feature, one user flow, one outcome. For massive multi-feature work, the user should break it down first.

## When NOT to use me

- The user wants a **technical plan** with files, phases, and code → use `plan-generator`.
- The user wants to **implement** the feature → use `plan-executor`.
- The user is asking a **technical question** about an existing feature.
- The user wants to document an **existing** feature as reference (use a different template).
- The idea is a single vague word with no clear user, value, or scenario → ask for clarification first, or refuse politely and explain what is missing.

## Workflow

### 1. Read the idea

Read the user's idea carefully. Identify:

- **What** the feature is (one sentence).
- **Who** benefits (the user role: guest, registered user, admin, agent, etc.).
- **Why** it matters (the value or pain it addresses).
- **When** it triggers (the context or scenario).

### 2. Resolve ambiguities with 1-3 short questions (only if needed)

If the idea is too vague to write a useful report, use the `question` tool to ask 1-3 short, focused questions. **Do not exceed 3 questions in total.** Good questions to ask only if truly necessary:

- "من هو المستخدم الأساسي لهذه الميزة؟" (Who is the primary user?)
- "ما النتيجة التي يريدها المستخدم من هذه الميزة؟" (What outcome does the user want?)
- "هل هناك تدفّق موجود في النظام يجب أن تتكامل معه؟" (Does it integrate with an existing flow?)

If the user refuses to answer or says "اكتب اللي تشوفه" (write whatever you think), make a **reasonable assumption**, mark it clearly in the report's "Open Questions" section, and proceed. Never block on missing info.

### 3. Determine the slug and path

- **Slug:** short, kebab-case, ASCII-only, derived from the idea's main noun (e.g., `favorites`, `agent-rating`, `saved-searches`, `property-comparison`).
- **Naming the slug:**
  - If the user provides a name → use it.
  - If the idea contains an obvious English noun → derive a slug from it.
  - If the idea is fully Arabic with no obvious English equivalent → ask the user once for a short English name (1-3 words). Do not guess; do not transliterate Arabic silently.
- **Path:** `docs/ideas/<feature-slug>/report.md`.
- **If the folder already exists** → ask the user: update the existing report, or pick a new slug?

### 4. Create the folder and write the report

1. Use `bash` to create the directory `docs/ideas/<feature-slug>/` if it does not exist.
2. Use `write` to create `docs/ideas/<feature-slug>/report.md` with the report content.
3. The report MUST follow the template below.
4. Do not write to any other file. Do not modify code, plans, or existing reports.

### 5. Confirm and remind

After writing, your final message MUST contain:

1. The file path: `📄 docs/ideas/<slug>/report.md`
2. A 3-5 bullet summary of the most important points in the report (so the user can decide whether to open it).
3. A reminder: "بعد المراجعة، إذا أعجبك التقرير اطلب مني استخدام `plan-generator` عليه. `plan-generator` يقرأ من `docs/reports/` افتراضياً، لذا يمكنك نقل الملف إلى هناك أو تمرير المسار الكامل صراحة."

## Report template

The report MUST be in the **same language as the user's idea**. The template below uses bilingual headings — pick the language that matches the input. For Arabic input, use the Arabic headings; for English, use the English ones. Do not mix.

```markdown
# <Feature Title>

> **Idea origin:** <one-line summary of the user's idea, in their own words>
> **Status:** Draft — awaiting user review
> **Created:** <YYYY-MM-DD>
> **Slug:** <feature-slug>

## Overview / نظرة عامة
<2-4 sentences describing what this feature is and what problem it solves, in plain language. No jargon. No technology.>

## Business Value / القيمة التجارية
- Why does this feature matter?
- What user pain or business opportunity does it address?
- How does it move the product forward?

## Target Users / المستخدمون المستهدفون
- Who benefits from this feature? (roles, account types, contexts)
- In what context do they use it? (web, mobile, both? guest, registered, admin?)

## User Scenarios / السيناريوهات
<List 2-5 concrete scenarios. Use the "As a <role>, I want to <action>, so that <outcome>" format.>

### Scenario 1: <short name>
- **As a** <role>, **I want to** <action>, **so that** <outcome>.
- **Steps:**
  1. <step>
  2. <step>
  3. <step>
- **Result:** <what the user sees/experiences at the end>

### Scenario 2: <short name>
<same structure>

## Business Rules & Logic / القواعد والمنطق
<Numbered, plain-language rules that govern behavior:>

1. A user can only <X> if <condition>.
2. When <event> happens, <effect> should occur.
3. <X> is not allowed when <Y>.
4. If <edge case>, the system should <fallback behavior>.

## Workflow / التدفّق
<A narrative walkthrough of what happens, end-to-end, from the user's perspective. Use bullet points or numbered steps. No technical terminology.>

1. <step>
2. <step>
3. <step>
...

## Edge Cases & Exceptions / الحالات الاستثنائية
- What happens if the user tries to <X> when <condition>?
- How should the system behave when <edge case>?
- What about users who don't have <prerequisite>?
- What about <unusual but plausible situation>?

## Acceptance Criteria / معايير القبول
<A checklist of business-level criteria. Each item is testable by a human without looking at code.>

- [ ] <criterion 1>
- [ ] <criterion 2>
- [ ] <criterion 3>
- ...

## Out of Scope / خارج النطاق
- What this feature does NOT include.
- What is intentionally deferred to a later iteration.
- What is assumed to already exist (e.g., "user authentication").

## Open Questions / أسئلة مفتوحة
- Any ambiguities the user should decide before planning.
- Assumptions that were made because the user did not specify.
- Follow-ups that surfaced while writing.
```

## Writing rules (strict)

1. **No technical terms** anywhere in the report:
   - Banned words: `API`, `database`, `framework`, `Laravel`, `model`, `controller`, `service`, `endpoint`, `route`, `migration`, `library`, `HTTP`, `JSON`, `REST`, `GraphQL`, `SQL`, `query`, `table`, `column`, `field`, `cache`, `queue`, `middleware`, `event`, `listener`, `job`, `frontend`, `backend`, `server`, `client request`, `response`, `payload`, `schema`, `index`, `foreign key`.
   - If a technical concept must be communicated, describe the **behavior** ("when the user clicks save, the listing is added to their favorites"), not the **mechanism** ("POST /api/favorites").
2. **No code blocks.** The only file path allowed in the body of the report is the report path itself.
3. **No class names, table names, field names, route names, config keys.** Use plain English/Arabic: "the user's profile", "the property listing", "the saved search", not `User`, `Property`, `SavedSearch`, `users.name`.
4. **No class-name compound words** ending in `Service`, `Controller`, `Repository`, `Provider`, `Event`, `Listener`, `Job`, `Middleware`, `Importer`, `Persister`, `Extractor`, `Parser`, `Orchestrator`, `Helper`, `Manager`, `Factory`, `Exception`, `Request`, `Resource`. If one slips in, replace it with a plain equivalent.
5. **Use plain language** as if explaining to a non-technical product manager.
6. **Be specific about behavior, not vague.**
   - Bad: "handle errors gracefully"
   - Good: "if the user enters a duplicate name, the system should warn them and not save."
7. **Be testable.** Every acceptance criterion should be observable by a user without looking at code.
8. **Keep it reviewable in 5 minutes.** Aim for 200-500 lines max for a typical small feature. If the report grows past 600 lines, the feature is too big — tell the user to split it and write multiple reports.
9. **Match the user's input language.** Arabic input → Arabic report (with optional bilingual section headers). English input → English report only. Mixed → follow the dominant language of the idea's description.
10. **No emojis inside the report body** (other than the file path prefix in the confirmation message, and the checkboxes in the acceptance criteria list).

## After writing — final message format

Always end with this structure:

```
📄 **تم إنشاء:** `docs/ideas/<slug>/report.md`

**أهم ما في التقرير:**
- <point 1>
- <point 2>
- <point 3>
- <point 4>
- <point 5>

**الخطوة التالية:** راجع التقرير. إذا أعجبك، اطلب مني استخدام `plan-generator` عليه لتفكيكه إلى خطط تنفيذ تقنية. ملاحظة: `plan-generator` يقرأ افتراضياً من `docs/reports/`، لذا قد تحتاج لنقل الملف إلى `docs/reports/<NN>-<slug>.md` أو تمرير المسار الكامل صراحة عند الاستدعاء.
```

(Use the English equivalent if the report is in English.)

## Pairing with `plan-generator`

`plan-generator` reads from `docs/reports/<file>.md` by default. The user has two options to continue the workflow after reviewing the report:

**Option A — move the file (recommended for plan-generator compatibility):**
```bash
mv docs/ideas/<slug>/report.md docs/reports/<NN>-<slug>.md
```

**Option B — pass the full path explicitly to plan-generator:**
```
"اعمل plans لملف docs/ideas/<slug>/report.md"
```

The skill does **not** move the file automatically. That decision is the user's, because moving the file is effectively a "promotion" from idea to actionable report.

## Reference: existing report tone

The existing report `docs/reports/02-project-description-and-features.md` is a good model for **tone, structure, and section headings** when the user asks for an English report. When the user asks for an Arabic report, mirror the same structure with Arabic headings.

## What I do NOT do

- I do not write technical reports, technical plans, or implementation code.
- I do not decide priority, effort estimate, or dependencies — that is `plan-generator`'s job.
- I do not write tests or run commands.
- I do not modify code in `app/`, `database/`, `tests/`, or any other source directory.
- I do not delete or overwrite an existing report without explicit confirmation.
- I do not invent features the user did not describe. If the idea is too vague, I ask.
- I do not output more than 3 clarifying questions. If the user refuses to answer, I make reasonable assumptions and document them in "Open Questions".
- I do not auto-move the file to `docs/reports/`. The user decides when a report is ready for planning.
- I do not generate multiple reports in one run. One idea = one report.
