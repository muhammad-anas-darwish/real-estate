---
description: Build User Management Module - Roles, Permissions, and User CRUD
agent: build
---

You are now in build mode. Execute the plan for the User Management module.

## Important Guidelines
- Follow the existing codebase patterns strictly
- Use existing DTO pattern: `readonly final class XxxDTO implements DTOInterface`
- Use existing Service pattern: extend `BaseService`
- Use existing Controller pattern: constructor injection + `applyPermissions()`
- Use existing Request pattern: extend form requests
- Use existing Resource pattern: extend `BaseJsonResource`
- Use existing API response format via `ApiResponses` trait
- Use `Filterable` trait for query scopes
- Use eager loading (`with`) to prevent N+1 queries
- Add type hints for all method arguments and return types

## Steps to Execute

### Step 1: Add Permissions to PermissionSeeder
Open `database/seeders/PermissionSeeder.php` and add these permissions:
- `roles.list`, `roles.show`, `roles.create`, `roles.edit`, `roles.delete`
- `users.list`, `users.show`, `users.create`, `users.edit`, `users.delete`, `users.toggle-status`

### Step 2: Create UserStatus Enum
Create `Modules/Auth/Enums/UserStatus.php`:
```php
readonly final class UserStatus extends Enum
{
    public const ACTIVE = 'active';
    public const INACTIVE = 'inactive';
}
```

### Step 3: Create Role Entity
Create `Modules/Auth/Entities/Role.php` extending Spatie's Role model

### Step 4: Create RoleDTO
Create `Modules/Auth/DTOs/RoleDTO.php` following existing DTO pattern

### Step 5: Create RoleService
Create `Modules/Auth/Services/RoleService.php` extending `BaseService`

### Step 6: Create Role Requests
Create form requests in `Modules/Auth/Http/Requests/`:
- `StoreRoleRequest.php`
- `UpdateRoleRequest.php`
- `AssignPermissionsRequest.php`

### Step 7: Create RoleResource
Create `Modules/Auth/Http/Resources/RoleResource.php`

### Step 8: Create PermissionResource
Create `Modules/Auth/Http/Resources/PermissionResource.php` to list all permissions

### Step 9: Create RoleController
Create `Modules/Auth/Http/Controllers/RoleController.php` with CRUD + assign permissions

### Step 10: Create UserManagementService
Create `Modules/Auth/Services/UserManagementService.php` for user CRUD with filtering

### Step 11: Create User Requests
Create form requests:
- `StoreUserRequest.php` - validate name, email, password, role_ids
- `UpdateUserRequest.php` - validate optional updates
- `AssignRolesRequest.php` - for assigning roles to existing user

### Step 12: Create User Resources
Create:
- `UserResource.php` - single user transformation
- `UserCollection.php` - paginated collection

### Step 13: Create UserController
Extend/create `Modules/Auth/Http/Controllers/UserController.php` with all CRUD operations

### Step 14: Update Routes
Update `Modules/Auth/Routes/api.php` to include new routes

### Step 15: Create RoleSeeder
Create `Modules/Auth/Database/Seeders/RoleSeeder.php` with default roles

## API Endpoints to Create

### Roles
- `GET /api/roles` - list roles
- `GET /api/roles/{id}` - show role
- `POST /api/roles` - create role
- `PUT /api/roles/{id}` - update role
- `DELETE /api/roles/{id}` - delete role
- `POST /api/roles/{id}/permissions` - assign/remove permissions
- `GET /api/permissions` - list all available permissions

### Users
- `GET /api/users` - list users (with filters: role_id, status, from_date, to_date)
- `GET /api/users/{id}` - show user
- `POST /api/users` - create user
- `PUT /api/users/{id}` - update user
- `DELETE /api/users/{id}` - soft delete user
- `PUT /api/users/{id}/toggle-status` - toggle active/inactive

## Response Format
All responses should follow:
```json
{
  "success": true,
  "message": "Operation description",
  "data": { ... }
}
```

Paginated responses:
```json
{
  "success": true,
  "message": "...",
  "data": [ ... ],
  "pagination": { "current_page": 1, "per_page": 15, "total": 100, ... }
}
```

Implement all files with clean, type-hinted code following the project conventions.
