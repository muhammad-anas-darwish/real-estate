---
name: test-case-generator
description: Generate PHPUnit test cases (Unit or Feature) for a given PHP class, method, or scenario. Uses Pest or PHPUnit based on what the project already uses. Use when the user says "اكتب اختبارات لـ X", "generate tests for X", "test this method", or "give me test cases for the order flow".
license: MIT
compatibility: opencode
metadata:
  workflow: testing
  audience: developers
---

## What I do

Given a class, method, or user-described scenario, I produce a **ready-to-paste** test file using the test framework the project already uses.

I cover:
- **Happy path** — the documented behavior.
- **Edge cases** — empty inputs, boundary values, off-by-one.
- **Failure paths** — invalid input, missing dependencies, thrown exceptions.
- **Authorization** — if the unit guards access, the test guards the test (403 / redirect).
- **Side effects** — DB writes, events dispatched, jobs queued, notifications sent.

## When to use me

Use me when the user:
- Says "اكتب اختبارات لـ X" / "اكتب test لـ X".
- Says "generate tests for X" / "test this method".
- Names a class, method, route, or scenario (e.g. "the order cancellation flow").
- Has just implemented something and wants coverage before merging.

## Workflow

### 1. Detect the test framework

```bash
ls composer.json | xargs grep -E '"pestphp/pest"|"phpunit/phpunit"'
ls tests/ | head
```

- If `pestphp/pest` is in `require-dev` → write **Pest** syntax (`it(...)`, `test(...)`, `expect(...)`).
- Otherwise → write **PHPUnit** syntax (`test_...` or `#[Test]` methods, `$this->get(...)`).
- Use the same framework consistently. Do not mix.

Also detect:
- `tests/TestCase.php` base class — extend it.
- Any `tests/Concerns/` or `tests/Traits/` — reuse if relevant.
- `RefreshDatabase` / `DatabaseTransactions` usage — mirror the project's convention.
- Factories location (`database/factories/`).

### 2. Identify the target

The user may give:
- A class path: `app/Services/OrderService.php`.
- A method: `OrderService::cancel`.
- A route: `POST /admin/orders/{id}/cancel`.
- A scenario: "when a customer pays and the stock is insufficient".

If unclear, ask **one** clarifying question. Do not auto-guess silently.

### 3. Read the source

- Read the full file. Do not skim.
- For services: read the constructor (deps to mock), all public methods, exceptions thrown, events dispatched, jobs queued.
- For controllers: read the FormRequest used (to know validation rules), the service called, the response shape.
- For repositories: read all query methods + return types.
- For models: read scopes, accessors, mutators, boot hooks, observers, casts.

### 4. Map dependencies → mocks / fakes

For each constructor dep, choose the right strategy:

| Dep type | Strategy |
|----------|----------|
| Other service / repository | Mock with `Mockery::mock(Class::class)` or `Mockery::spy(...)`. |
| Eloquent model | Use factories. |
| Notification / Mail | `Notification::fake()` / `Mail::fake()`. |
| Event dispatcher | `Event::fake()`. |
| Queue | `Queue::fake()`. |
| Cache | `Cache::shouldReceive(...)` or `Cache::flush()` + real. |
| HTTP client | `Http::fake()`. |
| Storage | `Storage::fake('public')`. |
| Time | `Carbon::setTestNow(...)`. |

### 5. Build the test file

#### Naming & location

| Source | Test location |
|--------|--------------|
| `app/Services/X.php` | `tests/Unit/Services/XTest.php` (pure logic) **or** `tests/Feature/Services/XTest.php` (touches DB / events) |
| `app/Http/Controllers/X.php` | `tests/Feature/Http/Controllers/XTest.php` |
| `app/Repositories/X.php` | `tests/Unit/Repositories/XTest.php` if pure, `tests/Feature/Repositories/XTest.php` if it joins through relations |
| `app/Models/X.php` | `tests/Unit/Models/XTest.php` for scopes/casts, `tests/Feature/Models/XTest.php` for relationships |
| `app/Jobs/X.php` | `tests/Unit/Jobs/XTest.php` |
| `app/Listeners/X.php` | `tests/Feature/Listeners/XTest.php` |
| Scenario only | Suggest a location based on the primary class involved; ask if unclear. |

