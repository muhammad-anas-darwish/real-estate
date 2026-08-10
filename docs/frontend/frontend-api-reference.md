# Real Estate System — Frontend API Reference

> **Document version:** 1.0 | **Generated:** 2026-07-03
> **Target audience:** Frontend AI / Frontend Developer
> **Base URL:** `http://127.0.0.1:8000`

---

## TABLE OF CONTENTS

1. [Global Conventions](#1-global-conventions)
2. [Scenarios & User Roles](#2-scenarios--user-roles)
3. [Permissions Matrix](#3-permissions-matrix)
4. [Auth Module (Fortify + Users + Roles + Publisher)](#4-auth-module)
5. [Core Module (Categories + Locations + Search + Upload)](#5-core-module)
6. [RealEstate Module (Properties + Ads + Appointments + Rental Cards + Reviews + Analytics)](#6-realestate-module)
7. [Communication Module (Chat + Notifications + FCM)](#7-communication-module)
8. [Subscription Module (Plans + Features + Checkout + My Subscription)](#8-subscription-module)
9. [CRM Module (Leads + Dashboard + Notes)](#9-crm-module)
10. [ServiceProvider Module](#10-serviceprovider-module)
11. [FileSystem Module](#11-filesystem-module)
12. [Deposit Module](#12-deposit-module)
13. [Ledger Module (Accounts + Journal + Payroll)](#13-ledger-module)
14. [Enums Reference](#14-enums-reference)

---

## 1. GLOBAL CONVENTIONS

### 1.1 Response Envelopes

**Single Success:**
```json
{ "success": true, "message": "string|null", "data": { ... } }
```
Status: `200` (or `201` for create with `->created('model')`).

**Paginated Success:**
```json
{
  "success": true,
  "message": "string|null",
  "data": [ ... ],
  "pagination": {
    "total": 100,
    "per_page": 15,
    "current_page": 1,
    "last_page": 7,
    "from": 1,
    "to": 15
  }
}
```

**Error:**
```json
{ "success": false, "message": "Error message", "errors": { "field": ["msg"] } | null, "data": null }
```

### 1.2 Date Formats
- **All API datetimes:** `Y-m-d H:i:s` (e.g. `2026-07-03 14:30:00`)
- **Date-only fields** (RentalCard start_date/end_date): `Y-m-d` (e.g. `2026-07-03`)
- Nullable fields return `null`.

### 1.3 Authentication
- **Auth header:** `Authorization: Bearer {sanctum_token}`
- **Token obtained from:** `POST /api/auth/login` or `POST /api/auth/register`
- All `auth:sanctum` middleware routes require the token.

### 1.4 Pagination & Filtering
All models extend `BaseModel` with `Filterable` trait. Standard query params:

| Pattern | Usage |
|---|---|
| `?field=value` | Exact match |
| `?search=text` | LIKE search on `$searchableColumns` |
| `?field[from]=val&field[to]=val` | Date range |
| `?id[]=1&id[]=2` | WHERE IN |
| `?sort_by=column&sort_order=asc\|desc` | Sorting |
| `?perPage=N&page=N` | Pagination |

### 1.5 Base Resource Auto-Fields
Every resource extends `BaseJsonResource` and auto-includes:
| Field | Type |
|---|---|
| `id` | int |
| `created_at` | string (Y-m-d H:i:s) |
| `updated_at` | string (Y-m-d H:i:s) |

Nested relations appear **only** when eager-loaded via `with()`.

---

## 2. SCENARIOS & USER ROLES

### 2.1 User Roles

| Role | Description | Permissions Count |
|---|---|---|
| **Super Admin** | Full system control | 161 permissions (all) |
| **Trader** | CRM/Leads only | 19 permissions (CRM + dashboard) |
| **Publisher (Individual)** | Property owner listing properties | No special role; uses properties.create/edit etc. |
| **Publisher (Office)** | Real estate office with verified badge | Same as individual + analytics + upgrade flow |
| **Service Provider** | Photographer/Lawyer/Inspector/Marketer | Service provider profile + request management |
| **Buyer/Tenant** | End user browsing properties | No admin permissions; uses socket-based chat, notifications |
| **Unauthenticated Visitor** | Public browsing | Only public routes |

### 2.2 Core Scenarios

#### Scenario 1: User Registration → Browse → Purchase
1. Register via `POST /api/auth/register`
2. Browse properties via `GET /api/properties/browse` (public)
3. View property details `GET /api/properties/{id}/details`
4. Request viewing via `POST /api/dashboard/appointments`
5. Get notifications about confirmed viewing
6. Pay deposit via `POST /api/dashboard/deposits/{id}/pay`
7. Deposit released by admin → `POST /api/dashboard/deposits/{id}/release`

#### Scenario 2: Publisher Lists Property
1. Login → `POST /api/auth/login`
2. Create property → `POST /api/dashboard/properties`
3. Upload property images via TemporaryFile or Media Library
4. Property enters `pending` → admin reviews → `approved`
5. Create rental card for tenant → `POST /api/dashboard/rental-cards`
6. Receive chat from interested buyers via Pusher

#### Scenario 3: Trader (CRM) Flow
1. Login as trader-role user
2. Dashboard summary → `GET /api/dashboard/crm/dashboard/summary`
3. Today's follow-ups → `GET /api/dashboard/crm/dashboard/today`
4. Create lead → `POST /api/dashboard/crm/leads`
5. Schedule follow-up → `POST /api/dashboard/appointments/follow-ups`
6. Change lead status (new → contacted → qualified → won/lost)
7. Export leads → `GET /api/dashboard/crm/leads/export`

#### Scenario 4: Subscription Purchase
1. Browse plans → `GET /api/plans` (public)
2. Validate coupon → `POST /api/coupons/validate`
3. Checkout → `POST /api/checkout` (returns Stripe checkout URL)
4. User pays on Stripe → webhook confirms → subscription active
5. Check current subscription → `GET /api/subscription/current`
6. Check feature access → `GET /api/subscription/features`

#### Scenario 5: Office Upgrade
1. Publisher submits → `POST /api/publisher/upgrade-request`
2. Uploads license + commercial register documents
3. Admin reviews → `GET /api/admin/office-upgrade-requests`
4. Admin approves → `POST /api/admin/upgrade-requests/{id}/approve`
5. User now has `publisher_type=office` + `is_verified=true`

#### Scenario 6: Service Provider Workflow
1. Register as provider → `POST /api/service-provider/register`
2. Client creates request → `POST /api/service-requests`
3. Provider sees request → `GET /api/service-provider/service-requests`
4. Provider accepts → `POST /api/service-provider/service-requests/{id}/accept`
5. Provider starts → `POST /api/service-provider/service-requests/{id}/start`
6. Provider completes → `POST /api/service-provider/service-requests/{id}/complete`
7. Provider withdraws funds → `POST /api/service-provider/withdraw`

#### Scenario 7: File Management
1. Create folder → `POST /api/folders`
2. Upload image → `POST /api/files/image`
3. Create text file → `POST /api/files/text`
4. Move/rename items as needed
5. Check storage → `GET /api/storage/status`
6. Upgrade storage → `POST /api/storage/upgrade`

### 2.3 Permissions vs Pages Mapping

| Page/Section | Role(s) | Required Permissions |
|---|---|---|
| Admin Dashboard | Super Admin | (all) |
| User Management | Super Admin | `users.list/show/create/edit/delete/toggle-status` |
| Role Management | Super Admin | `roles.*` |
| Properties List (Admin) | Super Admin | `properties.list` |
| My Properties | Publisher | `properties.list` (own properties only via owner_id) |
| Create/Edit Property | Publisher | `properties.create/edit` |
| Ad Groups & Ads Management | Super Admin + Publisher | `ad_groups.*`, `ads.*` (ownership-gated) |
| Ad Analytics | Super Admin + Publisher | `ads.view-analytics` |
| Appointments/Viewings | All authenticated | `appointments.*` |
| Rental Cards | Publisher | `rental_cards.*` |
| Reviews | All authenticated | `reviews.list/create/delete` (delete=own only) |
| Offices Directory | All authenticated | (none — public index, see `publishers` permission for admin) |
| Subscription Plans (Admin) | Super Admin | `subscription_plans.*`, `subscription_features.*`, etc. |
| Plans (Public Browsing) | All (including unauthenticated) | (none) |
| Checkout / My Subscription | All authenticated | (none) |
| CRM Dashboard | Trader | `crm_dashboard.view` |
| Leads Management | Trader | `leads.*`, `lead_notes.*`, `lead_follow_ups.*` |
| Service Providers Directory | Public | (none) |
| Service Providers Admin | Super Admin | `service_providers.list/verify/unverify` |
| My Service Requests (Client) | All authenticated | (none) |
| My Service Requests (Provider) | Service Provider | (none) |
| Files & Folders | All authenticated | `files.*` |
| Deposits | All authenticated | `deposits.*` |
| Ledger / Accounts | Super Admin | `accounts.*`, `journal_entries.*`, `trial_balance.view`, `payroll.*` |
| Chat | All authenticated | (none — policy-based) |
| Notifications | All authenticated | (none) |
| Publisher Profile & Stats | Publisher | (none — just auth) |
| Publisher Upgrade | Publisher | (none) |
| Office Verification | Super Admin | `offices.verify/unverify/approve-upgrade/reject-upgrade` |

---

## 3. PERMISSIONS MATRIX

### 3.1 Complete Permission List (161 permissions, 31 categories)

Guard: `web`

| # | Category | Permissions |
|---|----------|-------------|
| 1 | `roles` | `roles.list`, `roles.show`, `roles.create`, `roles.edit`, `roles.delete`, `roles.get-all-permissions` |
| 2 | `users` | `users.list`, `users.show`, `users.create`, `users.edit`, `users.delete`, `users.toggle-status` |
| 3 | `training_categories` | `training_categories.list`, `.show`, `.create`, `.edit`, `.delete` |
| 4 | `health_warnings` | `health_warnings.list`, `.show`, `.create`, `.edit`, `.delete` |
| 5 | `properties` | `properties.list`, `.show`, `.create`, `.edit`, `.delete` |
| 6 | `viewings` | `viewings.list`, `.show`, `.create`, `.edit`, `.delete`, `.confirm`, `.cancel`, `.reschedule`, `.complete` |
| 7 | `appointments` | `appointments.list`, `.show`, `.create`, `.edit`, `.delete`, `.confirm`, `.cancel`, `.reschedule`, `.complete` |
| 8 | `ad_groups` | `ad_groups.list`, `.show`, `.create`, `.edit`, `.delete`, `.archive`, `.restore`, `.set-default` |
| 9 | `ads` | `ads.list`, `.show`, `.create`, `.edit`, `.delete`, `.archive`, `.restore`, `.set-status`, `.link-property`, `.view-analytics`, `.export` |
| 10 | `countries` | `countries.list`, `.show`, `.create`, `.edit`, `.delete` |
| 11 | `cities` | `cities.list`, `.show`, `.create`, `.edit`, `.delete` |
| 12 | `subscription_plans` | `subscription_plans.list`, `.show`, `.create`, `.edit`, `.delete` |
| 13 | `subscription_features` | `subscription_features.list`, `.show`, `.create`, `.edit`, `.delete` |
| 14 | `subscription_plan_features` | `subscription_plan_features.list`, `.show`, `.create`, `.edit`, `.delete` |
| 15 | `subscription_discounts` | `subscription_discounts.list`, `.show`, `.create`, `.edit`, `.delete` |
| 16 | `offices` | `offices.list`, `.show`, `.verify`, `.unverify`, `.list-upgrade-requests`, `.approve-upgrade`, `.reject-upgrade` |
| 17 | `publishers` | `publishers.list`, `.show` |
| 18 | `reviews` | `reviews.list`, `.show`, `.delete` |
| 19 | `service_providers` | `service_providers.list`, `.show`, `.verify`, `.unverify` |
| 20 | `service_requests` | `service_requests.list`, `.show` |
| 21 | `accounts` | `accounts.list`, `.show`, `.create`, `.edit`, `.delete` |
| 22 | `journal_entries` | `journal_entries.list`, `.show`, `.create`, `.edit`, `.delete`, `.post` |
| 23 | `trial_balance` | `trial_balance.view` |
| 24 | `payroll` | `payroll.list`, `.manage`, `.run` |
| 25 | `leads` | `leads.list`, `.show`, `.create`, `.edit`, `.delete`, `.change-status`, `.archive`, `.restore`, `.export` |
| 26 | `lead_notes` | `lead_notes.list`, `.show`, `.create`, `.edit`, `.delete` |
| 27 | `lead_follow_ups` | `lead_follow_ups.list`, `.show`, `.create`, `.edit`, `.delete`, `.complete` |
| 28 | `crm_dashboard` | `crm_dashboard.view` |
| 29 | `rental_cards` | `rental_cards.list`, `.show`, `.create`, `.edit`, `.delete`, `.end`, `.renew` |
| 30 | `files` | `files.list`, `.show`, `.create`, `.edit`, `.delete`, `.move`, `.rename`, `.quota` |
| 31 | `deposits` | `deposits.list`, `.show`, `.create`, `.edit`, `.delete`, `.release`, `.refund` |

### 3.2 Roles

**super-admin:** All 161 permissions
**trader:** 19 permissions — `leads.*`, `lead_notes.*`, `lead_follow_ups.*`, `crm_dashboard.view`

---

## 4. AUTH MODULE

### 4.1 Fortify Auth Routes

All prefixed `/api/auth`.

#### Public (no auth required)

| Method | URL | Name | Description |
|---|---|---|---|
| `POST` | `/api/auth/register` | `register` | Register new user |
| `POST` | `/api/auth/login` | `login` | Login → returns Sanctum token |
| `POST` | `/api/auth/two-factor-challenge` | `two-factor.login` | 2FA challenge |
| `POST` | `/api/auth/forgot-password` | `password.email` | Send reset link |
| `POST` | `/api/auth/reset-password` | `password.reset` | Reset password |
| `GET` | `/api/auth/email/verify/{id}/{hash}` | `verification.verify` | Verify email (signed) |

**Register Request:**
| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max:255 |
| `email` | string | required, email, unique:users |
| `password` | string | required, min:8 |
| `password_confirmation` | string | required, same:password |

**Login Request:**
| Field | Type | Rules |
|---|---|---|
| `email` | string | required, email |
| `password` | string | required |

**Response** (login/register): Sanctum token + user data.

#### Protected (auth:sanctum)

| Method | URL | Name | Description |
|---|---|---|---|
| `POST` | `/api/auth/logout` | `logout` | Revoke token |
| `GET` | `/user` | — | Get current user |
| `POST` | `/api/auth/email/verification-notification` | `verification.send` | Resend verify email |
| `PUT` | `/api/auth/user/profile-information` | `user-profile-information.update` | Update name/email |
| `PUT` | `/api/auth/user/password` | `user-password.update` | Change password |
| `POST` | `/api/auth/user/confirm-password` | `password.confirm` | Confirm password |
| `POST` | `/api/auth/user/two-factor-authentication` | `two-factor.enable` | Enable 2FA |
| `POST` | `/api/auth/user/confirmed-two-factor-authentication` | `two-factor.confirm` | Confirm 2FA |
| `DELETE` | `/api/auth/user/two-factor-authentication` | `two-factor.disable` | Disable 2FA |
| `GET` | `/api/auth/user/two-factor-qr-code` | `two-factor.qr-code` | Get QR code |
| `GET` | `/api/auth/user/two-factor-secret-key` | `two-factor.secret-key` | Get secret |
| `GET` | `/api/auth/user/two-factor-recovery-codes` | `two-factor.recovery-codes` | List recovery codes |
| `POST` | `/api/auth/user/two-factor-recovery-codes` | `two-factor.regenerate-recovery-codes` | Regenerate codes |

### 4.2 User Management (Admin)

All require `auth:sanctum`.

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/users` | `users.list` | List users (paginated) |
| `GET` | `/api/users/{id}` | `users.show` | Show user |
| `POST` | `/api/users` | `users.create` | Create user |
| `PUT` | `/api/users/{id}` | `users.edit` | Update user |
| `DELETE` | `/api/users/{id}` | `users.delete` | Delete user |
| `PUT` | `/api/users/{id}/toggle-status` | `users.toggle-status` | Toggle active/inactive |

**StoreUserRequest:**
| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max:255 |
| `email` | string | required, email, unique:users |
| `password` | string | required, min:8 |
| `status` | string | sometimes, in:active,inactive |
| `role_ids` | array | nullable |
| `role_ids.*` | int | exists:roles,id |

**UpdateUserRequest:** Same but all `sometimes`.

**UserResource Response:**
| Field | Type | Description |
|---|---|---|
| `id` | int | |
| `created_at` | string | |
| `updated_at` | string | |
| `name` | string | |
| `email` | string | |
| `status` | string | active/inactive |
| `publisher_type` | string\|null | individual/office |
| `phone` | string\|null | |
| `website_url` | string\|null | |
| `social_links` | object\|null | JSON |
| `description` | string\|null | |
| `is_verified` | bool | |
| `employees_count` | int\|null | |
| `contact_preference` | string\|null | chat/external |
| `average_rating` | float\|null | |
| `avatar_url` | string\|null | |
| `reviews_count` | int | (conditional — only if `reviews_count` loaded) |
| `roles` | array | (conditional — if loaded) |
| `media` | array | (conditional — if loaded) |

### 4.3 Role Management (Admin)

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/roles` | `roles.list` | List roles |
| `GET` | `/api/roles/{id}` | `roles.show` | Show role |
| `POST` | `/api/roles` | `roles.create` | Create role |
| `PUT` | `/api/roles/{id}` | `roles.edit` | Update role |
| `DELETE` | `/api/roles/{id}` | `roles.delete` | Delete role |
| `POST` | `/api/roles/{id}/permissions` | `roles.edit` | Assign permissions to role |
| `GET` | `/api/permissions` | `roles.list` | List all permissions |

**StoreRoleRequest:**
| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max:255, unique:roles.name |
| `guard_name` | string | nullable |
| `permissions` | array | nullable |
| `permissions.*` | string | exists:permissions,name |

**AssignPermissionsRequest:**
| Field | Type | Rules |
|---|---|---|
| `permissions` | array | required |
| `permissions.*` | string | exists:permissions,name |

### 4.4 Publisher Profile Routes

All auth:sanctum, no explicit permissions.

| Method | URL | Description |
|---|---|---|
| `PUT` | `/api/publisher/profile` | Update publisher profile |
| `PUT` | `/api/publisher/contact-preference` | Update contact preference |
| `GET` | `/api/publisher/statistics` | Publisher statistics |
| `GET` | `/api/publisher/analytics` | Advanced analytics (subscription-gated) |
| `POST` | `/api/publisher/upgrade-request` | Submit office upgrade request |
| `GET` | `/api/publisher/upgrade-status` | Check upgrade request status |

**Update Profile body:** (all optional — `name`, `phone`, `website_url`, `social_links`, `description`, `employees_count`, `avatar`)

**Contact Preference body:**
| Field | Type | Rules |
|---|---|---|
| `contact_preference` | string | default:chat, in:chat,external |

**Upgrade Request body:**
| Field | Type | Description |
|---|---|---|
| `license_document` | file | License document |
| `commercial_register_document` | file | Commercial register |

### 4.5 Office/Publisher Directory

| Method | URL | Auth | Permission | Description |
|---|---|---|---|---|
| `GET` | `/api/dashboard/publishers/offices` | sanctum | — | List offices (paginated, filterable) |
| `GET` | `/api/dashboard/publishers/offices/{id}` | sanctum | — | Show office profile + reviews |

**OfficeResource:** `id`, `name`, `phone`, `website_url`, `social_links`, `description`, `is_verified`, `employees_count`, `contact_preference`, `average_rating`, `reviews_count`, `avatar_url`, `media[]`

**OfficeProfileResource** (extends OfficeResource with): `email`, `license_document_url`

### 4.6 Admin Office Management

| Method | URL | Permission | Description |
|---|---|---|---|
| `POST` | `/api/admin/offices/{id}/verify` | `offices.verify` | Verify office |
| `POST` | `/api/admin/offices/{id}/unverify` | `offices.unverify` | Unverify office |
| `GET` | `/api/admin/office-upgrade-requests` | `offices.list-upgrade-requests` | List upgrade requests |
| `POST` | `/api/admin/upgrade-requests/{id}/approve` | `offices.approve-upgrade` | Approve upgrade |
| `POST` | `/api/admin/upgrade-requests/{id}/reject` | `offices.reject-upgrade` | Reject upgrade |

Reject body: `rejection_reason` (string, default: "")

**UpgradeRequestResource:** `id`, `user_id`, `status` (pending/approved/rejected), `rejection_reason`, `reviewed_by`, `reviewed_at`, `user` (if loaded), `reviewer` (if loaded)

---

## 5. CORE MODULE

### 5.1 Categories

| Method | URL | Auth | Permission | Description |
|---|---|---|---|---|
| `GET` | `/api/categories/public` | — | — | Public category list |
| `GET` | `/api/categories/public/{id}` | — | — | Public category show |
| `GET` | `/api/categories` | sanctum | `categories.list` | Admin list |
| `POST` | `/api/categories` | sanctum | `categories.create` | Create category |
| `GET` | `/api/categories/{id}` | sanctum | `categories.show` | Show category |
| `PUT/PATCH` | `/api/categories/{id}` | sanctum | `categories.edit` | Update category |
| `DELETE` | `/api/categories/{id}` | sanctum | `categories.delete` | Delete category |

**Query params (public index):** `?search=`, `?type=property|car`, `?sort_by=created_at`, `?sort_order=desc`, `?perPage=15`

**StoreCategoryRequest:**
| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max:255 |
| `type` | string | required, in:property,car |

**CategoryResource:** `id`, `name`, `type` (property/car), `created_at`, `updated_at`

### 5.2 Countries & Cities

Base URL: `/api/location`

#### Countries

| Method | URL | Auth | Permission |
|---|---|---|---|
| `GET` | `/api/location/countries` | — | — |
| `GET` | `/api/location/countries/{id}` | — | — |
| `POST` | `/api/location/countries` | sanctum | `countries.create` |
| `PUT/PATCH` | `/api/location/countries/{id}` | sanctum | `countries.edit` |
| `DELETE` | `/api/location/countries/{id}` | sanctum | `countries.delete` |

**StoreCountryRequest / UpdateCountryRequest:**
| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max:128 |
| `code` | string | nullable, max:3 |
| `phone_code` | string | nullable, max:4 |
| `is_active` | boolean | required |

**CountryResource:** `id`, `name`, `code`, `phone_code`, `is_active`, `cities_count`, `created_at`, `updated_at`, `cities` (if loaded)

#### Cities

| Method | URL | Auth | Permission |
|---|---|---|---|
| `GET` | `/api/location/cities` | — | — |
| `GET` | `/api/location/cities/{id}` | — | — |
| `POST` | `/api/location/cities` | sanctum | `cities.create` |
| `PUT/PATCH` | `/api/location/cities/{id}` | sanctum | `cities.edit` |
| `DELETE` | `/api/location/cities/{id}` | sanctum | `cities.delete` |

**StoreCityRequest / UpdateCityRequest:**
| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max:128 |
| `country_id` | int | required, exists:countries,id |
| `state_provianc` | string | nullable |
| `postal_code` | string | nullable |
| `is_active` | boolean | nullable |

**CityResource:** `id`, `name`, `country_id`, `state_provianc`, `postal_code`, `is_active`, `created_at`, `updated_at`, `country` (if loaded)

### 5.3 Search

| Method | URL | Auth | Description |
|---|---|---|---|
| `GET` | `/api/search/{type}` | — | Public search |

**Types:** `cities`, `countries`, `users`

**Example:** `GET /api/search/cities?search=Riyadh`

Uses model's `filter()` scope. Returns `{ success: true, data: [...] }`. Limited to 20 results, returns `['id', 'name']` only.

### 5.4 Temporary File Upload (NOT WIRED)

`UploadFileController` exists but **no route is registered** yet. When wired:

**UploadFileRequest:**
| Field | Type | Rules |
|---|---|---|
| `type` | string | required, in:property (or other registered types) |
| `file` | file | required_without:files, mimes (type-dependent), max (type-dependent) |
| `files` | array | required_without:file |
| `files.*` | file | required |

**TemporaryFileResource:** `id`, `type`, `folder`, `filename`, `created_at`, `updated_at`

---

## 6. REALESTATE MODULE

### 6.1 Properties

#### Public Routes (no auth)

| Method | URL | Description |
|---|---|---|
| `GET` | `/api/properties/browse` | Browse/filter properties |
| `GET` | `/api/properties/random` | 10 random properties |
| `GET` | `/api/properties/{id}/details` | Property detail |

**AdvancedPropertyFilterRequest (query params for browse):**
| Field | Type | Rules |
|---|---|---|
| `price_min` | numeric | nullable, min:0 |
| `price_max` | numeric | nullable, min:0 |
| `area_min` | numeric | nullable, min:0 |
| `area_max` | numeric | nullable, min:0 |
| `rooms_min` | int | nullable, min:0 |
| `rooms_max` | int | nullable, min:0 |
| `bathrooms_min` | int | nullable, min:0 |
| `bathrooms_max` | int | nullable, min:0 |
| `property_type` | string | nullable, in:apartment,house,villa,land,commercial,office,warehouse,other |
| `type_of_contract` | string | nullable, in:sale,rent |
| `country_id` | int | nullable, exists:countries,id |
| `city_id` | int | nullable, exists:cities,id |
| `search` | string | nullable, max:255 |
| `sort_by` | string | nullable, in:price,area,rooms,bathrooms,created_at,views |
| `sort_order` | string | nullable, in:asc,desc |
| `perPage` | int | nullable, min:1, max:100 |

#### Dashboard Routes (auth:sanctum)

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/dashboard/properties` | `properties.list` | Dashboard property index |
| `GET` | `/api/dashboard/properties/statistics` | `properties.list` | Property statistics |
| `GET` | `/api/dashboard/properties/{id}` | `properties.list` | Dashboard property detail |
| `POST` | `/api/dashboard/properties` | `properties.create` | Create property (201) |
| `POST` | `/api/dashboard/properties/with-photographer` | `properties.create` | Create + photographer (201) |
| `PATCH` | `/api/dashboard/properties/{id}` | `properties.edit` | Update property |
| `DELETE` | `/api/dashboard/properties/{id}` | `properties.delete` | Delete property |
| `PATCH` | `/api/dashboard/properties/{id}/status` | `properties.edit` | Update status |
| `POST` | `/api/dashboard/properties/{id}/favorite` | `properties.list` | Toggle favorite |
| `GET` | `/api/dashboard/my-properties` | `properties.list` | My properties |

**StorePropertyRequest (create):**
| Field | Type | Required | Rules |
|---|---|---|---|
| `name` | string | Yes | max:255 |
| `description` | string | Yes | — |
| `country_id` | int | Yes | exists:countries,id |
| `city_id` | int | Yes | exists:cities,id where country_id matches |
| `longitude` | numeric | No | between:-180,180 |
| `latitude` | numeric | No | between:-90,90 |
| `property_type` | string | Yes | in:apartment,house,villa,land,commercial,office,warehouse,other |
| `type_of_contract` | string | Yes | in:sale,rent |
| `rooms` | int | Yes | min:0 |
| `bathrooms` | int | Yes | min:0 |
| `area` | numeric | Yes | min:0 |
| `detailed_info` | string | No | — |
| `price` | numeric | Yes | min:0 |
| `currency` | string | No | size:3, default:USD |
| `main_image` | array | No | `{ id: int|null, temporary_folder: string|null }` |
| `gallery` | array | No | `[{ id: int|null, temporary_folder: string|null }]` |

**UpdatePropertyRequest:** Same fields, all `sometimes`/optional. Admin can set `status` field.

**UpdatePropertyStatusRequest:**
| Field | Type | Rules |
|---|---|---|
| `status` | string | required, in:pending,under_inspection,approved,rejected,suspended,sold,rented,archived,draft |
| `rejection_reason` | string | required_if:status=rejected, nullable, max:1000 |

**PropertyResource fields:**
| Field | Type | Description |
|---|---|---|
| `id` | int | |
| `name` | string | |
| `description` | string | |
| `country_id` | int | |
| `city_id` | int | |
| `longitude` | float\|null | |
| `latitude` | float\|null | |
| `country` | object\|null | CountryResource (if loaded) |
| `city` | object\|null | CityResource (if loaded) |
| `type_of_contract` | string | sale/rent |
| `property_type` | string | enum value |
| `rooms` | int | |
| `bathrooms` | int | |
| `area` | float | |
| `detailed_info` | string\|null | |
| `price` | float | |
| `currency` | string | 3-letter |
| `formatted_price` | string | e.g. "USD 1,234.00" |
| `status` | string | PropertyStatus |
| `rejection_reason` | string\|null | |
| `is_loved` | bool | Current user favorited? |
| `views` | int | |
| `main_image` | string\|null | URL |
| `main_image_thumb` | string\|null | |
| `gallery` | array | URLs |
| `publisher_id` | int | |
| `publisher_type` | string | |
| `approved_by` | int\|null | |
| `approved_at` | string\|null | |
| `publisher_is_verified` | bool | |
| `is_physically_verified` | bool | |
| `inspection_requested_at` | string\|null | |
| `inspection_completed_at` | string\|null | |
| `inspection_score` | float\|null | |
| `inspection_report` | string\|null | |
| `publisher` | object\|null | UserResource |
| `approver` | object\|null | UserResource |
| `media` | array\|null | TemporaryFileResource[] |

### 6.2 Appointments (also accessible as /viewings)

All dashboard routes, auth:sanctum.

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/dashboard/appointments` | `appointments.list` | List/filter |
| `GET` | `/api/dashboard/appointments/{id}` | `appointments.show` | Show |
| `POST` | `/api/dashboard/appointments` | `appointments.create` | Book (201) |
| `POST` | `/api/dashboard/appointments/follow-ups` | `appointments.create` | Schedule follow-up (201) |
| `GET` | `/api/dashboard/appointments/calendar` | `appointments.list` | Calendar view |
| `GET` | `/api/dashboard/appointments/my` | `appointments.list` | My appointments |
| `GET` | `/api/dashboard/appointments/schedule` | `appointments.list` | Agent schedule |
| `DELETE` | `/api/dashboard/appointments/{id}` | `appointments.delete` | Delete |
| `PATCH` | `/api/dashboard/appointments/{id}/confirm` | `appointments.edit` | Confirm |
| `PATCH` | `/api/dashboard/appointments/{id}/reschedule` | `appointments.edit` | Reschedule |
| `PATCH` | `/api/dashboard/appointments/{id}/cancel` | `appointments.cancel` | Cancel |
| `PATCH` | `/api/dashboard/appointments/{id}/complete` | `appointments.edit` | Complete |
| `PATCH` | `/api/dashboard/appointments/{id}/no-show` | `appointments.edit` | Mark no-show |

**ViewingFilterRequest (query params):**
| Field | Type | Rules |
|---|---|---|
| `property_id` | int | nullable |
| `user_id` | int | nullable |
| `agent_id` | int | nullable |
| `status` | string | nullable, in:pending,confirmed,rescheduled,cancelled,completed,no_show |
| `viewing_type` | string | nullable, in:in_person,virtual,open_house |
| `scheduled_at.from` | date | nullable |
| `scheduled_at.to` | date | nullable |
| `date` | date | nullable |
| `search` | string | nullable, max:255 |
| `perPage` | int | nullable, min:1, max:100 |
| `sort_by` | string | nullable, in:scheduled_at,created_at,status |
| `sort_order` | string | nullable, in:asc,desc |

**BookAppointmentRequest (create):**
| Field | Type | Required | Rules |
|---|---|---|---|
| `type` | string | No | in:viewing,follow_up,general; default:viewing |
| `property_id` | int | conditional | required_if:type=viewing, exists:properties,id |
| `scheduled_at` | string | Yes | date, after:now |
| `duration_minutes` | int | No | min:15, max:240 |
| `buffer_minutes` | int | No | min:0, max:60 |
| `viewing_type` | string | No | in:in_person,virtual,open_house |
| `contact_method` | string | No | in:call,whatsapp,visit,email |
| `contact_name` | string | No | max:255 |
| `contact_phone` | string | No | max:30 |
| `notes` | string | No | max:1000 |
| `max_attendees` | int | No | min:1, max:50 |
| `followable_id` | int | conditional | required_if:type=follow_up |
| `followable_type` | string | conditional | required_if:type=follow_up |

**ScheduleFollowUpRequest:**
| Field | Type | Required | Rules |
|---|---|---|---|
| `followable_id` | int | Yes | — |
| `followable_type` | string | Yes | max:255 |
| `agent_id` | int | Yes | exists:users,id |
| `scheduled_at` | string | Yes | date, after:now |
| `contact_method` | string | No | in:call,whatsapp,visit,email |
| `duration_minutes` | int | No | min:15, max:240 |
| `buffer_minutes` | int | No | min:0, max:60 |
| `notes` | string | No | max:1000 |
| `contact_name` | string | No | max:255 |
| `contact_phone` | string | No | max:30 |

**UpdateAppointmentStatusRequest (confirm/reschedule/cancel):**
| Field | Type | Required | Rules |
|---|---|---|---|
| `status` | string | Yes | in:pending,confirmed,rescheduled,cancelled,completed,no_show |
| `cancellation_reason` | string | conditional | required_if:status=cancelled, max:500 |
| `agent_notes` | string | No | max:1000 |
| `scheduled_at` | string | conditional | required_if:status=rescheduled, date, after:now |

**AppointmentResource fields:**
| Field | Type | Description |
|---|---|---|
| `id` | int | |
| `created_at` | string | |
| `updated_at` | string | |
| `type` | string | viewing/follow_up/general |
| `property_id` | int\|null | |
| `user_id` | int\|null | |
| `agent_id` | int\|null | |
| `scheduled_at` | string | |
| `duration_minutes` | int\|null | |
| `buffer_minutes` | int\|null | |
| `status` | string | ViewingStatus |
| `viewing_type` | string\|null | |
| `contact_method` | string\|null | |
| `contact_name` | string\|null | |
| `contact_phone` | string\|null | |
| `notes` | string\|null | |
| `agent_notes` | string\|null | |
| `cancelled_by` | int\|null | |
| `cancellation_reason` | string\|null | |
| `max_attendees` | int\|null | |
| `confirmed_at` | string\|null | |
| `completed_at` | string\|null | |
| `cancelled_at` | string\|null | |
| `ends_at` | string\|null | |
| `followable_id` | int\|null | |
| `followable_type` | string\|null | |
| `property` | object\|null | PropertyResource |
| `user` | object\|null | UserResource |
| `agent` | object\|null | UserResource |
| `cancelledBy` | object\|null | UserResource |

### 6.3 Ad Groups

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/dashboard/ad-groups` | (auth only) | List |
| `GET` | `/api/dashboard/ad-groups/{id}` | (auth only) | Show |
| `POST` | `/api/dashboard/ad-groups` | (auth only) | Create (201) |
| `PATCH` | `/api/dashboard/ad-groups/{id}` | (auth only) | Update |
| `DELETE` | `/api/dashboard/ad-groups/{id}` | (auth only) | Archive |
| `POST` | `/api/dashboard/ad-groups/{id}/restore` | (auth only) | Restore |
| `POST` | `/api/dashboard/ad-groups/{id}/set-default` | (auth only) | Set default ad |
| `DELETE` | `/api/dashboard/ad-groups/{id}/default` | (auth only) | Remove default |

**CreateAdGroupRequest:**
| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max:255, unique:ad_groups.name |
| `description` | string | nullable, max:1000 |

**UpdateAdGroupRequest:**
| Field | Type | Rules |
|---|---|---|
| `name` | string | sometimes, max:255, unique (ignoring current) |
| `status` | string | sometimes, in:active,inactive |
| `is_archived` | bool | sometimes |

**SetDefaultAdRequest:**
| Field | Type | Rules |
|---|---|---|
| `ad_id` | int | required, exists:ads,id |
| `ad_group_id` | int | required |

**AdGroupResource:** `id`, `name`, `description`, `status` (active/inactive), `is_archived`, `ads_count`, `default_ad_id`, `created_at`, `updated_at`, `ads[]` (if loaded), `creator` (if loaded)

### 6.4 Ads

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/dashboard/ads` | `ads.list` | List |
| `GET` | `/api/dashboard/ads/{id}` | `ads.show` | Show |
| `POST` | `/api/dashboard/ads` | `ads.create` | Create (201) |
| `PATCH` | `/api/dashboard/ads/{id}` | `ads.edit` | Update |
| `DELETE` | `/api/dashboard/ads/{id}` | `ads.delete` | Archive (soft-delete) |
| `POST` | `/api/dashboard/ads/{id}/restore` | `ads.edit` | Restore |
| `POST` | `/api/dashboard/ads/{id}/status` | `ads.edit` | Set status |
| `POST` | `/api/dashboard/ads/{id}/link-property` | `ads.edit` | Link property |
| `DELETE` | `/api/dashboard/ads/{id}/property` | `ads.edit` | Unlink property |

**CreateAdRequest:**
| Field | Type | Required | Rules |
|---|---|---|---|
| `title` | string | Yes | max:255 |
| `description` | string | No | max:2000 |
| `ad_group_id` | int | No | exists:ad_groups,id |
| `media_type` | string | Yes | in:image,video |
| `external_url` | string | No | url |
| `property_id` | int | No | exists:properties,id |
| `status` | string | No | in:draft,active,paused |
| `start_date` | date | No | — |
| `end_date` | date | No | after_or_equal:start_date |
| `media` | array | No | — |

**UpdateAdRequest:** Same fields, all `sometimes`.

**AdResource fields:**
| Field | Type | Description |
|---|---|---|
| `id` | int | |
| `title` | string | |
| `description` | string\|null | |
| `type` | string\|null | banner/sponsored |
| `media_type` | string\|null | image/video |
| `external_url` | string\|null | |
| `status` | string\|null | draft/active/paused/archived |
| `is_default` | bool | |
| `start_date` | string\|null | |
| `end_date` | string\|null | |
| `is_linked_to_property` | bool | |
| `view_count` | int | (when present) |
| `visit_count` | int | (when present) |
| `ctr` | float | (when present) |
| `amount_paid` | float | (sponsored only) |
| `currency` | string | (sponsored only) |
| `payment_method` | string | (sponsored only) |
| `pricing_tier` | string | (sponsored only) |
| `sponsor_duration` | string | (sponsored only) |
| `target_url` | string | (when present) |
| `starts_at` | string\|null | (when present) |
| `ends_at` | string\|null | (when present) |
| `ad_group` | object\|null | AdGroupResource |
| `property` | object\|null | PropertyResource |
| `creator` | object\|null | UserResource |
| `media` | array\|null | AdMediaResource[] |

**AdMediaResource:** `id`, `file_url` (signed URL, 60 min), `thumbnail_url`, `media_type`, `sort_order`

### 6.5 Ad Display (Public)

| Method | URL | Auth | Description |
|---|---|---|---|
| `GET` | `/api/ads/display` | — | All available ads |
| `GET` | `/api/public/ads/display` | — | All available ads |
| `GET` | `/api/public/ads/display/{groupId}` | — | Group ads |
| `GET` | `/api/public/ads/display/standalone` | — | Standalone ads |

**AdDisplayDTO response:** `title`, `description`, `media_type`, `external_url`, `media_urls[]` (AdMediaResource[]), `has_link`

### 6.6 Ad Tracking (Public)

| Method | URL | Description |
|---|---|---|
| `POST` | `/api/ads/{id}/track/view` | Record view |
| `POST` | `/api/ads/{id}/track/visit` | Record visit (click) |

Uses IP + User-Agent. No request body.

### 6.7 Ad Analytics

| Method | URL | Auth | Description |
|---|---|---|---|
| `GET` | `/api/dashboard/analytics/dashboard` | sanctum | Dashboard overview |
| `GET` | `/api/dashboard/analytics/groups/{groupId}` | sanctum + owner | Group analytics |
| `GET` | `/api/dashboard/analytics/ads/{adId}` | sanctum + owner | Ad analytics |
| `GET` | `/api/dashboard/analytics/export` | sanctum | Export |

**AdAnalyticsRequest (query params):**
| Field | Type | Rules |
|---|---|---|
| `date_from` | date | nullable |
| `date_to` | date | nullable |
| `group_id` | mixed | nullable |
| `ad_id` | mixed | nullable |
| `period` | string | nullable, in:day,week,month |

### 6.8 Sponsored Ads

| Method | URL | Auth | Permission | Description |
|---|---|---|---|---|
| `GET` | `/api/sponsored-ads/pricing` | — | — | Pricing tiers |
| `GET` | `/api/sponsored-ads/active` | — | — | Active sponsored ads |
| `GET` | `/api/dashboard/sponsored-ads` | sanctum | `sponsored_ads.list_own` | My sponsored ads |
| `POST` | `/api/dashboard/sponsored-ads` | sanctum | `sponsored_ads.create` | Create (201) |
| `GET` | `/api/dashboard/sponsored-ads/{id}` | sanctum | `sponsored_ads.show_own` | Show |
| `POST` | `/api/dashboard/sponsored-ads/{id}/cancel` | sanctum | `sponsored_ads.cancel` | Cancel |

**CreateSponsoredAdRequest:**
| Field | Type | Required | Rules |
|---|---|---|---|
| `title` | string | Yes | max:255 |
| `description` | string | No | max:2000 |
| `media_type` | string | Yes | in:image,video |
| `external_url` | string | No | url, max:2048 |
| `property_id` | int | No | exists:properties,id |
| `pricing_tier` | string | Yes | in:basic,standard,premium |
| `sponsor_duration` | string | Yes | in:7_days,14_days,30_days |
| `payment_method` | string | Yes | in:stripe,balance |
| `currency` | string | Yes | in:usd,sar,aed |
| `idempotency_key` | string | No | max:255 |
| `media` | array | No | array of files |

### 6.9 Rental Cards

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/dashboard/rental-cards` | `rental_cards.list` | List/filter |
| `GET` | `/api/dashboard/rental-cards/{id}` | `rental_cards.show` + Gate | Show |
| `GET` | `/api/dashboard/properties/{propertyId}/rental-cards/active` | `rental_cards.show` + Gate | Active card |
| `GET` | `/api/dashboard/properties/{propertyId}/rental-cards/history` | `rental_cards.list` | History |
| `POST` | `/api/dashboard/rental-cards` | `rental_cards.create` | Create (201) |
| `PATCH` | `/api/dashboard/rental-cards/{id}` | `rental_cards.edit` + Gate | Update |
| `PATCH` | `/api/dashboard/rental-cards/{id}/end` | `rental_cards.end` + Gate | End |
| `PATCH` | `/api/dashboard/rental-cards/{id}/renew` | `rental_cards.renew` + Gate | Renew |
| `DELETE` | `/api/dashboard/rental-cards/{id}` | `rental_cards.delete` + Gate | Delete |

**RentalCardFilterRequest (query):**
| Field | Type | Rules |
|---|---|---|
| `property_id` | int | nullable |
| `owner_id` | int | nullable (super-admin only) |
| `tenant_user_id` | int | nullable |
| `status` | string | nullable, in:active,ended,cancelled,renewed |
| `is_renewable` | bool | nullable |
| `search` | string | nullable, max:100 |
| `start_date` | date | nullable |
| `end_date` | date | nullable |
| `sort_by` | string | nullable, in:created_at,start_date,end_date |
| `sort_order` | string | nullable, in:asc,desc |
| `perPage` | int | nullable, min:1, max:100 |

**CreateRentalCardRequest:**
| Field | Type | Required | Rules |
|---|---|---|---|
| `property_id` | int | Yes | exists where publisher_id=self and status=approved |
| `tenant_user_id` | int | No | exists:users,id |
| `external_tenant_name` | string | No | max:255, required_without:tenant_user_id |
| `external_tenant_phone` | string | No | max:32 |
| `external_tenant_email` | email | No | max:255 |
| `external_tenant_id_notes` | string | No | max:1000 |
| `start_date` | date | Yes | after_or_equal:today |
| `end_date` | date | Yes | after:start_date |
| `terms` | string | No | max:5000 |
| `notes` | string | No | max:5000 |
| `is_renewable` | bool | No | default:false |
| `pre_rental_photos` | file[] | No | max:20, image, max:5120KB each |

**UpdateRentalCardRequest:** `end_date`, `terms`, `notes`, `is_renewable` (immutable: property_id, owner_id, tenant_user_id, start_date)

**EndRentalCardRequest:**
| Field | Type | Rules |
|---|---|---|
| `ended_at` | date | nullable, before_or_equal:today |
| `end_reason` | string | nullable, max:1000 |

**RenewRentalCardRequest:**
| Field | Type | Rules |
|---|---|---|
| `end_date` | date | required |
| `terms` | string | nullable, max:5000 |
| `notes` | string | nullable, max:5000 |

**RentalCardResource fields:**
| Field | Type | Description |
|---|---|---|
| `id` | int | |
| `property_id` | int | |
| `owner_id` | int | |
| `tenant.type` | string | "registered" or "external" |
| `tenant.user_id` | int\|null | |
| `tenant.name` | string | |
| `tenant.phone` | string\|null | |
| `tenant.email` | string\|null | |
| `tenant.id_notes` | string\|null | |
| `start_date` | string (Y-m-d) | |
| `end_date` | string (Y-m-d) | |
| `days_remaining` | int | |
| `terms` | string\|null | |
| `notes` | string\|null | |
| `is_renewable` | bool | |
| `status` | string | active/ended/cancelled/renewed |
| `status_label` | string | Active/Ended/Cancelled/Renewed |
| `is_active` | bool | |
| `renewal_count` | int | |
| `ended_at` | string\|null | |
| `end_reason` | string\|null | |
| `pre_rental_photos` | array | `[{ id, name, file_name, mime_type, size, url, thumb_url, medium_url }]` |
| `pre_rental_photos_count` | int | |
| `property` | object\|null | PropertyResource |
| `owner` | object\|null | UserResource |
| `tenantUser` | object\|null | UserResource |
| `endedBy` | object\|null | UserResource |

### 6.10 Reviews

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/dashboard/offices/{officeId}/reviews` | `reviews.list` | List office reviews |
| `POST` | `/api/reviews` | `reviews.create` | Create review (201) |
| `DELETE` | `/api/reviews/{id}` | `reviews.delete` | Delete (own only) |

**Review body (no FormRequest):** `reviewed_id` (int), `rating` (int), `comment` (string), `property_id` (int, optional)

**ReviewResource:** `id`, `reviewed_id`, `reviewer_id`, `property_id`, `rating`, `comment`, `created_at`, `updated_at`, `reviewer` (UserResource, if loaded), `property` (PropertyResource, if loaded)

---

## 7. COMMUNICATION MODULE

### 7.1 Chat Rooms

All require `auth:sanctum` + throttle 60/min.

| Method | URL | Description |
|---|---|---|
| `GET` | `/api/chat/rooms` | List user's rooms (paginated) |
| `POST` | `/api/chat/rooms` | Create room (201) |
| `GET` | `/api/chat/rooms/{roomId}` | Show room |
| `GET` | `/api/chat/rooms/{roomId}/messages` | List messages (paginated, 20/page) |
| `POST` | `/api/chat/rooms/{roomId}/messages` | Send message (201) |
| `DELETE` | `/api/chat/rooms/{roomId}/messages/{messageId}` | Delete message (soft, sender-only) |
| `POST` | `/api/chat/rooms/{roomId}/typing` | Typing indicator (throttle 1/min) |

**StoreChatRoomRequest:**
| Field | Type | Required | Rules |
|---|---|---|---|
| `type` | string | Yes | in:private,property |
| `recipient_id` | int | cond (type=private) | integer |
| `property_id` | int | cond (type=property) | exists:properties,id |

**StoreMessageRequest:**
| Field | Type | Required | Rules |
|---|---|---|---|
| `body` | string | cond (type=text) | max:5000 |
| `type` | string | No | in:text,image,file (default:text) |
| `parent_id` | int | No | exists:messages,id |
| `attachment` | file | No | max:20480 (20MB) |

**ChatRoomResource:** `id`, `type` (private/group), `name`, `property_id`, `created_at`, `updated_at`, `participants[]` (if loaded)

**MessageResource:** `id`, `room_id`, `body`, `type` (text/image/file), `is_mine` (bool), `is_read` (bool), `attachment_url`, `parent_message` (if loaded), `created_at`, `read_at`, `updated_at`, `sender` (UserResource), `parent` (MessageResource)

### 7.2 Notifications

| Method | URL | Description |
|---|---|---|
| `GET` | `/api/notifications` | List notifications (paginated, 15/page, unread first) |
| `GET` | `/api/notifications/unread-count` | Unread count |
| `PATCH` | `/api/notifications/{id}/read` | Mark one as read |
| `PATCH` | `/api/notifications/read-all` | Mark all as read |
| `DELETE` | `/api/notifications/{id}` | Delete notification |

**NotificationResource:** `id` (UUID), `type` (NotificationTypeEnum), `type_label` (Arabic), `title`, `body`, `data` (array), `read_at`, `is_read`, `created_at`, `updated_at`

### 7.3 FCM Tokens

| Method | URL | Description |
|---|---|---|
| `POST` | `/api/fcm/register` | Register FCM token |
| `DELETE` | `/api/fcm/revoke` | Revoke token(s) |

**RegisterFcmTokenRequest:**
| Field | Type | Rules |
|---|---|---|
| `token` | string | nullable, string |
| `device_type` | string | nullable, in:android,ios,web |

**Behavior on revoke:** If `token` provided → revoke specific. If only `device_type` → revoke all for that device. If neither → revoke all.

### 7.4 Pusher Channels (WebSocket)

Broadcast channels for real-time:
- `user.{userId}` — personal events
- `chat.{roomId}` — chat messages + typing events
- `property.{propertyId}` — property updates

---

## 8. SUBSCRIPTION MODULE

### 8.1 Public Routes (no auth)

| Method | URL | Description |
|---|---|---|
| `GET` | `/api/plans` | List public plans |
| `GET` | `/api/plans/{id}` | Show plan detail |
| `POST` | `/api/coupons/validate` | Validate coupon code |

**ValidateCouponRequest:**
| Field | Type | Rules |
|---|---|---|
| `code` | string | required, max:255 |
| `plan_id` | int | nullable, exists:subscription_plans,id |

**Response:** `{ valid: bool, discount: {...}, pricing: { original_price, discount_amount, final_price, discount_type, discount_value } }`

**PublicPlanResource:** `id`, `name`, `slug`, `description`, `price` (decimal string), `currency`, `duration_days`, `features[]` (SubscriptionFeatureResource[] with pivot when loaded)

### 8.2 User Subscription Routes (auth:sanctum)

| Method | URL | Description |
|---|---|---|
| `POST` | `/api/checkout` | Create checkout session |
| `GET` | `/api/subscription/current` | Current active subscription |
| `GET` | `/api/subscription/history` | Subscription history (paginated) |
| `POST` | `/api/subscription/cancel` | Cancel subscription |
| `GET` | `/api/subscription/features` | All feature access |
| `GET` | `/api/subscription/features/{slug}` | Check single feature |
| `GET` | `/api/subscription/{id}/status-logs` | Status logs (paginated) |

**CheckoutRequest:**
| Field | Type | Rules |
|---|---|---|
| `plan_id` | int | required, exists:subscription_plans,id |
| `coupon_code` | string | nullable, max:255 |
| `payment_method` | string | default:stripe, in:stripe,balance |

**CheckoutResource:** `id`, `checkout_session_id`, `checkout_url` (Stripe), `subscription_id`, `plan` (SubscriptionPlanResource, if loaded), `pricing` (object), `discount` (SubscriptionDiscountResource, if loaded)

**ActiveSubscriptionResource:** `id`, `status`, `starts_at`, `ends_at`, `days_remaining`, `currency`, `plan` (if loaded), `features[]` (array of feature access objects)

**SubscriptionResource:** `id`, `user_id`, `plan_id`, `discount_id`, `status`, `starts_at`, `ends_at`, `cancelled_at`, `currency`, `plan` (always loaded), `discount` (if loaded)

**Feature Access response:** `{ "feature-slug": { name, slug, type, is_enabled, limit_value }, ... }`

**Check Feature response:** `{ has_access: bool, reason: string|null, feature: string|null, value: bool|int|null }`

### 8.3 Admin Subscription Routes (auth:sanctum)

#### Plans

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/admin/subscription/plans` | `subscription_plans.list` | List |
| `POST` | `/api/admin/subscription/plans` | `subscription_plans.create` | Create (201) |
| `GET` | `/api/admin/subscription/plans/{id}` | `subscription_plans.show` | Show |
| `PATCH` | `/api/admin/subscription/plans/{id}` | `subscription_plans.edit` | Update |
| `DELETE` | `/api/admin/subscription/plans/{id}` | `subscription_plans.delete` | Delete (soft) |
| `POST` | `/api/admin/subscription/plans/{id}/features` | `subscription_plans.edit` | Sync features |

**StorePlanRequest:**
| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max:255 |
| `slug` | string | required, max:255, unique |
| `description` | string | nullable, max:1000 |
| `price` | numeric | required, min:0 |
| `currency` | string | nullable, max:3 |
| `duration_days` | int | required, min:1 |
| `is_active` | bool | nullable |
| `sort_order` | int | nullable, min:0 |

**SyncPlanFeaturesRequest:**
| Field | Type | Rules |
|---|---|---|
| `features` | array | required |
| `features.*.feature_id` | int | required, exists:subscription_features,id |
| `features.*.is_enabled` | bool | nullable |
| `features.*.limit_value` | int | nullable, min:0 |

**SubscriptionPlanResource:** `id`, `name`, `slug`, `description`, `price`, `currency`, `duration_days`, `is_active`, `sort_order`, `deleted_at`, `features[]` (if loaded with pivot), `created_at`, `updated_at`

#### Features

| Method | URL | Permission |
|---|---|---|
| `GET` | `/api/admin/subscription/features` | `subscription_features.list` |
| `POST` | `/api/admin/subscription/features` | `subscription_features.create` |
| `GET` | `/api/admin/subscription/features/{id}` | `subscription_features.show` |
| `PATCH` | `/api/admin/subscription/features/{id}` | `subscription_features.edit` |
| `DELETE` | `/api/admin/subscription/features/{id}` | `subscription_features.delete` |

**StoreFeatureRequest:**
| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max:255 |
| `slug` | string | required, max:255, unique |
| `type` | string | required, in:toggle,limit |
| `description` | string | nullable, max:1000 |

**SubscriptionFeatureResource:** `id`, `name`, `slug`, `type` (toggle/limit), `description`, `pivot` (if loaded — `{ is_enabled, limit_value }`), `created_at`, `updated_at`

#### Plan Features (Pivot)

| Method | URL | Permission |
|---|---|---|
| `POST` | `/api/admin/subscription/plan-features` | `subscription_plan_features.create` |
| `PATCH` | `/api/admin/subscription/plan-features/{id}` | `subscription_plan_features.edit` |
| `DELETE` | `/api/admin/subscription/plan-features/{id}` | `subscription_plan_features.delete` |

**StorePlanFeatureRequest:**
| Field | Type | Rules |
|---|---|---|
| `plan_id` | int | required, exists:subscription_plans,id |
| `feature_id` | int | required, exists:subscription_features,id |
| `is_enabled` | bool | nullable |
| `limit_value` | int | nullable, min:0 |

**SubscriptionPlanFeatureResource:** `id`, `plan_id`, `feature_id`, `is_enabled`, `limit_value`, `plan` (if loaded), `feature` (if loaded), `created_at`, `updated_at`

#### Discounts

| Method | URL | Permission |
|---|---|---|
| `GET` | `/api/admin/subscription/discounts` | `subscription_discounts.list` |
| `POST` | `/api/admin/subscription/discounts` | `subscription_discounts.create` |
| `GET` | `/api/admin/subscription/discounts/{id}` | `subscription_discounts.show` |
| `PATCH` | `/api/admin/subscription/discounts/{id}` | `subscription_discounts.edit` |
| `DELETE` | `/api/admin/subscription/discounts/{id}` | `subscription_discounts.delete` |

**StoreDiscountRequest:**
| Field | Type | Rules |
|---|---|---|
| `code` | string | required, max:255, unique |
| `type` | string | required, in:percentage,fixed |
| `value` | numeric | required, min:0 |
| `plan_id` | int | nullable, exists:subscription_plans,id |
| `max_uses` | int | nullable, min:1 |
| `expires_at` | date | nullable, after:now |
| `is_active` | bool | nullable |

**SubscriptionDiscountResource:** `id`, `code`, `type` (percentage/fixed), `value`, `plan_id`, `max_uses`, `used_count`, `expires_at`, `is_active`, `is_valid`, `plan` (if loaded), `created_at`, `updated_at`

### 8.4 Stripe Webhook

`POST /api/stripe/webhook` — public (no auth, Stripe signature verified)

---

## 9. CRM MODULE

All routes prefixed `/api/dashboard/crm`, all require `auth:sanctum` + `trader` role.

### 9.1 Dashboard

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/dashboard/crm/dashboard/summary` | `crm_dashboard.view` | Stats summary (cached 5 min) |
| `GET` | `/api/dashboard/crm/dashboard/today` | `crm_dashboard.view` | Today's + overdue follow-ups (cached 5 min) |

**Summary response:** `{ new_leads_this_week, today_follow_ups, overdue_follow_ups, leads_by_status: { new, contacted, qualified, won, lost } }`

**Today response:** `{ today_appointments: AppointmentResource[], overdue_appointments: AppointmentResource[] }`

### 9.2 Leads

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/dashboard/crm/leads` | `leads.list` | List (paginated, own only) |
| `GET` | `/api/dashboard/crm/leads/{id}` | `leads.show` | Show (own only) |
| `POST` | `/api/dashboard/crm/leads` | `leads.create` | Create (201) |
| `PATCH` | `/api/dashboard/crm/leads/{id}` | `leads.edit` | Update |
| `PATCH` | `/api/dashboard/crm/leads/{id}/status` | `leads.change-status` | Change status |
| `POST` | `/api/dashboard/crm/leads/{id}/archive` | `leads.archive` | Archive |
| `POST` | `/api/dashboard/crm/leads/{id}/restore` | `leads.restore` | Restore (within 30 days) |
| `GET` | `/api/dashboard/crm/leads/archived` | `leads.list` | Archived list |
| `GET` | `/api/dashboard/crm/leads/check-duplicate` | `leads.list` | Check duplicate phone |
| `GET` | `/api/dashboard/crm/leads/export` | `leads.export` | Export CSV |

**Query params (index/archived/export):** `?search=`, `?status=new|contacted|qualified|won|lost`, `?source=website|whatsapp|referral|walk_in|phone|other`, `?trader_id=`, `?id[]=`, date ranges on `created_at`, `status_changed_at`, `last_activity_at`

**StoreLeadRequest:**
| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max:120 |
| `phone` | string | required, max:32 |
| `email` | string | nullable, email, max:120 |
| `source` | string | required, in:website,whatsapp,referral,walk_in,phone,other |

**UpdateLeadRequest:** Same fields, all `sometimes`.

**ChangeLeadStatusRequest:**
| Field | Type | Rules |
|---|---|---|
| `status` | string | required, in:new,contacted,qualified,won,lost |
| `lost_reason` | string | required_if:status=lost, max:500 |

**Check duplicate query:** `?phone=`

**LeadResource:** `id`, `name`, `phone`, `email`, `source`, `status`, `status_label`, `lost_reason`, `status_changed_at`, `archived_at`, `last_activity_at`, `created_at`, `updated_at`, `notes[]` (if loaded), `followUps[]` (AppointmentResource, if loaded), `trader` (UserResource, if loaded)

### 9.3 Lead Notes

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/dashboard/crm/leads/{leadId}/notes` | `lead_notes.list` | List notes |
| `POST` | `/api/dashboard/crm/leads/{leadId}/notes` | `lead_notes.create` | Create note (201) |
| `PATCH` | `/api/dashboard/crm/notes/{noteId}` | `lead_notes.edit` | Update note |
| `DELETE` | `/api/dashboard/crm/notes/{noteId}` | `lead_notes.delete` | Delete note |

**StoreLeadNoteRequest:**
| Field | Type | Rules |
|---|---|---|
| `body` | string | required, min:1, max:5000 |

**UpdateLeadNoteRequest:** Same, all `sometimes`.

**LeadNoteResource:** `id`, `lead_id`, `body`, `is_locked`, `is_editable` (author + < 24h), `is_deletable`, `author` ({ id, name }), `created_at`, `updated_at`

---

## 10. SERVICEPROVIDER MODULE

### 10.1 Public Directory

| Method | URL | Auth | Description |
|---|---|---|---|
| `GET` | `/api/public/service-providers` | — | List providers |
| `GET` | `/api/public/service-providers/{id}` | — | Show provider |

**Query params (public index):** `?type=photographer|lawyer|inspector|marketer|other`, `?city_id=`, `?search=`, `?sort_by=average_rating`, `?sort_order=desc`

**ServiceProviderResource:** `id`, `user_id`, `type`, `bio`, `experience_years`, `license_number`, `price_type`, `price_per_task`, `is_verified`, `is_available`, `average_rating`, `total_completed_tasks`, `license_document_url`, `reviews_count`, `metadata`, `user` (UserResource), `coverageAreas[]` (ServiceProviderCoverageAreaResource), `media[]` (TemporaryFileResource), `created_at`, `updated_at`

**ServiceProviderCoverageAreaResource:** `id`, `city_id`, `city` (CityResource, if loaded), `created_at`, `updated_at`

### 10.2 Provider Self-Service (auth:sanctum)

| Method | URL | Permission | Description |
|---|---|---|---|
| `POST` | `/api/service-provider/register` | — | Register as provider |
| `GET` | `/api/service-provider/profile` | — | My profile |
| `PUT` | `/api/service-provider/profile` | — | Update profile |
| `POST` | `/api/service-provider/availability` | — | Toggle availability |

**StoreServiceProviderProfileRequest:**
| Field | Type | Rules |
|---|---|---|
| `type` | string | required, in:photographer,lawyer,inspector,marketer,other |
| `bio` | string | nullable, max:1000 |
| `experience_years` | int | nullable, min:0, max:50 |
| `license_number` | string | nullable, max:100 |
| `price_type` | string | nullable, in:fixed,hourly,negotiable |
| `price_per_task` | numeric | nullable, min:0 |
| `coverage_city_ids` | array | nullable, array |
| `coverage_city_ids.*` | int | exists:cities,id |
| `license_document` | array | nullable |
| `metadata` | array | nullable |

**UpdateServiceProviderProfileRequest:** Same fields, all nullable.

### 10.3 Provider Request Management

| Method | URL | Description |
|---|---|---|
| `GET` | `/api/service-provider/service-requests` | My requests |
| `GET` | `/api/service-provider/service-requests/{id}` | Show request |
| `POST` | `/api/service-provider/service-requests/{id}/accept` | Accept |
| `POST` | `/api/service-provider/service-requests/{id}/reject` | Reject |
| `POST` | `/api/service-provider/service-requests/{id}/start` | Start progress |
| `POST` | `/api/service-provider/service-requests/{id}/complete` | Complete |
| `POST` | `/api/service-provider/service-request-tasks/{taskId}/complete` | Complete task |

**ServiceRequestResource:** `id`, `client_id`, `provider_id`, `property_id`, `service_type`, `status`, `scheduled_at`, `completed_at`, `cancelled_at`, `client_notes`, `provider_notes`, `admin_notes`, `price`, `platform_fee`, `provider_earnings`, `is_paid`, `is_provider_paid`, `client` (UserResource), `provider` (ServiceProviderResource), `property` (PropertyResource), `tasks[]` (ServiceRequestTaskResource), `created_at`, `updated_at`

**ServiceRequestTaskResource:** `id`, `service_request_id`, `task_type` (photo_upload/checklist/report/verify), `checklist_json`, `completed_at`, `created_at`, `updated_at`

### 10.4 Provider Billing

| Method | URL | Description |
|---|---|---|
| `GET` | `/api/service-provider/balance` | Balance info |
| `GET` | `/api/service-provider/earnings` | Earnings history (paginated) |
| `POST` | `/api/service-provider/withdraw` | Withdraw funds |

**Balance response:** `{ current_balance, available_balance, total_earnings }`

**Withdraw:** `?amount=100.00` (float, must ≤ available_balance)

### 10.5 Client Service Requests

| Method | URL | Description |
|---|---|---|
| `GET` | `/api/service-requests` | My requests |
| `POST` | `/api/service-requests` | Create request |
| `GET` | `/api/service-requests/{id}` | Show request |
| `POST` | `/api/service-requests/{id}/cancel` | Cancel |

**CreateServiceRequestRequest:**
| Field | Type | Rules |
|---|---|---|
| `service_type` | string | required, in:photography,inspection,legal,marketing |
| `provider_id` | int | nullable, exists:service_provider_profiles,id |
| `property_id` | int | nullable, exists:properties,id |
| `scheduled_at` | date | nullable |
| `client_notes` | string | nullable, max:2000 |
| `price` | numeric | nullable, min:0 |

### 10.6 Admin Routes

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/admin/service-providers` | — | List all providers |
| `POST` | `/api/admin/service-providers/{id}/verify` | `service_providers.verify` | Verify |
| `POST` | `/api/admin/service-providers/{id}/unverify` | `service_providers.unverify` | Unverify |
| `GET` | `/api/admin/service-requests` | `service_requests.list` | List all requests |
| `GET` | `/api/admin/service-requests/{id}` | `service_requests.show` + `service_requests.manage` | Show request |

---

## 11. FILESYSTEM MODULE

All routes prefixed `/api`, all require `auth:sanctum`.

### 11.1 Folders

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/folders` | — | List root/child folders |
| `POST` | `/api/folders` | `files.create` | Create folder (201) |
| `GET` | `/api/folders/{id}` | — | Show folder contents |
| `PUT` | `/api/folders/{id}` | `files.edit` | Update folder |
| `DELETE` | `/api/folders/{id}` | — (service-enforced) | Delete |
| `POST` | `/api/folders/{id}/move` | `files.move` | Move |
| `POST` | `/api/folders/{id}/rename` | `files.rename` | Rename |
| `GET` | `/api/properties/{propertyId}/files` | — | Property files folder |

**Query params (index):** `?parent_id=` (null = root)

**CreateFolderRequest:** `name` (required, max:255), `parent_id` (nullable, exists where user_id=self)

**UpdateFolderRequest:** `name` (required, max:255)

**MoveItemRequest:** `target_folder_id` (required, exists where user_id=self)

**RenameItemRequest:** `name` (required, max:255)

**Folder show response:** `{ folder: FolderResource, children: FolderResource[], files: FileResource[], breadcrumbs: [...], storage: {...} }`

**FolderResource:** `id`, `name`, `folder_type`, `is_protected`, `parent_id`, `can_move`, `can_delete`, `can_rename`, `children_count`, `files_count`, `children[]` (if loaded), `parent` (if loaded), `user` (if loaded), `created_at`, `updated_at`

### 11.2 Files

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/files/{id}` | — | Show file |
| `POST` | `/api/files/text` | `files.create` | Create text file (201) |
| `PUT` | `/api/files/{id}/text` | `files.edit` | Update text file |
| `POST` | `/api/files/image` | `files.create` | Upload image (201) |
| `DELETE` | `/api/files/{id}` | — (service-enforced) | Delete |
| `POST` | `/api/files/{id}/move` | `files.move` | Move |
| `POST` | `/api/files/{id}/rename` | `files.rename` | Rename |

**CreateTextFileRequest:** `folder_id` (required, exists where user_id=self), `name` (required, max:255), `content` (nullable, max:5MB)

**UpdateTextFileRequest:** `name` (nullable, max:255), `content` (nullable, max:5MB)

**UploadImageRequest:** `folder_id` (required, exists where user_id=self), `image` (required, file, mimes:jpg,jpeg,png,webp,gif, max:10MB), `name` (nullable, max:255)

**FileResource:** `id`, `name`, `file_type` (text/image), `mime_type`, `size` (bytes), `size_readable`, `content` (text only), `preview_url` (image only), `download_url` (image only), `folder_id`, `folder` (if loaded), `user` (if loaded), `created_at`, `updated_at`

**Image upload response:** `{ file: FileResource, storage: {...} }`

### 11.3 Storage Quota

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/storage/status` | — | Storage status |
| `GET` | `/api/storage/packages` | — | Available packages |
| `POST` | `/api/storage/upgrade` | `files.quota` | Upgrade storage |

**UpgradeStorageRequest:** `package_type` (required, in:free,small,medium,large,max)

**Status response:** `{ quota_bytes, quota_readable, used_bytes, used_readable, remaining_bytes, used_percentage, package_type, is_exceeded, is_near_limit, can_upload, available_packages }`

---

## 12. DEPOSIT MODULE

All routes prefixed `/api/dashboard`, all require `auth:sanctum`.

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/dashboard/deposits` | `deposits.list` | List/filter |
| `GET` | `/api/dashboard/deposits/{id}` | `deposits.show` | Show |
| `POST` | `/api/dashboard/deposits` | `deposits.create` | Create (201) |
| `PATCH` | `/api/dashboard/deposits/{id}` | `deposits.edit` | Update |
| `DELETE` | `/api/dashboard/deposits/{id}` | `deposits.delete` | Delete (soft) |
| `POST` | `/api/dashboard/deposits/{id}/pay` | `deposits.create` | Pay/Hold |
| `POST` | `/api/dashboard/deposits/{id}/release` | `deposits.release` | Release to seller |
| `POST` | `/api/dashboard/deposits/{id}/refund` | `deposits.refund` | Refund to buyer |
| `POST` | `/api/dashboard/deposits/{id}/cancel` | `deposits.edit` | Cancel |
| `GET` | `/api/dashboard/my-deposits` | `deposits.list` | My deposits (as buyer) |
| `GET` | `/api/dashboard/my-sales` | `deposits.list` | My sales (as seller) |

**Status Transitions:**
| Current | Allowed Actions |
|---|---|
| `pending` | `pay`, `update`, `cancel` |
| `held` | `release`, `refund` |
| `disputed` | `cancel` |
| `released`, `refunded`, `cancelled` | (terminal — none) |

**DepositFilterRequest (query):**
| Field | Type | Rules |
|---|---|---|
| `property_id` | int | nullable |
| `buyer_id` | int | nullable (super-admin only) |
| `seller_id` | int | nullable (super-admin only) |
| `status` | string | nullable, in:pending,held,released,refunded,disputed,cancelled |
| `currency` | string | nullable, max:3 |
| `search` | string | nullable, max:255 (searches reference_number, terms, notes) |
| `sort_by` | string | nullable, in:created_at,amount,held_at,released_at |
| `sort_order` | string | nullable, in:asc,desc |
| `perPage` | int | nullable, min:1, max:100 |

**StoreDepositRequest:**
| Field | Type | Required | Rules |
|---|---|---|---|
| `property_id` | int | Yes | exists:properties,id |
| `seller_id` | int | Yes | exists:users,id |
| `amount` | numeric | Yes | min:0.01 |
| `currency` | string | No | max:3, in:SAR,USD; default:SAR |
| `terms` | string | No | max:5000 |
| `notes` | string | No | max:5000 |

**UpdateDepositRequest:** `amount`, `terms`, `notes` (all optional)

**PayDepositRequest:** `payment_method` (required, in:balance,stripe)

**ReleaseDepositRequest:** `notes` (nullable, max:5000)

**RefundDepositRequest:** `notes` (nullable, max:5000)

**CancelDepositRequest:** `reason` (required, max:1000)

**DepositResource:** `id`, `reference_number`, `property_id`, `buyer_id`, `seller_id`, `amount`, `currency`, `status`, `status_label`, `terms`, `notes`, `held_at`, `released_at`, `refunded_at`, `cancelled_at`, `cancelled_by`, `released_by`, `refunded_by`, `cancellation_reason`, `release_notes`, `refund_notes`, `property` (loaded), `buyer` (loaded), `seller` (loaded), `cancelledBy` (loaded), `releasedBy` (loaded), `refundedBy` (loaded), `created_at`, `updated_at`

---

## 13. LEDGER MODULE

All routes prefixed `/api`, all require `auth:sanctum`.

### 13.1 Ledger (My Balance)

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/ledger/balance` | — | Current user balance |
| `GET` | `/api/ledger/statement` | — | Statement (paginated) |

**Balance response:** `{ account: AccountResource|null, current_balance, held_balance, available_balance, currency }`

### 13.2 Accounts

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/accounts/tree` | — | Chart of accounts tree |
| `GET` | `/api/accounts` | `accounts.list` | List |
| `POST` | `/api/accounts` | `accounts.create` | Create (201) |
| `GET` | `/api/accounts/{account}` | `accounts.show` | Show |
| `PUT` | `/api/accounts/{account}` | `accounts.edit` | Update |
| `DELETE` | `/api/accounts/{account}` | `accounts.delete` | Delete |

**StoreAccountRequest:**
| Field | Type | Required | Rules |
|---|---|---|---|
| `code` | string | Yes | max:100, unique |
| `name` | string | Yes | max:255 |
| `account_number` | string | No | max:50, unique |
| `account_category` | string | Yes | in:asset,liability,equity,revenue,expense |
| `description` | string | No | max:1000 |
| `currency` | string | No | max:3 |
| `parent_id` | int | No | exists:ledger_accounts,id |
| `sort_order` | int | No | min:0 |
| `is_active` | bool | No | — |

**AccountResource:** `id`, `code`, `name`, `account_number`, `account_category`, `account_category_label`, `account_category_label_ar`, `normal_balance`, `type`, `type_label`, `description`, `currency`, `parent_id`, `sort_order`, `current_balance`, `held_balance`, `available_balance`, `is_active`, `children[]` (tree-only), `parent` (if loaded), `entries[]` (if loaded), `created_at`, `updated_at`

**AccountEntryResource:** `id`, `account_id`, `batch_id`, `entry_type` (debit/credit), `amount`, `currency`, `balance_after`, `reference_type`, `reference_id`, `description`, `idempotency_key`, `metadata`, `posted_at`, `account` (if loaded), `created_at`

### 13.3 Journal Entries

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/journal-entries` | `journal_entries.list` | List |
| `POST` | `/api/journal-entries` | `journal_entries.create` | Create (201) |
| `GET` | `/api/journal-entries/{journalEntry}` | `journal_entries.show` | Show |
| `PUT` | `/api/journal-entries/{journalEntry}` | `journal_entries.edit` | Update |
| `DELETE` | `/api/journal-entries/{journalEntry}` | `journal_entries.delete` | Delete |
| `POST` | `/api/journal-entries/{journalEntry}/post` | `journal_entries.post` | Post (finalize) |
| `GET` | `/api/trial-balance` | `trial_balance.view` | Trial balance |

**StoreJournalEntryRequest:**
| Field | Type | Required | Rules |
|---|---|---|---|
| `entry_date` | date | Yes | date |
| `description` | string | No | max:1000 |
| `reference` | string | No | max:100 |
| `notes` | string | No | max:2000 |
| `lines` | array | Yes | min:2 |
| `lines[].account_id` | int | Yes | exists:ledger_accounts,id |
| `lines[].debit_amount` | float | No | min:0 |
| `lines[].credit_amount` | float | No | min:0 |
| `lines[].description` | string | No | max:500 |

**Trial balance query:** `?date_from=` (required), `?date_to=`

**JournalEntryResource:** `id`, `journal_number`, `entry_date` (Y-m-d), `description`, `reference`, `status` (draft/posted/reversed), `status_label`, `notes`, `total_debit`, `total_credit`, `is_balanced`, `posted_at`, `lines[]` (JournalEntryLineResource, if loaded), `createdBy` (UserResource), `postedBy` (UserResource), `created_at`, `updated_at`

**JournalEntryLineResource:** `id`, `journal_entry_id`, `account_id`, `description`, `debit_amount`, `credit_amount`, `sort_order`, `account` (AccountResource, if loaded), `created_at`

### 13.4 Payroll

| Method | URL | Permission | Description |
|---|---|---|---|
| `GET` | `/api/payroll/providers` | `payroll.list` | List providers |
| `POST` | `/api/payroll/setup` | `payroll.manage` | Setup payroll (201) |
| `GET` | `/api/payroll/{payroll}` | `payroll.list` | Show payroll |
| `PUT` | `/api/payroll/{payroll}` | `payroll.manage` | Update payroll |
| `POST` | `/api/payroll/run` | `payroll.run` | Run payroll |
| `GET` | `/api/payroll/payments` | `payroll.list` | Payment history |

**SetupPayrollRequest:**
| Field | Type | Required | Rules |
|---|---|---|---|
| `service_provider_profile_id` | int | Yes | exists:service_provider_profiles,id |
| `type` | string | Yes | in:monthly,per_task,both |
| `base_salary` | float | No | min:0 |
| `per_task_rate` | float | No | min:0 |
| `is_active` | bool | No | — |
| `start_date` | date | No | — |
| `end_date` | date | No | after:start_date |
| `notes` | string | No | max:1000 |

**Run payroll response:** `{ summary: { total, paid, failed, skipped }, details: [...] }`

**PayrollResource:** `id`, `service_provider_profile_id`, `type`, `type_label`, `base_salary`, `per_task_rate`, `is_active`, `start_date`, `end_date`, `notes`, `serviceProviderProfile` (if loaded), `payments[]` (if loaded), `created_at`, `updated_at`

**PayrollPaymentResource:** `id`, `payroll_id`, `service_provider_profile_id`, `amount`, `salary_amount`, `tasks_count`, `tasks_amount`, `period_start`, `period_end`, `status` (pending/paid/failed), `status_label`, `paid_at`, `journal_entry_id`, `notes`, `payroll` (if loaded), `journalEntry` (if loaded), `serviceProviderProfile` (if loaded), `created_at`

---

## 14. ENUMS REFERENCE

### Auth
| Enum | Values |
|---|---|
| `UserStatus` | `active`, `inactive` |
| `PublisherType` | `individual`, `office` |
| `ContactPreference` | `chat`, `external` |

### RealEstate
| Enum | Values |
|---|---|
| `PropertyStatus` | `pending`, `under_inspection`, `approved`, `rejected`, `suspended`, `sold`, `rented`, `archived`, `draft` |
| `PropertyType` | `apartment`, `house`, `villa`, `land`, `commercial`, `office`, `warehouse`, `other` |
| `TypeOfContract` | `sale`, `rent` |
| `AdStatus` | `draft`, `active`, `paused`, `archived` |
| `AdType` | `banner`, `sponsored` |
| `AdMediaType` | `image`, `video` |
| `AppointmentType` | `viewing`, `follow_up`, `general` |
| `ViewingStatus` | `pending`, `confirmed`, `rescheduled`, `cancelled`, `completed`, `no_show` |
| `ViewingType` | `in_person`, `virtual`, `open_house` |
| `ContactMethod` | `call`, `whatsapp`, `visit`, `email` |
| `PricingTier` | `basic`, `standard`, `premium` |
| `SponsorDuration` | `7_days`, `14_days`, `30_days` |
| `RentalCardStatus` | `active`, `ended`, `cancelled`, `renewed` |

### Communication
| Enum | Values |
|---|---|
| `RoomTypeEnum` | `private`, `group` |
| `MessageTypeEnum` | `text`, `image`, `file` |
| `DeviceTypeEnum` | `android`, `ios`, `web` |
| `NotificationTypeEnum` | `new_message`, `new_offer`, `property_update`, `booking_confirmed`, `admin_alert`, `subscription_expiring` |
| `ConversationType` | `property_inquiry`, `general` |

### Subscription
| Enum | Values |
|---|---|
| `SubscriptionStatus` | `pending`, `active`, `expired`, `cancelled` |
| `DiscountType` | `percentage`, `fixed` |
| `FeatureType` | `toggle`, `limit` |

### CRM
| Enum | Values |
|---|---|
| `LeadStatus` | `new`, `contacted`, `qualified`, `won`, `lost` |
| `LeadSource` | `website`, `whatsapp`, `referral`, `walk_in`, `phone`, `other` |

### ServiceProvider
| Enum | Values |
|---|---|
| `ServiceProviderType` | `photographer`, `lawyer`, `inspector`, `marketer`, `other` |
| `ServiceType` | `photography`, `inspection`, `legal`, `marketing` |
| `ServiceRequestStatus` | `pending`, `accepted`, `in_progress`, `completed`, `cancelled`, `rejected` |
| `PricingType` | `fixed`, `hourly`, `negotiable` |
| `ServiceTaskType` | `photo_upload`, `checklist`, `report`, `verify` |

### FileSystem
| Enum | Values |
|---|---|
| `FileType` | `text`, `image` |
| `StoragePackageType` | `free` (100MB), `small` (500MB), `medium` (1GB), `large` (3GB), `max` (5GB) |

### Deposit
| Enum | Values |
|---|---|
| `DepositStatus` | `pending`, `held`, `released`, `refunded`, `disputed`, `cancelled` |

### Ledger
| Enum | Values |
|---|---|
| `AccountType` | `user_balance`, `revenue`, `clearing`, `platform_fee`, `liability`, `expense` |
| `AccountCategory` | `asset`, `liability`, `equity`, `revenue`, `expense` |
| `EntryType` | `debit`, `credit` |
| `JournalEntryStatus` | `draft`, `posted`, `reversed` |
| `PayrollType` | `monthly`, `per_task`, `both` |
| `PayrollPaymentStatus` | `pending`, `paid`, `failed` |
| `AccountEntry reference_type` | `ad_payment`, `subscription_payment`, `wallet_topup`, `journal_entry`, `service_payment`, `service_earning`, `service_commission` |

