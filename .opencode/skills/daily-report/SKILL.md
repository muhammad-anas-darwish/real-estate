---
name: daily-report
description: Generate a non-technical daily progress report in a fixed format (8 numbered achievements, "Yesterday" or "Today" header, theme in parens). Reads from any combination of reports, plans, or markdown files the user names. Use when the user says "اكتب تقرير يومي", "daily report", "تقرير today", "تقرير yesterday", or "ملخص الانجاز".
license: MIT
compatibility: opencode
metadata:
  workflow: reports
  audience: non-technical
---

## What I do

I read a set of `.md` files (reports, plans, or any other) and produce a **non-technical daily progress report** with exactly the format below:

```
Yesterday (<Theme>)
1 : <Achievement Title>: <Plain-language description of what was done and the value>.
2 : <Achievement Title>: <...>
...
8 : <Achievement Title>: <...>
```

or, if the user asked for "today" instead of "yesterday":

```
Today (<Theme>)
...
```

## When to use me

Use me when the user says any of:
- "اكتب تقرير يومي"
- "daily report"
- "تقرير today" / "تقرير yesterday"
- "ملخص الانجاز"
- "قدم تقرير للمدير" (when the audience is non-technical)
- "report from <file1>, <file2>"

The user may pass:
- One or more file names (relative or absolute).
- A time mode: `today` (default if the user says "today" or "الآن") or `yesterday` (default otherwise).
- A point count (default **8**).
- A theme string (e.g. "Backend - Architecture Refactoring & Technical Debt Reduction"). If not given, infer from the file content.

## Workflow

### 1. Resolve the inputs

- Resolve each file. Defaults:
  - `docs/reports/<file>.md`
  - `docs/plans/<file>.md`
  - otherwise, treat the input as a path and read it.
- If no files are given, ask the user which files to base the report on.
- Read all files fully. Do not summarize prematurely.

### 2. Determine the time word

- If the user says `today` / `اليوم` / `الآن` → use `Today`.
- If the user says `yesterday` / `الأمس` / `امس` → use `Yesterday`.
- Default: `Yesterday` (this is the common case — daily standups for the previous workday).

### 3. Determine the point count

- Default: **8** points.
- The user can say e.g. "10 نقاط" or "6 only" — respect that.
- The user can also say "وزع على 12 نقطة" — respect that.
- If the source content is sparse, still produce up to the requested count by grouping smaller items under a single point.

### 4. Determine the theme

- If the user provides a theme, use it verbatim.
- Otherwise, infer a high-level theme from the file content. Format: `<Area> - <Focus>`.
  - Example: `Backend - Architecture Refactoring & Technical Debt Reduction`
  - Example: `Security - Critical Vulnerabilities Closure`
  - Example: `Performance - Database Optimization`
- Keep the theme short (≤ 60 chars) and free of implementation jargon.

### 5. Extract candidate achievements

From the source files, extract concrete accomplishments — things that are **done**, **completed**, or **migrated**. Skip:
- Open issues / future work / "to do later".
- Plans that are not yet started.
- Items with status `❌ لم يبدأ` or `🟡 جزئياً` (unless the partial work itself is worth reporting — then rephrase as "Progress on ...").

For each accomplishment, capture:
- The **what** (action verb + object).
- The **value** (what changed for the product / team / users).
- Any **before/after** contrast (e.g. "from X to Y", "eliminated Z").

### 6. Filter to the requested point count

- If you have **more** candidates than points, merge the smallest into a single "miscellaneous" point.
- If you have **fewer** candidates than points, group related small items together. Do **not** invent work.
- Aim for 8 roughly equal-weight points. Do not let one point dominate.

### 7. Rewrite each point in non-technical language

Each point MUST follow this exact structure:

```
<N> : <Title>: <One-sentence plain-language description>.
```