File name: `XTest.php` (PHPUnit) or `XTest.php` with `uses(TestCase::class);` (Pest, in `tests/Feature/...` or `tests/Unit/...`).

#### Structure of a test method

```
// AAA: Arrange, Act, Assert
// One logical concept per test method.
// One assertion cluster per AAA block (multiple asserts on the same outcome are OK).
```

#### Must-have test list (always include)

For a method `X`:

1. ✅ Happy path — valid input, returns the documented result.
2. ✅ Returns / sets the right side effect (DB row, event, job, mail).
3. ✅ Throws the documented exception on invalid input.
4. ✅ Boundary / edge case (empty, max, min, null).
5. ✅ Authorization: unauthenticated / unauthorized user is rejected.
6. ✅ Validation: bad payload → 422 (Feature) / throws ValidationException.
7. ✅ Idempotency: a second identical call does not double-write (when relevant).
8. ✅ Side-effect isolation: a failure in the middle does not leave partial state (transaction rollback).

Skip any of the above that is genuinely not applicable (e.g. unit test for a static helper does not need auth).

### 6. Output

```
// tests/Feature/Orders/OrderCancellationTest.php  (or .php for Pest)

<?php

namespace Tests\Feature\Orders;

use App\Events\OrderCancelled;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_their_own_pending_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['status' => 'pending']);

        // Act
        $this->actingAs($user)
            ->postJson("/api/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');

        // Assert
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
        Event::assertDispatched(OrderCancelled::class);
    }

    public function test_cannot_cancel_an_order_owned_by_another_user(): void
    {
        // ...
    }

    public function test_cannot_cancel_a_shipped_order(): void
    {
        // ...
    }

    public function test_cancellation_rolls_back_when_refund_fails(): void
    {
        // ...
    }
}
```

If Pest:

```php
<?php

use App\Models\{Order, User};
use Illuminate\Support\Facades\Event;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

it('cancels a pending order for its owner', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['status' => 'pending']);

    $this->actingAs($user)
        ->postJson("/api/orders/{$order->id}/cancel")
        ->assertOk();

    expect($order->fresh()->status)->toBe('cancelled');
    Event::assertDispatched(\App\Events\OrderCancelled::class);
});
```

### 7. Apply

By default I just output the test file. If the user says "create the file" / "اكتب الملف" / "احفظه", I:
1. Create the test file at the proposed path.
2. Run the test (`./vendor/bin/phpunit <path>` or `./vendor/bin/pest <path>`) and report the result.
3. If the test fails because of missing seed data / wrong assertion, **fix the test**, not the source.

## Codebase-specific notes

- This project is **Laravel 11** with PHPUnit or Pest (I will detect which).
- Existing tests live under `tests/Feature/...` and `tests/Unit/...`. I follow the same convention.
- The project has 88 models, 140 controllers, 52 repositories. I do not test all of them in one go — I test what the user asked for.
- For Sanctum-protected routes, the test uses `Sanctum::actingAs($user)`, not `actingAs`.
- For Queues, the project may use database driver. I use `Queue::fake()` to assert jobs were pushed without running them.

## What I do NOT do

- I do not modify the source code under test. If the test reveals a real bug, I report it (and may suggest using `bug-report-formatter`).
- I do not generate tests that are tautological (`$x = 1; $this->assertEquals(1, $x);`).
- I do not mock what I do not need to mock — over-mocking hides bugs.
- I do not invent factories that do not exist. If a factory is missing for a model, I tell the user and offer to create one.
- I do not generate tests for frontend (Vue) code in this skill — that's a separate concern.
- I do not commit the test. I just write the file (if the user asked) and run it.
