---
name: flow-reviewer
description: Trace a user-described scenario through the codebase. Identifies every file, route, controller, service, repository, model, event, listener, and Blade/Vue view involved — in execution order. Use when the user says "اشرح لي تدفق X", "trace the order flow", "what happens when a customer pays", or "اي ملفات تتأثر بـ X".
license: MIT
compatibility: opencode
metadata:
  workflow: understanding
  audience: developers
---

## What I do

The user gives me a **scenario in plain language** — e.g. "what happens when a customer pays for an order?" — and I produce a **step-by-step trace** through the codebase:

- The HTTP request → route → middleware.
- The controller and FormRequest.
- The service(s) and repository(ies) called.
- The models touched and queries fired.
- The events dispatched, listeners triggered, jobs queued, notifications sent.
- The Blade / Vue view that renders the response.
- Any failure branches (validation, authorization, exception paths).

The output is a **readable map**, not a wall of file paths. Each step explains *what* runs and *why*.

## When to use me

Use me when the user:
- Says "اشرح لي تدفق X" / "اشرح flow الـ X" / "trace the X flow".
- Asks "what files are involved when a customer does Y?".
- Is onboarding a new dev and wants a guided tour of a feature.
- Is about to refactor a flow and wants the blast radius.
- Wants to debug by understanding the expected path first.

## Workflow

### 1. Parse the scenario

The scenario is usually one of:

- **User action on a page** — "when a customer clicks Pay".
- **System event** — "when an order is created".
- **Background job** — "what does the queue worker run every minute?".
- **Webhook** — "when PayPal calls our IPN endpoint".
- **API call** — "when the mobile app calls POST /api/orders".

Extract from the scenario:
- The **entry point** (route URL or job class or command name).
- The **actor** (guest, customer, seller, rider, admin, system).
- The **verb** (create, update, cancel, pay, refund, ...).
- The **expected outcome** (redirect, JSON, email, ...).

If any of these is missing or ambiguous, ask **one** focused question.

### 2. Find the entry point

Use `grep` and `read` to locate:

| Scenario type | How to find |
|---------------|-------------|
| HTTP / page | `rg "Route::(get\|post\|put\|patch\|delete)\(" routes/` |
| API | `rg "Route::(get\|post\|put\|patch\|delete)\(" routes/api.php` or `routes/api_*.php` |
| Webhook | look for `webhook`, `ipn`, `callback` in routes and controllers |
| Job | `rg "implements ShouldQueue" app/Jobs/` |
| Event listener | `app/Providers/EventServiceProvider.php` and `app/Listeners/` |
| Console command | `app/Console/Commands/` and `app/Console/Kernel.php` |
| Scheduled task | `app/Console/Kernel.php` (schedule method) |

### 3. Walk the stack — top down

Start at the entry point and follow the code, layer by layer:

1. **Middleware** — what runs before the controller?
   - Auth (`auth`, `auth:sanctum`, `authShop`, custom).
   - Role / permission (`checkPermission`, `role:...`).
   - Throttling (`throttle:...`).
   - Locale, subscription, CSRF.
2. **Form Request** — what is validated?
   - Read `authorize()` and `rules()`.
3. **Controller** — what does it call?
   - Read the method body.
   - Note any `try/catch`, `event()`, `dispatch()`, `Mail::to()`, `Notification::send()`.
4. **Service / Action** — the business logic.
   - Read each method called by the controller.
   - Note the constructor deps (repos, gateways, other services).
5. **Repository** — the data access.
   - Note the query methods called.
   - Note any caching, locking, transactions.
6. **Model** — relationships, scopes, accessors, observers.
7. **Events / Listeners** — what fires after?
8. **Jobs / Queue** — what is async?
9. **Notifications / Mail** — what is sent to the user?
10. **Response** — view, redirect, or JSON.

### 4. Note the side effects

At each step, capture:
- **DB writes** (which table, what column).
- **Cache writes / invalidations** (key, TTL).
- **External calls** (HTTP, payment gateway, mail, push, SMS, storage).
- **Auth-relevant actions** (login, token issued, role changed).

### 5. Note the failure paths

For each step, look for:
- Validation rules (FormRequest).
- `authorize()` returns — who can / cannot do this.
- Thrown exceptions — what the user sees on failure.
- `abort()`, `throw`, `response()->json(..., 4xx)`.
- Compensation logic (e.g. refund when downstream fails).

