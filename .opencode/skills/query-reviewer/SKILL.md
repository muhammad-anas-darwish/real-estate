---
name: query-reviewer
description: Audit Eloquent / database queries for N+1, missing eager loading, missing indexes, inefficient selects, and unsafe raw SQL. Returns a prioritized list of fixes. Use when the user says "راجع الـ queries", "ابحث عن N+1", "audit queries", or "مراجعة أداء قاعدة البيانات".
license: MIT
compatibility: opencode
metadata:
  workflow: database
  audience: developers
---

## What I do

I scan the codebase for **database access patterns that hurt** and produce a prioritized fix list.

I look for:
- **N+1 queries** — looping over results and accessing a relation per iteration.
- **Missing eager loading** — `->all()`, `->get()`, `->paginate()` without `with()`.
- **Select \*** — pulling columns the code never reads.
- **Raw SQL with interpolation** — `whereRaw` / `havingRaw` / `orderByRaw` / `DB::raw` with user input.
- **Queries inside loops** — `Model::find()` / `Model::where()->first()` inside `foreach`.
- **Missing indexes** — `where`, `whereIn`, `orderBy`, `groupBy`, `having`, `join` on columns that are not indexed (verified by reading the latest migration for that table).
- **Full-table scans in disguise** — `LIKE '%...%'` (leading wildcard kills the index), `OR` chains that prevent index use, `!=` / `<>` on indexed columns.
- **Unbounded results** — `->get()` / `->all()` on tables that can grow without a `->limit()` or pagination.
- **Counters in loops** — `Model::count()` or `->exists()` called per iteration when a single `withCount` would do.
- **Locking mistakes** — `lockForUpdate()` outside a transaction, or `sharedLock` on a write path.

## When to use me

Use me when the user:
- Says "راجع الـ queries" / "ابحث عن N+1" / "audit queries".
- Asks for a performance review of a specific controller / repository / page.
- Mentions slow API responses or a slow admin page.
- Is about to ship a new endpoint and wants a pre-flight check.

## Workflow

### 1. Decide the scope

The user may give me:
- A path: `app/Http/Controllers/Admin/OrderController.php`.
- A feature area: "the order listing in admin".
- The whole project: default if nothing is specified.

Use `glob` and `grep` to enumerate candidate files:
- `app/Http/Controllers/**/*.php`
- `app/Repositories/**/*.php`
- `app/Services/**/*.php` (services that talk to DB)
- `app/Models/**/*.php` (scopes, accessors that query)

### 2. For each file, look for the patterns

#### A. N+1 / missing eager loading

```php
// ❌ N+1
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->customer->name;  // 1 query per order
}

// ❌ Same problem with paginate
$orders = Order::paginate(20);
return view(...)->with('orders', $orders);

// ❌ Accessing relation inside a Blade view
{{ $order->items->count() }}

// ❌ Counting in a loop
foreach ($orders as $order) {
    $count = $order->items()->count();  // 1 query per row
}
```

Fix template: add `with()` / `load()` / `withCount()`.

#### B. Raw SQL with interpolation

```php
// ❌ SQL injection risk
$query->havingRaw('average_rating >= ' . $rating);
$query->orderByRaw("id = $firstItem DESC");
DB::statement("UPDATE x SET y = '$value'");
```

Fix template: parameter binding — `?` placeholders + array of values.

#### C. Queries inside loops

```php
// ❌
foreach ($productIds as $id) {
    $product = Product::find($id);
    $product->update([...]);
}

// ✅
Product::whereIn('id', $productIds)->update([...]);
```

#### D. Missing indexes

Read the latest `database/migrations/*_<table>.php`. For every column the query filters / orders / groups by, check:
- `$table->index(...)` exists, **or**
- The column is part of a composite index **with the same column order**, **or**
- It's a foreign key column (Laravel auto-indexes FKs).

If not, flag as `missing index`.

#### E. Full-table scans

```php
// ❌ leading wildcard
->where('name', 'like', "%$term%");

// ❌ OR chain preventing index use
->where('a', $x)->orWhere('b', $y);

// ❌ function on indexed column
->whereRaw("DATE(created_at) = ?", [$date]);
```

### 3. Cross-check with the query log

If the project has Telescope / Debugbar enabled, look for the slowest queries (top 20). If not, infer from code only.

### 4. Score and prioritize

Score = `(rows_estimated_impact) × (frequency) × (fix_effort_inverted)`

| Priority | Score | Examples |
|----------|:---:|----------|
| 🔴 **Critical** | ≥ 50 | N+1 in a paginated public endpoint, raw SQL with user input |
| 🟠 **High** | 20–49 | N+1 in admin, missing index on a hot filter |
| 🟡 **Medium** | 5–19 | `select *` in a list view, count in loop |
| 🟢 **Low** | < 5 | Cosmetic, edge case |

### 5. Output

```
# Query Review — <scope>

## 🔴 Critical (N)
### Q-1: N+1 in Admin/OrderController@index
**File:** `app/Http/Controllers/Admin/OrderController.php:42`
**Pattern:** paginate(20) without `with(['customer', 'items'])`
**Impact:** ~21 queries per page (1 + 20 × relations). At 1k orders/min, ~21k queries/min wasted.
**Fix:**
```php
- $orders = Order::where('status', $active)->paginate(20);
+ $orders = Order::with(['customer', 'items'])->where('status', $active)->paginate(20);
```
**Ref:** related to docs/plans/21-database-indexes-performance.md

## 🟠 High (N)
...

## 🟡 Medium (N)
...

## 🟢 Low (N)
...

## Summary
- N+1 patterns: X
- Raw SQL issues: Y
- Missing indexes: Z
- Other: W
- Estimated total fix effort: <hours/days>
```

## Codebase-specific notes

This project uses **Laravel 11 + Vue 3 + (most likely) MySQL**. Defaults I apply:
- Foreign keys are auto-indexed in Laravel migrations.
- `utf8mb4` and `InnoDB` are the default.
- `created_at`, `updated_at` are present on all tables.
- The codebase has a strong `Service` / `Repository` split (per plan #01, #02) — so query problems often live in repositories, not controllers. Always check both.

I also reference the existing plans when relevant:
- `docs/plans/21-database-indexes-performance.md` — index additions.
- `docs/plans/20-race-conditions-locks.md` — concurrency / locks.
- `docs/plans/18-decouple-order-repository.md` — large repos to split.
- `docs/plans/16-critical-security-fixes.md` — `whereRaw` SQL injection.

## What I do NOT do

- I do not modify code. I report and recommend. The user (or another skill) applies the fix.
- I do not run `EXPLAIN` against a live database (no DB connection from the skill).
- I do not guess row counts. If I cannot estimate, I say "unknown — measure with Telescope / slow query log".
- I do not flag stylistic preferences (variable naming, etc.) — only measurable or correctness issues.
- I do not propose adding a cache unless the user asks — caching has its own failure modes and is out of scope here.
