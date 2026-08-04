# Real Estate API — Agent Instructions

## Stack
- **Laravel 12**, PHP 8.2+, MySQL 8.0 (Docker) / SQLite (local/.env.example)
- **Fortify** + **Sanctum** for auth (API tokens)
- **Spatie Permission** (roles/permissions), **Spatie Media Library** (images)
- **Scribe** (API docs), **Telescope** (debug), **Horizon** (queues)
- **Pusher** (real-time), **FCM** (push notifications)
- Docker: `docker-compose up -d` (nginx, php-fpm, mysql:3307, redis:6380)

## Key Commands
```
composer setup         # full install (composer, .env, key, migrate, npm, build)
composer dev           # concurrent: serve + queue:listen + pail + vite
composer test          # runs phpunit (config:clear first)
composer pint          # format code (no pint.json — uses defaults)
php artisan scribe:generate   # regenerate API docs
```

## Architecture — Modules
Modules live in `Modules/{Name}/`. Each module's **ServiceProvider** self-registers its routes and migrations:
```php
$this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
$this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
```
- `AppServiceProvider` only loads SubModule migrations: `Modules/{Main}/SubModules/{Sub}/Database/Migrations`
- Providers are registered via `composer.json` PSR-4 autoloading + Laravel auto-discovery
- 4 modules: **Auth**, **Core** (with SubModules: Category, Location, TemporaryFile), **RealEstate**, **Communication**

## Auth
- **Fortify routes are custom**, defined in `routes/api.php` — default Fortify routes disabled via `Fortify::ignoreRoutes()`
- Fortify response contracts (login, register, logout etc.) are overridden with inline anonymous classes in `Modules/Auth/Providers/FortifyServiceProvider.php` — each returns a Sanctum token
- Guard: `auth:sanctum`

## Controllers & Services
- Base `Controller` uses `ApiResponses`, `ApplyPermissions`, `ValidatesRequests` traits
- `ApplyPermissions` maps CRUD methods → permission strings via middleware
- **All permission strings must exist** in `database/seeders/PermissionSeeder.php` before use
- Controllers stay thin; business logic in Service classes (extends `BaseService`)
- Caching via `Cache::tags(static::CACHE_TAG)` — define `CACHE_TAG` const on each service

## API Responses
Use the `ApiResponses` trait helpers:
- `$this->successResponse($data, 'Message')` → `ResponseBuilder` (chained: `->created('model')`, `->updated('model')`, `->deleted('model')`)
- `$this->paginatedResponse($paginator, 'Message')` → JSON with `data` + `pagination` keys
- `$this->failedResponse('msg', $code)` / `$this->validationErrorResponse($errors)`

## API Resources
All resources extend `BaseJsonResource`. Pattern:
- `getCustomData()` — resource-specific fields
- `getRelationMap()` — `['relation' => Resource::class]` — auto-includes when relation is loaded
- Date format: `Y-m-d H:i:s`

## Data Layer
- **DTOs**: `readonly final class FooDTO implements DTOInterface` with `fromRequest(array)` + `toArray()`
- **Models** extend `BaseModel` (uses `Filterable` trait). Define:
  - `$filterableColumns` — single-value equality filters
  - `$multiFilterableColumns` — `WHERE IN` filters (default: `['id']`)
  - `$searchableColumns` — `LIKE` search
  - `$dateFilterableColumns` — date range filters (default: `['created_at']`)
- Usage: `Model::query()->filter()` picks up `?search=`, `?status=`, `?created_at[from]=&created_at[to]=` from request

## Tests
- PHPUnit (4 suites in `phpunit.xml`): `Unit`, `Feature`, `UserModule` (`Modules/Auth/Tests`), `Modules` (all modules)
- Module tests: `Modules/{Name}/Tests/`
- Use `RefreshDatabase` + `$this->actingAs($user)` with Sanctum
- Run single: `php artisan test --testsuite=Modules --filter=PropertyTest`

## Conventions
- Type hints required on all parameters and returns
- Eager load relations (`with()`) in Services to avoid N+1
- Use `withExists`/`withCount` for user-specific booleans (e.g., "loved"), never compute in a loop or resource
- Permissions named `model.action` (e.g., `properties.list`, `properties.create`)
- Scribe auth in `.env`: `SCRIBE_AUTH_KEY=1|yourActualTokenHere` — must match a valid Sanctum token