Rules:
- **Title** is Title Case, action-verb first (Completed, Refactored, Migrated, Established, Split, Removed, Introduced, Standardized, Decomposed, Added, Reduced, Improved, Fixed).
- **Description** is **one sentence**, ends with a period.
- The description answers: *what was done, and what value it brings*.
- The description must be understandable by a **non-technical** reader (a manager, a stakeholder, a client).
- Strip ALL of the following from **both the title and the description**:
  - File paths (`app/Http/...`, `config/...`, `database/migrations/...`).
  - **Class names** (`ProductApproveEvent`, `OrderMailEvent`, `CatchHelpers`, `EnvWriterService`, ...).
  - **Service names** (`NotificationService`, `RepositoryServiceProvider`, `ErrorTrackingService`, `ProductImportPersister`, `SheinProductUrlImporter`, ...).
  - Function names, method names, variable names (`setEnv`, `whereRaw`, `Cache::remember`, `module_exists`, ...).
  - Line numbers, code snippets, regex patterns.
  - Package names that are noise (`predis/predis`, `mews/purifier`, `joynala/maker`, `nwidart/laravel-modules`).
  - Acronyms the reader is unlikely to know (`DI`, `CRUD`, `ORM`, `CSRF`, `TOCTOU`, `SPA`, `API` when used as a technical term).
- **Keep** only generic, non-branded vocabulary that any reader understands:
  - Pattern names as **concepts** (repository, service, middleware, queue, event, listener) — but never with a class suffix attached.
  - Principle names (Single Responsibility, Separation of Concerns).
  - Outcome words (eliminated, reduced, standardized, secured, simplified, faster, more reliable).
  - User / business words (customer, order, payment, product, page, login, email, notification).
- **Class / service name test:** if the phrase could only exist in a developer's IDE (e.g. "RepositoryServiceProvider", "ProductImportPersister", "SheinProductUrlImporter"), it must be reworded to a generic equivalent ("the product import system", "the routing configuration", "the product import flow").
- Match the tone of the example the user provided: confident, outcome-focused, present tense for value ("eliminating...", "enabling..."), past tense for action ("Refactored...", "Migrated...").

### 8. Sanity-check the output

Before showing the user, verify:
- [ ] Exactly the requested number of points.
- [ ] Each point is on its own line.
- [ ] Each point has the format `N : Title: Description.`
- [ ] The header is `Yesterday (<Theme>)` or `Today (<Theme>)` on a single line.
- [ ] **No file paths, no class names, no service names, no line numbers.**
- [ ] **No specific identifiers**: scan for PascalCase compound words ending in `Service`, `Controller`, `Repository`, `Provider`, `Event`, `Listener`, `Job`, `Middleware`, `Importer`, `Persister`, `Extractor`, `Parser`, `Orchestrator`, `Helper`, `Manager`, `Factory`, `Exception`, `Request`, `Resource`. If any appear, reword to a generic phrase.
- [ ] No bullet points, no sub-points, no extra prose.

### 9. Output

Return the report **as a single markdown block**, ready to copy-paste. Do not write it to a file unless the user asks. Do not add commentary around it.

If the user later asks to save it, default location: `docs/reports/daily-<YYYY-MM-DD>-<theme-slug>.md`. (This is also where reports like `daily-2026-06-25-security-performance.md` already live — match the existing naming convention.)

## Reference example (style guide — format and tone only)

> **Note:** this example is the user's reference for **structure and tone** (header, numbering, sentence shape, verb style). It is **not** a license to include class or service names. The rules in section 7 (especially the "Class / service name test") override any example wording. When in doubt, reword.
>
> Yesterday (Backend - Architecture Refactoring & Technical Debt Reduction)
> 1 : Refactor Repository Pattern to Instance-Based Design: Completed the migration of all 52 repository classes from static methods to instance-based design with proper dependency injection and interface contracts, eliminating reliance on external library joynala/maker.
> 2 : Implement RepositoryInterface & Service Provider Bindings: Created RepositoryInterface contract with standardized CRUD methods and configured RepositoryServiceProvider with full interface-to-implementation bindings for automatic DI resolution across controllers.
> ...
> 8 : Establish Product Import Service Architecture: Restructured the product import namespace with clear separation between Parsers, Extractors, Persisters, and Orchestrators, enabling easier extension for new import sources like Temu.
>
> **A cleaner, rules-compliant version of the same point 2 would be:**
> 2 : Standardize Configuration Binding Contracts: Introduced a binding contract for the data-access layer with standardized access methods and configured automatic resolution across all controllers, removing the need for ad-hoc wiring in each request handler.

## What I do NOT do

- I do not write technical reports. If the source is too technical to summarize plainly, I will say so and ask the user for guidance.
- I do not invent work. If the source files do not contain enough accomplished items to fill the requested point count, I report fewer points and tell the user.
- I do not output Arabic unless the user wrote in Arabic. I mirror the user's language.
- I do not add emojis, badges, tables, or extra headings to the output.
- I do not change the point format (`N : Title: Description.`).
