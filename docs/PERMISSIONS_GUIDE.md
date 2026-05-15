# Permissions Guide

## Overview

Uses **Spatie Permission** package. All permissions must be defined in `database/seeders/PermissionSeeder.php` before use.

---

## Permission Naming Convention

Format: `model.action` (lowercase, singular model name)

| Action | Usage |
|--------|-------|
| `list` | index, search, filtering |
| `show` | display single record |
| `create` | store new record |
| `edit` | update existing record |
| `delete` | destroy record |
| `toggle-status` | custom action for status change |

### Example Permissions

```php
'properties' => ['list', 'show', 'create', 'edit', 'delete'],
'countries' => ['list', 'show', 'create', 'edit', 'delete'],
'roles' => ['list', 'show', 'create', 'edit', 'delete', 'get-all-permissions'],
```

---

## Defining Permissions

Edit `database/seeders/PermissionSeeder.php`:

```php
private $permissionGroups = [
    'model_name' => ['list', 'show', 'create', 'edit', 'delete'],
];
```

The seeder creates permissions with `guard_name => 'web'`.

**Important:** After adding new permissions, clear cached permissions:
```php
app()[Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

---

## Applying Permissions to Controller

Use the `ApplyPermissions` trait in controller constructor:

```php
use App\Traits\ApplyPermissions;

class PropertyController extends Controller
{
    public function __construct()
    {
        $this->applyPermissions(
            'properties',                              // Permission prefix
            ['store', 'update', 'destroy'],           // CRUD methods (auto-mapped)
            [                                          // Additional methods
                'toggleFavorite' => 'list',
                'statistics' => 'list',
                'updateStatus' => 'edit',
            ]
        );
    }
}
```

### Auto-Mapped CRUD Methods

| Method | Permission |
|--------|------------|
| `show` | `show` |
| `index` | `list` |
| `store` | `create` |
| `update` | `edit` |
| `destroy` | `delete` |

---

## Custom Method Permissions

Map custom actions to permissions explicitly:

```php
$this->applyPermissions(
    'properties',
    ['store', 'update', 'destroy'],
    [
        'toggleFavorite' => 'list',
        'exportPdf' => 'list',
        'bulkAction' => 'edit',
    ]
);
```

---

## Registering Policies

Register model policies in module's ServiceProvider:

```php
// Modules/RealEstate/Providers/RealEstateServiceProvider.php
public function boot(): void
{
    $this->registerPolicies([Property::class => PropertyPolicy::class]);
}
```

---

## Super Admin Role

The seeder assigns ALL permissions to the Super Admin role automatically after permission creation:

```php
$superAdmin->givePermissionTo($permissions->pluck('name')->toArray());
```

---

## Middleware Usage

Permissions are applied via `ApplyPermissions` trait which uses `$this->middleware()` internally. No manual middleware needed.

---

## Cache Invalidation

When permissions change, clear Spatie's cached permissions:
```php
app()[Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

---

## Checklist for Adding New Model Permissions

1. Add model permissions to `$permissionGroups` in `PermissionSeeder.php`
2. Run `php artisan db:seed --class=PermissionSeeder`
3. Use `ApplyPermissions` trait in controller
4. Register policy in module ServiceProvider (optional)