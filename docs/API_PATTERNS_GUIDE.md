# API Patterns Guide

## Overview

Standard patterns for Controllers, Services, Resources, DTOs, and Enums.

---

## Controller Pattern

```php
namespace Modules\RealEstate\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\RealEstate\Http\Requests\StorePropertyRequest;
use Modules\RealEstate\Http\Requests\UpdatePropertyRequest;
use Modules\RealEstate\Http\Resources\PropertyResource;
use Modules\RealEstate\Services\PropertyService;

class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService
    ) {
        $this->applyPermissions('properties', ['store', 'update', 'destroy'], [
            'toggleFavorite' => 'list',
            'statistics' => 'list',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $this->propertyService->getAll($request->all());
        return $this->paginatedResponse($data, 'Properties retrieved successfully');
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $dto = StorePropertyDTO::fromRequest($request->validated());
        $property = $this->propertyService->create($dto);
        return $this->successResponse(new PropertyResource($property), 'Property created')
            ->created('property');
    }

    public function show(int $id): JsonResponse
    {
        $property = $this->propertyService->find($id);
        return $this->successResponse(new PropertyResource($property));
    }

    public function update(UpdatePropertyRequest $request, int $id): JsonResponse
    {
        $dto = UpdatePropertyDTO::fromRequest($request->validated());
        $property = $this->propertyService->update($id, $dto);
        return $this->successResponse(new PropertyResource($property), 'Property updated')
            ->updated('property');
    }

    public function destroy(int $id): JsonResponse
    {
        $this->propertyService->delete($id);
        return $this->successResponse(null, 'Property deleted')->deleted('property');
    }
}
```

### Controller Traits

```php
use App\Traits\ApiResponses;      // successResponse, paginatedResponse, failedResponse
use App\Traits\ApplyPermissions;  // applyPermissions in constructor
use App\Traits\ValidatesRequests; // validationErrorResponse
```

---

## Service Pattern

```php
namespace Modules\RealEstate\Services;

use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class PropertyService extends BaseService
{
    protected const CACHE_TAG = 'properties';

    public function getAll(array $params): LengthAwarePaginator
    {
        return Property::query()
            ->filter()
            ->with(['country', 'category', 'media'])
            ->withExists(['favoritedByCurrentUser'])
            ->latest()
            ->paginate($this->getPerPage());
    }

    public function find(int $id): Model
    {
        return Property::findOrFail($id);
    }

    public function create(PropertyDTO $dto): Model
    {
        $property = Property::create($dto->toArray());
        $this->clearCache();
        return $property;
    }

    public function update(int $id, PropertyDTO $dto): Model
    {
        $property = $this->find($id);
        $property->update($dto->toArray());
        $this->clearCache();
        return $property->fresh();
    }

    public function delete(int $id): void
    {
        $property = $this->find($id);
        $property->delete();
        $this->clearCache();
    }
}
```

### BaseService Methods

| Method | Usage |
|--------|-------|
| `generateCacheKey(array, prefix)` | Generate consistent cache keys |
| `getPerPage(int)` | Get pagination limit (default 15) |
| `clearCache()` | Flush cache for `CACHE_TAG` |

### Cache Pattern

```php
return Cache::tags(self::CACHE_TAG)->remember($cacheKey, $ttl, function () {
    // expensive operation
});
```

---

## Resource Pattern

```php
namespace Modules\RealEstate\Http\Resources;

use App\Http\Resources\BaseJsonResource;

class PropertyResource extends BaseJsonResource
{
    protected string $dateFormat = 'Y-m-d H:i:s';

    protected function getCustomData(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'property_type' => $this->property_type,
            'status' => $this->status,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'area' => $this->area,
            'main_image' => $this->main_image_url,
            'main_image_thumb' => $this->main_image_thumb_url,
            'gallery' => $this->gallery_urls,
            'country' => new CountryResource($this->whenLoaded('country')),
            'city' => new CityResource($this->whenLoaded('city')),
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }

    protected function getRelationMap(): array
    {
        return [
            'country' => CountryResource::class,
            'city' => CityResource::class,
            'category' => CategoryResource::class,
        ];
    }
}
```

