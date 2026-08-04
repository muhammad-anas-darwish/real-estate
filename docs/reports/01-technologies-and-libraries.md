# Technologies and Libraries Report

## Backend (PHP / Laravel Ecosystem)

| Technology / Library | Version | Purpose |
|---|---|---|
| **Laravel Framework** | ^12.0 | Core PHP framework providing MVC architecture, routing, ORM (Eloquent), queues, caching, events, validation, and all foundational web application features. |
| **Laravel Fortify** | ^1.33 | Authentication scaffolding — provides login, registration, password reset, email verification, and two-factor authentication with custom API responses. Default routes are disabled; all auth endpoints are custom-defined in `Modules/Auth/Routes/api.php`. |
| **Laravel Sanctum** | ^4.0 | API token authentication — issues personal access tokens for SPA and mobile API consumers. Used as the primary auth guard (`auth:sanctum`). |
| **Laravel Horizon** | ^5.46 | Redis-powered queue monitoring dashboard — provides real-time visibility into queue jobs, failed jobs, and worker metrics. |
| **Laravel Telescope** | ^5.16 | Debugging and introspection panel — logs requests, exceptions, queries, cache hits, model events, and more during local development. |
| **Laravel Tinker** | ^2.10.1 | Interactive REPL (Read-Eval-Print Loop) for experimenting with the application from the command line. |
| **Spatie Laravel Permission** | ^6.24 | Roles and permissions (ACL) — defines granular permissions per resource (e.g., `properties.list`, `ads.create`) and assigns roles to users. All permissions are seeded via `PermissionSeeder`. |
| **Spatie Laravel Media Library** | ^11.17 | File uploads and media management — handles property images, ad media with automatic image conversions (thumb, medium). Supports multiple collections per model. |
| **Scribe (knuckleswtf/scribe)** | ^5.6 | Automatic API documentation generation — scans routes, requests, and responses to produce interactive docs (OpenAPI 3.0, Postman collection). |
| **Pusher PHP Server** | ^7.2 | WebSocket real-time broadcasting — powers live chat messaging, typing indicators, and real-time notifications. Used with Laravel's broadcasting system. |
| **Predis** | ^3.4 | Redis client for PHP — handles caching (tagged cache), queue backend, and session storage. |
| **Stripe PHP SDK** | * | Payment processing — handles subscription checkout sessions, webhook events (checkout completed, expired, refunded), and payment lifecycle management. |
| **PHPUnit** | ^11.5.3 | Unit and feature testing framework — runs 4 test suites (Unit, Feature, UserModule, Modules) across all application modules. |

## Frontend / Build Tooling

| Technology / Library | Version | Purpose |
|---|---|---|
| **Vite** | ^7.0.7 | Modern build tool and dev server — compiles and bundles frontend assets (CSS, JS) with fast Hot Module Replacement (HMR). |
| **Laravel Vite Plugin** | ^2.0.0 | Laravel integration for Vite — handles asset path resolution and entry points. |
| **Tailwind CSS v4** | ^4.0.0 | Utility-first CSS framework — provides low-level styling primitives for rapid UI development. |
| **Tailwind CSS Vite Plugin** | ^4.0.0 | Vite-specific Tailwind integration enabling JIT compilation and direct CSS-based configuration. |
| **Axios** | ^1.11.0 | Promise-based HTTP client for the browser — used for API requests from the frontend. |
| **Concurrently** | ^9.0.1 | Runs multiple npm scripts concurrently (e.g., dev server + queue listener + log watcher). |

## Infrastructure (Docker)

| Service | Image | Purpose |
|---|---|---|
| **PHP-FPM (app)** | Custom Dockerfile (`docker/php/Dockerfile`) | Application server running PHP 8.2+ with necessary extensions for the Laravel application. |
| **Nginx** | `nginx:alpine` | Reverse proxy and static file server — routes HTTP requests to the PHP-FPM application container. Exposed on port **8000**. |
| **MySQL** | `mysql:8.0` | Primary relational database — stores all application data. Exposed on port **3307** (external). |
| **Redis** | `redis:alpine` | In-memory data store — used for caching (tagged cache), queue jobs (Horizon), and session storage. Exposed on port **6380** (external). |

## Project Architecture — Modular Structure (5 Modules)

| Module | Purpose |
|---|---|
| **Auth** | User management, role/permission assignment, authentication and authorization actions, DTOs, and tests. |
| **Core** | Foundational data — countries, cities, categories. Contains submodules: Category, Location, and TemporaryFile (for chunked file uploads). |
| **RealEstate** | Core business domain — properties (CRUD, filtering, search, favorites), advertisements (groups, scheduling, media, display), ad tracking (views/visits), and analytics dashboards. |
| **Communication** | Real-time chat (rooms, messages, typing indicators), push notifications (Pusher WebSockets + FCM), notification preferences, user presence, and FCM token management. |
| **Subscription** | Subscription plans, features (toggle/limit types), discounts/coupons, Stripe checkout, webhook handling, subscription lifecycle (active/expired/cancelled), and feature-access gating middleware. |

## Key Conventions and Design Patterns

| Pattern / Convention | Description |
|---|---|
| **Service Layer** | Business logic is encapsulated in Service classes (extending `BaseService`) — controllers remain thin. |
| **DTOs** | Read-only Data Transfer Objects with `fromRequest()` and `toArray()` for clean data passing between layers. |
| **API Resources** | All JSON resources extend `BaseJsonResource` with `getCustomData()` and `getRelationMap()` for consistent response formatting. |
| **Filterable Models** | Models use the `Filterable` trait providing `$filterableColumns`, `$multiFilterableColumns`, `$searchableColumns`, and `$dateFilterableColumns` for query string filtering. |
| **Tagged Cache** | Each service defines a `CACHE_TAG` constant and uses `Cache::tags()` for efficient cache invalidation per entity. |
| **Permissions** | Named `{model}.{action}` pattern (e.g., `properties.list`, `ads.create`). Enforced via middleware generated by the `ApplyPermissions` trait. |
| **Traits** | Common controller traits: `ApiResponses` (success/error/paginated responses), `ApplyPermissions` (CRUD→permission mapping), `ValidatesRequests` (Laravel default). |
