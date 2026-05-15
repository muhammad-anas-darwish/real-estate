# Testing Guide

## Overview

PHPUnit testing with 4 test suites: `Unit`, `Feature`, `UserModule`, `Modules`.

---

## PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<testsuites>
    <testsuite name="Unit">tests/Unit</testsuite>
    <testsuite name="Feature">tests/Feature</testsuite>
    <testsuite name="UserModule">./Modules/Auth/Tests</testsuite>
    <testsuite name="Modules">./Modules</testsuite>
</testsuites>
```

---

## Running Tests

```bash
# Run all tests
composer test

# Run specific suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=UserModule
php artisan test --testsuite=Modules

# Run specific test class
php artisan test --testsuite=Modules --filter=PropertyTest

# Run specific test method
php artisan test --filter=test_user_can_create_property

# Run with coverage
php artisan test --coverage
```

---

## Test Structure

### Standard Feature Test

```php
namespace Modules\RealEstate\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_property(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/dashboard/properties', [
            'name' => 'Luxury Villa',
            'price' => 500000,
            'currency' => 'USD',
            'property_type' => 'villa',
            'country_id' => 1,
            'city_id' => 1,
            'category_id' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'price'],
            ]);
    }
}
```

### Module Test Structure

```
Modules/RealEstate/Tests/
├── Feature/
│   └── PropertyTest.php
└── Unit/
    └── Services/
        └── PropertyServiceTest.php
```

---

## Test Traits

| Trait | Usage |
|-------|-------|
| `RefreshDatabase` | Reset database after each test |
| `RefreshDatabase::class` | For tests that modify database |
| `WithFaker` | Access to `$this->faker` |
| `Illuminate\Foundation\Testing\LazilyRefreshDatabase` | Refresh only when needed |

---

## Authentication in Tests

```php
// Using Sanctum
$user = User::factory()->create();
$this->actingAs($user, 'sanctum');

// For API routes that require token
$token = $user->createToken('test')->plainTextToken;
$response = $this->withHeader('Authorization', 'Bearer ' . $token)
    ->postJson('/api/dashboard/properties', [...]);
```

---

## Assertions

### HTTP Assertions

```php
$response->assertStatus(200);
$response->assertStatus(201);
$response->assertStatus(404);
$response->assertStatus(422);

$response->assertJson(['success' => true]);
$response->assertJsonStructure(['data' => ['id', 'name']]);
$response->assertJsonFragment(['id' => 1, 'name' => 'Test']);

$response->assertRedirect('/expected-url');
$response->assertSessionHas('message');
```

### Database Assertions

```php
$this->assertDatabaseHas('properties', ['name' => 'Luxury Villa']);
$this->assertDatabaseMissing('properties', ['id' => 999]);
$this->assertDatabaseCount('properties', 5);
```

### Model Assertions

```php
$property = Property::first();
$this->assertEquals('Luxury Villa', $property->name);
$this->assertNotNull($property->created_at);
```

---

## Factories

### Creating Factory

```php
// database/factories/PropertyFactory.php
namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'slug' => $this->faker->slug(3),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->numberBetween(100000, 1000000),
            'currency' => 'USD',
            'property_type' => $this->faker->randomElement(['villa', 'apartment', 'house']),
            'status' => 'pending',
            'country_id' => Country::factory(),
            'city_id' => City::factory(),
            'category_id' => Category::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn(array $attributes) => ['status' => 'approved']);
    }
}
```

### Using Factory in Test

```php
// Create single
$property = Property::factory()->create();

// Create multiple
$properties = Property::factory()->count(5)->create();

// Create with state
$approved = Property::factory()->approved()->create();

// Create with specific attributes
$property = Property::factory()->create(['name' => 'Custom Name']);
```

---

## Test Data Setup

```php
public function setUp(): void
{
    parent::setUp();

    // Create test data
    $this->country = Country::factory()->create();
    $this->city = City::factory()->create(['country_id' => $this->country->id]);
    $this->category = Category::factory()->create();
    $this->user = User::factory()->create();
}
```

---

## Testing API Endpoints

### List Endpoint

```php
public function test_can_list_properties(): void
{
    Property::factory()->count(10)->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/dashboard/properties');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
            'data',
            'pagination' => ['total', 'per_page', 'current_page'],
        ]);
}
```

### Show Endpoint

```php
public function test_can_show_property(): void
{
    $property = Property::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson("/api/dashboard/properties/{$property->id}");

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $property->id]);
}
```

### Store Endpoint

```php
public function test_can_create_property(): void
{
    $user = User::factory()->create();
    $data = [
        'name' => 'New Property',
        'price' => 250000,
        'property_type' => 'apartment',
        'country_id' => $this->country->id,
        'city_id' => $this->city->id,
        'category_id' => $this->category->id,
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/dashboard/properties', $data);

    $response->assertStatus(201)
        ->assertJsonFragment(['name' => 'New Property']);

    $this->assertDatabaseHas('properties', ['name' => 'New Property']);
}
```

### Update Endpoint

```php
public function test_can_update_property(): void
{
    $property = Property::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->putJson("/api/dashboard/properties/{$property->id}", [
            'name' => 'Updated Name',
        ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['name' => 'Updated Name']);
}
```

### Delete Endpoint

```php
public function test_can_delete_property(): void
{
    $property = Property::factory()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/dashboard/properties/{$property->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('properties', ['id' => $property->id]);
}
```

---

## Testing Validation

```php
public function test_property_requires_name(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/dashboard/properties', [
            'price' => 100000,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
}
```

---

## Testing Authorization

```php
public function test_user_cannot_delete_others_property(): void
{
    $property = Property::factory()->create();
    $otherUser = User::factory()->create();

    $response = $this->actingAs($otherUser, 'sanctum')
        ->deleteJson("/api/dashboard/properties/{$property->id}");

    $response->assertStatus(403);
}
```

---

## Service Testing

```php
// Modules/RealEstate/Tests/Unit/Services/PropertyServiceTest.php
namespace Modules\RealEstate\Tests\Unit\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\DTOs\PropertyDTO;

class PropertyServiceTest extends TestCase
{
    use RefreshDatabase;

    private PropertyService $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new PropertyService();
    }

    public function test_can_create_property(): void
    {
        $dto = PropertyDTO::fromRequest([
            'name' => 'Test Property',
            'price' => 100000,
        ]);

        $property = $this->service->create($dto);

        $this->assertEquals('Test Property', $property->name);
        $this->assertDatabaseHas('properties', ['name' => 'Test Property']);
    }
}
```

---

## Checklist for Writing Tests

1. Use `RefreshDatabase` trait for database tests
2. Use `$this->actingAs($user, 'sanctum')` for authenticated requests
3. Use factory `create()` for test data
4. Assert response status code
5. Assert JSON structure/fragments
6. Assert database state with `assertDatabaseHas`
7. Test validation errors with `assertJsonValidationErrors`
8. Test authorization (403 for unauthorized)
9. Test edge cases (empty results, max records)