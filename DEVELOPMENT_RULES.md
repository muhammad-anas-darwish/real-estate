# Development Rules for Real Estate Module

## 1. Permission Management
- Any permission string used in `applyPermissions` or anywhere else in the code MUST exist in `database/seeders/PermissionSeeder.php`.
- If a permission is required for a new feature or controller method and is missing from `PermissionSeeder.php`, it MUST be added to the seeder before the code is merged or used.
- Ensure consistency in permission naming conventions (e.g., `model.action`).

## 2. Performance and N+1 Issues
- Always use eager loading (`with`) in Eloquent queries in Services to prevent N+1 issues.
- When retrieving user-specific statuses (like "loved" or "favorited"), use `withExists` or `withCount` in the query rather than computing it in the resource or loop.

## 3. Controller and Service Separation
- Controllers should remain thin. Business logic and complex Eloquent queries must reside in the corresponding Service class.
- Controllers should only handle request validation, service calls, and response generation.

## 4. Code Quality and Typing
- Use type hints for all method arguments and return types.
- Ensure all new code passes the project's static analysis/linting checks.
- Address all LSP (Language Server Protocol) errors immediately after implementing changes.
