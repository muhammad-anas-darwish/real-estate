---
description: Create User Management Module - Roles, Permissions, and User CRUD
agent: plan
---

You are planning the User Management module for a Laravel real estate API project.

## Project Context
- Laravel 12.x with modular architecture
- Uses Spatie Laravel Permission for roles/permissions
- Existing User entity in `Modules/Auth/Entities/User`
- API-only backend (no frontend views)

## Task
Plan the complete User Management module with the following features:

### 1. Roles Management
- Create Role entity in `Modules/Auth/Entities/Role`
- Create RoleController with CRUD operations
- Create RoleService with business logic
- Create RoleResource for API transformation
- API routes: list, show, store, update, destroy

### 2. Permissions Management
- Use existing Spatie permissions
- Create PermissionResource to list all available permissions
- API route to get all permissions

### 3. Assign Permissions to Roles
- Create API endpoint to assign/remove permissions to a role
- Store permissions in `role_has_permissions` table via Spatie

### 4. User CRUD with Role Assignment
- Extend existing User entity with role management
- Create UserController with:
  - List users (with filtering by role_id, status)
  - Show single user
  - Store new user (with role assignment)
  - Update user (with role assignment)
  - Delete/SoftDelete user
  - Toggle user status (enable/disable)
- Create UserResource and UserCollection
- Create UserService with filtering logic

### 5. User Filtering
- Filter users by role_id
- Filter users by status (active/inactive)
- Filter users by creation date range
- Paginated results with proper ordering

## Required Files Structure
```
Modules/Auth/
├── Entities/
│   └── Role.php
├── Http/
│   ├── Controllers/
│   │   └── RoleController.php
│   ├── Requests/
│   │   ├── StoreRoleRequest.php
│   │   ├── UpdateRoleRequest.php
│   │   └── AssignPermissionsRequest.php
│   └── Resources/
│       ├── RoleResource.php
│       └── PermissionResource.php
├── Services/
│   ├── RoleService.php
│   └── UserManagementService.php
├── DTOs/
│   └── RoleDTO.php
├── Enums/
│   └── UserStatus.php
├── Routes/
│   └── api.php (update)
└── Database/
    └── Seeders/
        └── RoleSeeder.php
```

## Key Implementation Details
- Use `Spatie\Permission\Models\Role` or extend it
- User status enum: `active`, `inactive`
- Soft deletes on users (no hard delete)
- Use `HasRoles` trait on User model
- API response format: `{ success: true, message: "...", data: {...} }`
- Use existing DTO pattern from codebase
- Use existing Filterable trait for query scopes

## Permissions to Create
Add these permissions to `PermissionSeeder.php`:
- `roles.list`
- `roles.show`
- `roles.create`
- `roles.edit`
- `roles.delete`
- `users.list`
- `users.show`
- `users.create`
- `users.edit`
- `users.delete`
- `users.toggle-status`

Provide a detailed implementation plan with file paths, line numbers for key methods, and the exact changes needed for each file.
