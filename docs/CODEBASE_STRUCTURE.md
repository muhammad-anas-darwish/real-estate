# Codebase Structure Guide

## Overview

Laravel 12 modular architecture using **PSR-4** autoloading with self-registering ServiceProviders.

---

## Module Structure

```
Modules/
├── Auth/                           # Authentication, Users, Roles
├── Core/                           # Shared utilities
│   └── SubModules/
│       ├── Category/
│       ├── Location/
│       └── TemporaryFile/
├── RealEstate/                     # Properties, Listings
└── Communication/                  # Chat, Notifications
```

### Standard Module Directory Layout

```
Modules/{Name}/
├── Database/
│   └── Migrations/                 # Auto-loaded via ServiceProvider
├── DTOs/                           # Data Transfer Objects
├── Entities/                       # Eloquent Models
├── Enums/                          # PHP 8.1+ Enums
├── Http/
│   ├── Controllers/
│   ├── Requests/                   # FormRequest validation
│   └── Resources/                  # API JSON transformers
├── Providers/
│   └── {Name}ServiceProvider.php   # Self-registering
├── Routes/
│   └── api.php                     # API routes
├── Services/                       # Business logic
├── Helpers/                        # Utility classes
├── Policies/                       # Authorization policies
├── Notifications/                  # Laravel notifications
├── Mail/                           # Mail classes
└── Tests/
    ├── Feature/
    └── Unit/
```

---

## ServiceProvider Pattern

Each module's ServiceProvider must:
1. Load routes from `__DIR__.'/../Routes/api.php'`
2. Load migrations from `__DIR__.'/../Database/Migrations'`
3. Register policies

```php
// Modules/RealEstate/Providers/RealEstateServiceProvider.php
public function boot(): void
{
    $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
    $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    $this->registerPolicies([Property::class => PropertyPolicy::class]);
}
```

---

## SubModule Structure

SubModules live in `Modules/{Main}/SubModules/{Sub}/` with their own full directory structure:

```
Modules/Core/SubModules/Category/
├── Database/Migrations/
├── DTOs/
├── Entities/
├── Http/Controllers/
├── Http/Requests/
├── Http/Resources/
├── Services/
└── Routes/api.php
```

AppServiceProvider loads SubModule migrations via:
```php
Modules/{Main}/SubModules/{Sub}/Database/Migrations
```

---

## Key Base Classes

| Layer | Base Class | Location |
|-------|------------|----------|
| Controller | `Controller` | `app/Http/Controllers/Controller.php` |
| Service | `BaseService` | `app/Services/BaseService.php` |
| Model | `BaseModel` | `app/Models/BaseModel.php` |
| Resource | `BaseJsonResource` | `app/Http/Resources/BaseJsonResource.php` |
| DTO | `DTOInterface` | `app/Interfaces/DTOInterface.php` |

---

## Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Module | PascalCase | `RealEstate`, `Auth` |
| Entity | PascalCase singular | `Property`, `User` |
| Controller | PascalCase + Controller | `PropertyController` |
| Service | PascalCase + Service | `PropertyService` |
| Request | PascalCase + Request | `StorePropertyRequest` |
| Resource | PascalCase + Resource | `PropertyResource` |
| DTO | PascalCase + DTO | `PropertyDTO` |
| Enum | PascalCase | `PropertyStatus` |
| Permission | `model.action` | `properties.create` |
| Migration | snake_case | `create_properties_table` |
| Route | kebab-case | `/api/properties` |

---

## Class Inheritance Hierarchy

```
Controller
└── app\Http\Controllers\Controller
    └── uses: ApiResponses, ApplyPermissions, ValidatesRequests

Service
└── app\Services\BaseService

Model
└── app\Models\BaseModel
    └── uses: Filterable

Resource
└── app\Http\Resources\BaseJsonResource
    └── extends: JsonResource
```

---

## Routes Pattern

```php
// Modules/{Name}/Routes/api.php
Route::prefix('api')->group(function () {
    // Public routes - no middleware
    Route::get('properties', [PropertyController::class, 'indexPublic']);

    // Protected routes - requires auth:sanctum
    Route::middleware(['auth:sanctum'])->prefix('dashboard')->group(function () {
        Route::apiResource('properties', PropertyController::class)
            ->except(['index', 'show']);
    });
});
```

---

## Auto-Discovery

Modules are registered via `composer.json` PSR-4 autoloading and Laravel's auto-discovery mechanism. No manual registration needed in `config/app.php`.