### 6. Output

Use this structure:

```
# Flow: <scenario name>

**Actor:** <who>
**Entry point:** <route or class>
**Outcome:** <what the user/system sees at the end>

## Happy path (step by step)

1. **<step name>** — `<file>:<line>`
   `<one-sentence description of what runs>`
   - DB: <table.column> = ...
   - Cache: ...
   - Side effect: ...

2. **<step name>** — `<file>:<line>`
   ...

3. ...

## Diagram (optional, for complex flows)

```
[HTTP POST /api/orders]
        ↓
[AuthMiddleware] → 401 if no token
        ↓
[FormRequest::authorize] → 403 if not customer
        ↓
[FormRequest::rules] → 422 if invalid
        ↓
[OrderController@store]
        ↓
[OrderService::create]
        ↓       ↘
[OrderRepository]  [PaymentService]
        ↓
[OrderCreated event]
        ↓
[SendOrderEmail listener]  [ReduceStock listener]  [NotifySeller listener]
        ↓
[201 Created with order JSON]
```

## Failure paths

| When | Where | What happens | User sees |
|------|-------|--------------|-----------|
| Missing token | AuthMiddleware | 401 | "Unauthenticated" |
| Wrong role | checkPermission | 403 | "Forbidden" |
| Invalid payload | FormRequest::rules | 422 | field-level errors |
| Payment gateway down | PaymentService | catch + Order marked pending | "Payment pending" |
| Stock insufficient | OrderService::create | throws | 422 "Out of stock" |

## Side effects summary

- **DB writes:** orders, order_items, payments, ...
- **Cache writes:** none / user:<id>:* (TTL ...)
- **External calls:** PayPal charge, SendGrid send, FCM push
- **Events fired:** OrderCreated, OrderPaid
- **Jobs queued:** ProcessPayout, GenerateInvoice
- **Notifications:** OrderConfirmationEmail, OrderPushNotification

## Files involved (full list)

| Layer | Files |
|-------|-------|
| Route | `routes/api.php:42` |
| Middleware | `app/Http/Middleware/Authenticate.php`, ... |
| FormRequest | `app/Http/Requests/OrderRequest.php` |
| Controller | `app/Http/Controllers/Shop/OrderController.php` |
| Service | `app/Services/OrderService.php` |
| Repository | `app/Repositories/OrderRepository.php` |
| Model | `app/Models/Order.php`, `app/Models/OrderItem.php` |
| Event | `app/Events/OrderCreated.php` |
| Listener | `app/Listeners/SendOrderEmail.php`, ... |
| Job | `app/Jobs/ProcessPayout.php` |
| Notification | `app/Notifications/OrderConfirmation.php` |
| View / Vue | `resources/js/pages/Shop/Order/Show.vue` |
| Tests | `tests/Feature/Shop/OrderTest.php` |

## Related plans

- docs/plans/18-decouple-order-repository.md (this flow spans the 488-line repo)
- docs/plans/20-race-conditions-locks.md (stock reduction has a race-condition plan)
- ...
```

### 7. Optional: edge cases

If the user asks ("what about idempotency?", "what if the user clicks twice?", "what if the network drops?"), I add an **"Edge cases"** section answering exactly that, in the same step-by-step style.

## Codebase-specific notes

- This project uses **Laravel 11 + Vue 3**. Routes are split into `routes/{admin,shop,seller,rider,api,api_*,web}.php`.
- The service / repository split is in place (per plans #01, #02). I always go controller → service → repository, never skip the service.
- Events / listeners exist for many flows but not all (some direct dispatches remain — see plan #17). I flag those when I see them.
- The project is multilingual; I do not explain translation keys in the trace unless relevant.
- The project uses `sanctum` for API auth, `auth` + `checkPermission` for admin/shop, custom middleware for seller / rider.

## What I do NOT do

- I do not modify any file. This is a read-only analysis.
- I do not invent paths or classes. If I cannot find a step, I say "I could not locate this — please point me to it".
- I do not explain every line of every file. I summarize what each layer does, with file:line for verification.
- I do not propose refactors or improvements — that's `tech-advisor`'s job.
- I do not generate test cases for the flow — that's `test-case-generator`'s job. (I can mention which test file covers the flow, if it exists.)
- I do not invent failure paths. If a step has no obvious failure mode, I leave it out of the table.
