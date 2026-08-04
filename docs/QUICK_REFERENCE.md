# Quick Reference - AI Agent Rules

Consolidated rules for writing code in this Laravel project.

---

## Module Structure

```
Modules/{Name}/
├── Database/Migrations/     # Auto-loaded via ServiceProvider
├── DTOs/                     # readonly final class DTOs
├── Entities/                  # Eloquent models (extends BaseModel)
├── Enums/                     # PHP 8.1+ backed enums
├── Http/
│   ├── Controllers/          # extends Controller
│   ├── Requests/              # FormRequest validation
│   └── Resources/             # BaseJsonResource
├── Providers/                 # Self-registering ServiceProvider
├── Routes/api.php
├── Services/                  # extends BaseService
├── Policies/
└── Tests/
```

**ServiceProvider must:**
- Load routes: `$this->loadRoutesFrom(__DIR__.'/../Routes/api.php')`
- Load migrations: `$this->loadMigrationsFrom(__DIR__.'/../Database/Migrations')`
- Register policies: `$this->registerPolicies([Model::class => Policy::class])`

---

## Class Inheritance

| Layer | Base Class | Use |
|-------|------------|-----|
| Controller | `Controller` (uses ApiResponses, ApplyPermissions, ValidatesRequests) | Thin, delegates to Service |
| Service | `BaseService` | Business logic, define `CACHE_TAG` |
| Model | `BaseModel` (uses Filterable) | Define `$filterableColumns`, `$searchableColumns` |
| Resource | `BaseJsonResource` | Implement `getCustomData()` + `getRelationMap()` |
| DTO | `DTOInterface` | `readonly final class`, `fromRequest()`, `toArray()` |

---

## Permissions (CRUD)

**Format:** `model.action` (e.g., `properties.list`, `properties.create`)

**Define in:** `database/seeders/PermissionSeeder.php` → `$permissionGroups`

**Apply in Controller:**
```php
$this->applyPermissions('properties', ['store', 'update', 'destroy'], [
    'toggleFavorite' => 'list',
]);
```

**CRUD mapping:** `show→show`, `index→list`, `store→create`, `update→edit`, `destroy→delete`

---

## Media Upload (Spatie)

**Entity implements:** `HasMedia`, uses `InteractsWithMedia`

```php
public function registerMediaCollections(): void
{
    $this->addMediaCollection('main_image')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png']);
    $this->addMediaCollection('gallery');
}
```

**Upload:** `UploadMediaHelper::upload($file, $model, 'collection_name')`

**Access:** `$model->getFirstMediaUrl('main_image')` or accessor `main_image_url`

---

## API Response

```php
// Success with chainable
return $this->successResponse($data, 'msg')->created('model');

// Paginated
return $this->paginatedResponse($paginator, 'msg');

// Error
return $this->failedResponse('msg', 404);
return $this->validationErrorResponse($errors);
```

---

## DTO Pattern

```php
readonly final class PropertyDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        // ...
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(name: $array['name'] ?? null, ...);
    }

    public function toArray(): array
    {
        return array_filter(['name' => $this->name, ...], fn($v) => $v !== null);
    }
}
```

---

## Enum Pattern

```php
enum PropertyStatus: string
{
    case PENDING = 'pending';

    public function label(): string
    {
        return match($this) { self::PENDING => 'Pending', };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

---

## Resource Pattern

```php
class PropertyResource extends BaseJsonResource
{
    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }

    protected function getRelationMap(): array
    {
        return ['category' => CategoryResource::class];
    }
}
```

---

## Model Filtering

```php
protected static $filterableColumns = ['status', 'country_id'];
protected static $searchableColumns = ['name', 'description'];
protected static $dateFilterableColumns = ['created_at'];

// Query: ?search=&status=approved&created_at[from]=&created_at[to]=
```

---

## Service Pattern

```php
class PropertyService extends BaseService
{
    protected const CACHE_TAG = 'properties';

    public function getAll(array $params): LengthAwarePaginator
    {
        return Property::query()->filter()->with(['country'])->latest()->paginate(15);
    }
}
```

**Cache:** `Cache::tags(self::CACHE_TAG)->remember($key, $ttl, fn() => ...)`

---

## Validation Rules

| Rule | Example |
|------|---------|
| Required | `'name' => ['required', 'string']` |
| Exists | `'country_id' => ['required', Rule::exists('countries', 'id')]` |
| In/Enum | `'status' => ['required', Rule::in(PropertyStatus::values())]` |
| File | `'image' => ['nullable', 'file', 'mimes:jpeg,png', 'max:5120']` |

---

## Testing

```php
// Authenticated test
$this->actingAs($user, 'sanctum')->postJson('/api/...', [...]);

// Assert
$response->assertStatus(201)
    ->assertJsonStructure(['data' => ['id', 'name']])
    ->assertJsonValidationErrors(['name']);

$this->assertDatabaseHas('table', ['column' => 'value']);
```

---

## Checklist Before Completing Task

- [ ] Type hints on all parameters and returns
- [ ] Use DTOs for data transfer
- [ ] Service uses `BaseService`, defines `CACHE_TAG`
- [ ] Controller thin, delegates to Service
- [ ] Resource extends `BaseJsonResource`
- [ ] Permissions defined in `PermissionSeeder.php` and applied in Controller
- [ ] Model defines `$filterableColumns`, `$searchableColumns`
- [ ] Media collections registered on entity with conversions
- [ ] FormRequest validation rules defined with `Rule::exists()` for FK
- [ ] Tests use `RefreshDatabase` + `$this->actingAs($user, 'sanctum')`
- [ ] Run `composer pint` before committing
- [ ] No hardcoded values - use config/env