# Auth Module — Test Coverage

## Target
Full test coverage for all Auth controllers, services, and flows.

## Controllers to Test
- `AuthController` — complete existing tests (email verification, reset-password, user info)
- `PublisherController` — profile, analytics, upgrade, reviews
- `RoleController` — roles CRUD
- `UserController` — users CRUD, permissions

## Services to Test
- `OtpService`
- `PublisherService`
- `RoleService`
- `UpgradeRequestService`
- `UserManagementService`

## Files to Create
1. `Modules/Auth/Tests/Feature/AuthControllerTest.php` — email verify, reset-password, user info
2. `Modules/Auth/Tests/Feature/PublisherControllerTest.php` — publisher profile, analytics, upgrade
3. `Modules/Auth/Tests/Feature/RoleControllerTest.php` — roles CRUD
4. `Modules/Auth/Tests/Feature/UserControllerTest.php` — users CRUD, permissions
5. `Modules/Auth/Tests/Unit/OtpServiceTest.php`
6. `Modules/Auth/Tests/Unit/PublisherServiceTest.php`
7. `Modules/Auth/Tests/Unit/RoleServiceTest.php`
8. `Modules/Auth/Tests/Unit/UpgradeRequestServiceTest.php`
9. `Modules/Auth/Tests/Unit/UserManagementServiceTest.php`