### BaseJsonResource Methods

| Method | Purpose |
|--------|---------|
| `getCustomData()` | Define resource-specific fields (required) |
| `getRelationMap()` | Map relations to resources for auto-include |
| `getBaseFields()` | Returns `['id', 'created_at', 'updated_at']` |

---

## DTO Pattern

```php
namespace Modules\RealEstate\DTOs;

use App\Interfaces\DTOInterface;

readonly final class PropertyDTO implements DTOInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
        public ?float $price = null,
        public ?string $currency = null,
        public ?string $property_type = null,
        public ?string $status = null,
        public ?int $country_id = null,
        public ?int $city_id = null,
        public ?int $category_id = null,
    ) {}

    public static function fromRequest(array $array): self
    {
        return new self(
            name: $array['name'] ?? null,
            slug: $array['slug'] ?? null,
            description: $array['description'] ?? null,
            price: $array['price'] ?? null,
            currency: $array['currency'] ?? 'USD',
            property_type: $array['property_type'] ?? null,
            status: $array['status'] ?? PropertyStatus::PENDING->value,
            country_id: $array['country_id'] ?? null,
            city_id: $array['city_id'] ?? null,
            category_id: $array['category_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'property_type' => $this->property_type,
            'status' => $this->status,
            'country_id' => $this->country_id,
            'city_id' => $this->city_id,
            'category_id' => $this->category_id,
        ], fn($value) => $value !== null);
    }
}
```

### DTO Rules

- Use `readonly final class`
- Implement `DTOInterface`
- Define `fromRequest(array): self`
- Define `toArray(): array`
- Use `array_filter()` in `toArray()` to exclude null values

---

## Enum Pattern

```php
namespace Modules\RealEstate\Enums;

enum PropertyStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SOLD = 'sold';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::SOLD => 'Sold',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toSelectArray(): array
    {
        return collect(self::cases())->mapWithKeys(fn($case) => [
            $case->value => $case->label()
        ])->toArray();
    }
}
```

### Enum Rules

- Use PHP 8.1+ backed enums
- Define `label(): string` for display
- Define `values(): array` for dropdowns
- Define `toSelectArray(): array` for form selects

---

## API Response Patterns

### Success Response

```php
return $this->successResponse($data, 'Message');
// or with chainable methods
return $this->successResponse($model, 'Created')->created('model');
return $this->successResponse($model, 'Updated')->updated('model');
return $this->successResponse(null, 'Deleted')->deleted('model');
```

### Paginated Response

```php
return $this->paginatedResponse($paginator, 'Retrieved successfully');
// Returns: { data: [...], pagination: { total, per_page, current_page, ... } }
```

### Error Responses

```php
return $this->failedResponse('Not found', 404);
return $this->notFoundResponse();
return $this->unauthorizedResponse();
return $this->validationErrorResponse($errors);
return $this->serverErrorResponse();
```

---

## Model Filter Pattern

```php
// In Entity
protected static $filterableColumns = ['property_type', 'status', 'country_id'];
protected static $multiFilterableColumns = ['id'];
protected static $searchableColumns = ['name', 'description'];
protected static $dateFilterableColumns = ['created_at'];

// In Service
public function getAll(array $params): LengthAwarePaginator
{
    return Property::query()->filter()->with(['country'])->latest()->paginate(15);
}
```

### Query Parameters

| Parameter | Example | Effect |
|-----------|---------|--------|
| `?search=` | `?search=villa` | LIKE search on searchableColumns |
| `?status=approved` | | WHERE status = 'approved' |
| `?property_type[]=villa&property_type[]=apartment` | | WHERE property_type IN (...) |
| `?created_at[from]=2024-01-01&created_at[to]=2024-12-31` | | Date range filter |