# Real Estate API — Endpoints Documentation

> Generated: 2026-06-05 | Total endpoints: **80+**

---

## Table of Contents

1. [Auth / Fortify](#auth--fortify)
2. [Auth Module](#auth-module)
3. [Communication Module](#communication-module)
4. [Core Module](#core-module)
5. [RealEstate Module](#realestate-module)

---

## Auth / Fortify

All endpoints prefixed with `/api/auth`. Fortify default routes disabled, custom response contracts return JSON via `ApiResponses` trait.

**Response format:** `{ success: boolean, message: string, data: ... }`

---

### POST /api/auth/register
**Auth:** No (`guest`)

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | Yes | `required`, `string`, `max:255` | Full name |
| email | string | Yes | `required`, `email`, `max:255`, `unique:users` | Email (used as login) |
| password | string | Yes | `required`, `Password::default()`, `confirmed` | Password |
| password_confirmation | string | Yes | — | Must match password |

**Response (200):**
| Field | Type | Description |
|---|---|---|
| data.user | object | Created User model |
| data.token | string | Sanctum plain-text API token |

---

### POST /api/auth/login
**Auth:** No (`guest`) — Rate limit: 5/min per email+IP

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| email | string | Yes | `required`, `string` | Email |
| password | string | Yes | `required`, `string` | Password |

If 2FA enabled → session stores `login.id`, client must call two-factor-challenge.

**Response (200):**
| Field | Type | Description |
|---|---|---|
| data.user | object | Authenticated User model |
| data.token | string | Sanctum plain-text API token |

---

### POST /api/auth/logout
**Auth:** Yes (`auth:sanctum`)

No input. Deletes all Sanctum tokens for the user.

**Response (200):** `{ success: true, message: "Successfully logged out" }`

---

### POST /api/auth/two-factor-challenge
**Auth:** No (`guest`, requires `login.id` in session) — Rate limit: 5/min

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| code | string | No* | `nullable`, `string` | 6-digit TOTP code |
| recovery_code | string | No* | `nullable`, `string` | Backup recovery code |

*At least one of `code` or `recovery_code` required.

**Response (200):**
| Field | Type | Description |
|---|---|---|
| data.user | object | Authenticated User model |
| data.token | string | Sanctum plain-text API token |

---

### POST /api/auth/forgot-password
**Auth:** No (`guest`)

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| email | string | Yes | `required`, `email` | Email to send reset link |

**Response (200):** `{ message: "We have emailed your password reset link." }`
**Response (422):** `{ success: false, errors: { email: [...] } }`

---

### POST /api/auth/reset-password
**Auth:** No (`guest`)

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| token | string | Yes | `required` | Reset token from email |
| email | string | Yes | `required`, `email` | Email address |
| password | string | Yes | `required`, `Password::default()`, `confirmed` | New password |
| password_confirmation | string | Yes | — | Must match password |

**Response (200):** `{ success: true, message: "Password reset successfully" }`

---

### GET /api/auth/email/verify/{id}/{hash}
**Auth:** No — Requires valid signed URL (`expires`, `signature` query params)

| Field | Type | Required | Description |
|---|---|---|---|
| id | integer | Yes (path) | User ID |
| hash | string | Yes (path) | SHA-1 hash of email |
| expires | integer | Yes (query) | Expiration timestamp |
| signature | string | Yes (query) | HMAC signature |

**Response (200):** `{ success: true, message: "Email verified successfully" }`

---

### POST /api/auth/email/verification-notification
**Auth:** Yes (`auth:sanctum`) — Throttle: 6/min

No input. Resends verification email. **Response:** 202 (sent), 204 (already verified)

---

### PUT /api/auth/user/profile-information
**Auth:** Yes (`auth:sanctum`)

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | Yes | `required`, `string`, `max:255` | Full name |
| email | string | Yes | `required`, `email`, `max:255`, `unique:users,email,{id}` | Email |

**Response (200):** `{ success: true, message: "Profile updated successfully", data: { user: {...} } }`

---

### PUT /api/auth/user/password
**Auth:** Yes (`auth:sanctum`)

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| current_password | string | Yes | `required`, `current_password:web` | Current password |
| password | string | Yes | `required`, `Password::default()`, `confirmed` | New password |
| password_confirmation | string | Yes | — | Must match password |

**Response (200):** `{ success: true, message: "Password updated successfully" }`

---

### POST /api/auth/user/confirm-password
**Auth:** Yes (`auth:sanctum`)

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| password | string | Yes | — | Current password |

**Response:** 201 (confirmed), 422 (wrong password)

---

### POST /api/auth/user/two-factor-authentication
**Auth:** Yes (`auth:sanctum`)

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| force | boolean | No | `boolean` | Regenerates secret if already set |

**Response:** 200 (empty body). After enabling, call GET `/api/auth/user/two-factor-qr-code` and confirm with POST `/api/auth/user/confirmed-two-factor-authentication`.

---

### POST /api/auth/user/confirmed-two-factor-authentication
**Auth:** Yes (`auth:sanctum`)

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| code | string | Yes | — | 6-digit TOTP code |

**Response:** 200 (empty body) on success, 422 on invalid code.

---

### DELETE /api/auth/user/two-factor-authentication
**Auth:** Yes (`auth:sanctum`)

No input. Disables 2FA. **Response:** 200 (empty body).

---

### GET /api/auth/user/two-factor-qr-code
**Auth:** Yes (`auth:sanctum`)

No input.

**Response (200):**
| Field | Type | Description |
|---|---|---|
| svg | string | Inline SVG QR code |
| url | string | `otpauth://` URL |

---

### GET /api/auth/user/two-factor-secret-key
**Auth:** Yes (`auth:sanctum`)

No input.

**Response (200):** `{ secretKey: "..." }` — 404 if 2FA not enabled.

---

### GET /api/auth/user/two-factor-recovery-codes
**Auth:** Yes (`auth:sanctum`)

No input.

**Response (200):** JSON array of 8 recovery code strings.

---

### POST /api/auth/user/two-factor-recovery-codes
**Auth:** Yes (`auth:sanctum`)

No input. Generates fresh recovery codes. **Response:** 200 (empty body). Retrieve codes via GET endpoint.

---

## Auth Module

All endpoints under `/api/`. Auth: `auth:sanctum` + Spatie permissions.

---

### GET /api/user
**Auth:** Yes (`auth:sanctum`), **Permission:** none

No input. Returns authenticated user's model (id, name, email, status, timestamps).

---

### GET /api/roles
**Auth:** Yes, **Permission:** `roles.list`

**Query params (optional):**
| Field | Type | Default | Description |
|---|---|---|---|
| perPage | integer | 15 | Items per page |
| sort_by | string | `created_at` | Sort column |
| sort_order | string | `desc` | Sort direction |

**Response (paginated):**
| Field | Type | Description |
|---|---|---|
| data[].id | integer | Role ID |
| data[].name | string | Role name |
| data[].guard_name | string | Guard name |
| data[].created_at | string (Y-m-d H:i:s) | Created |
| data[].updated_at | string (Y-m-d H:i:s) | Updated |

---

### GET /api/roles/{id}
**Auth:** Yes, **Permission:** `roles.show`

**Response:** Same as GET roles + `permissions` array (always loaded):
| Field | Type | Description |
|---|---|---|
| permissions[].id | integer | Permission ID |
| permissions[].name | string | Permission name |

---

### POST /api/roles
**Auth:** Yes, **Permission:** `roles.create`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | Yes | `required`, `string`, `max:255`, `unique:roles,name` | Unique role name |
| guard_name | string | No | `nullable`, `string` | Default: `web` |
| permissions | array | No | `nullable`, `array` | Permission names to assign |
| permissions.* | string | No | `string`, `exists:permissions,name` | Valid permission |

**Response (201):** RoleResource with permissions loaded.

---

### PUT /api/roles/{id}
**Auth:** Yes, **Permission:** `roles.edit`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | No | `sometimes`, `string`, `max:255`, `unique:roles,name` (ignore self) | New name |
| guard_name | string | No | `nullable`, `string` | Guard name |
| permissions | array | No | `nullable`, `array` | Permission names (syncs — removes absent) |
| permissions.* | string | No | `string`, `exists:permissions,name` | Valid permission |

**Response (200):** RoleResource with permissions loaded.

---

### DELETE /api/roles/{id}
**Auth:** Yes, **Permission:** `roles.delete`

No body. **Response (200):** Empty success with `deleted('role')` message.

---

### POST /api/roles/{id}/permissions
**Auth:** Yes, **Permission:** `roles.edit`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| permissions | array | Yes | `required`, `array` | Permission names to assign |
| permissions.* | string | Yes | `string`, `exists:permissions,name` | Valid permission |

**Response (200):** RoleResource with permissions loaded. Uses `syncPermissions` (full replacement).

---

### GET /api/permissions
**Auth:** Yes, **Permission:** `roles.list`

No input. Returns all permissions (cached 86400s).

**Response:** Array of:

| Field | Type | Description |
|---|---|---|
| id | integer | Permission ID |
| name | string | Permission name (e.g. `roles.list`) |
| guard_name | string | Guard name |
| created_at | string | Created |
| updated_at | string | Updated |

---

### GET /api/users
**Auth:** Yes, **Permission:** `users.list`

**Query params (optional):**
| Field | Type | Description |
|---|---|---|
| perPage | integer | Items per page (default: 15) |
| search | string | Searches `name`, `email` (LIKE) |
| status | string | `active` / `inactive` filter |
| role_id | integer | Filter users by role ID |
| id | array/string | Multi-filter by user IDs |
| created_at[from] | string | Date range start |
| created_at[to] | string | Date range end |

**Response (paginated):**
| Field | Type | Description |
|---|---|---|
| data[].id | integer | User ID |
| data[].name | string | Display name |
| data[].email | string | Email |
| data[].status | string | `active` or `inactive` |
| data[].roles | array | RoleResource array (always loaded) |
| data[].created_at | string (Y-m-d H:i:s) | Created |
| data[].updated_at | string (Y-m-d H:i:s) | Updated |

---

### GET /api/users/{id}
**Auth:** Yes, **Permission:** `users.show`

**Response (200):** Same as GET users (single item + roles loaded).

---

### POST /api/users
**Auth:** Yes, **Permission:** `users.create`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | Yes | `required`, `string`, `max:255` | Display name |
| email | string | Yes | `required`, `email`, `unique:users,email` | Unique email |
| password | string | Yes | `required`, `string`, `min:8` | Password (hashed) |
| status | string | No | `sometimes`, `in:active,inactive` | Default: `active` |
| role_ids | array | No | `nullable`, `array` | Role IDs to assign |
| role_ids.* | integer | No | `integer`, `exists:roles,id` | Valid role ID |

**Response (201):** UserResource with roles loaded.

---

### PUT /api/users/{id}
**Auth:** Yes, **Permission:** `users.edit`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | No | `sometimes`, `string`, `max:255` | Updated name |
| email | string | No | `sometimes`, `email`, `unique:users,email` (ignore self) | Updated email |
| password | string | No | `sometimes`, `string`, `min:8` | New password (hashed if provided) |
| status | string | No | `sometimes`, `in:active,inactive` | Updated status |
| role_ids | array | No | `nullable`, `array` | Role IDs (syncs — removes absent) |
| role_ids.* | integer | No | `integer`, `exists:roles,id` | Valid role ID |

**Response (200):** UserResource with roles loaded.

---

### DELETE /api/users/{id}
**Auth:** Yes, **Permission:** `users.delete`

**Response (200):** Empty success with `deleted('user')` message.

---

### PUT /api/users/{id}/toggle-status
**Auth:** Yes, **Permission:** `users.toggle-status`

No body. Toggles user status between `active` / `inactive`. **Response:** UserResource.

---

## Communication Module

All endpoints require `auth:sanctum`. Throttle: 60/min unless noted.

---

### GET /api/chat/rooms
**Auth:** Yes, **Permission:** none

Paginated list of user's chat rooms.

**Query params:** `search`, `page`, `per_page`

**Response (paginated):**
| Field | Type | Description |
|---|---|---|
| data[].id | integer | Room ID |
| data[].type | string | `private` or `group` |
| data[].name | string | Room name |
| data[].property_id | integer\|null | Associated property |
| data[].participants | array\|null | UserResource array (when loaded) |
| data[].created_at | string (Y-m-d H:i:s) | Created |
| data[].updated_at | string (Y-m-d H:i:s) | Updated |

---

### POST /api/chat/rooms
**Auth:** Yes, **Permission:** none

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| type | string | Yes | `in:private,property` | Room type |
| recipient_id | integer | If `type=private` | `integer` | User to chat with |
| property_id | integer | If `type=property` | `integer`, `exists:properties,id` | Property to chat about |

**Response (201):** ChatRoomResource.

---

### GET /api/chat/rooms/{roomId}
**Auth:** Yes, **Permission:** none

**Response:** ChatRoomResource (same as POST).

---

### GET /api/chat/rooms/{roomId}/messages
**Auth:** Yes, **Permission:** none

Paginated (20 per page, newest first).

**Response (paginated):**
| Field | Type | Description |
|---|---|---|
| data[].id | integer | Message ID |
| data[].room_id | integer | Room ID |
| data[].body | string | Message text |
| data[].type | string | `text`, `image`, or `file` |
| data[].is_mine | boolean | Belongs to authenticated user |
| data[].is_read | boolean | Has been read |
| data[].attachment_url | string\|null | File URL |
| data[].parent_message | object\|null | Parent message (reply) |
| data[].sender | object\|null | UserResource |
| data[].created_at | string | Sent |
| data[].read_at | string\|null | Read timestamp |

---

### POST /api/chat/rooms/{roomId}/messages
**Auth:** Yes, **Permission:** none

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| body | string | If `type=text` | `string`, max:5000 | Message text |
| type | string | No | `in:text,image,file` | Default: `text` |
| parent_id | integer | No | `nullable`, `integer`, `exists:messages,id` | Reply to message |
| attachment | file | No | `file`, max:20480 KB | File attachment |

**Response (201):** MessageResource.

---

### DELETE /api/chat/rooms/{roomId}/messages/{messageId}
**Auth:** Yes, **Permission:** none

**Response (200):** `{ success: true, message: "message deleted" }`

---

### POST /api/chat/rooms/{roomId}/typing
**Auth:** Yes, **Permission:** none

No body. Broadcasts typing indicator.

**Response (200):** `{ success: true, message: "typing event broadcasted" }`

---

### GET /api/notifications
**Auth:** Yes, **Permission:** none

Paginated (15 per page), unread first.

| Field | Type | Description |
|---|---|---|
| data[].id | integer | Notification ID |
| data[].type | string | Type slug (see enum below) |
| data[].type_label | string | Arabic label |
| data[].title | string\|null | Title |
| data[].body | string\|null | Body |
| data[].data | object\|array | Payload |
| data[].is_read | boolean | Read status |
| data[].read_at | string\|null | Read timestamp |
| data[].created_at | string (Y-m-d H:i:s) | Created |

**Notification type enum:**

| Value | Arabic |
|---|---|
| new_message | رسالة جديدة |
| new_offer | عرض جديد |
| property_update | تحديث عقار |
| booking_confirmed | تأكيد الحجز |
| admin_alert | تنبيه الإدارة |

---

### GET /api/notifications/unread-count
**Auth:** Yes, **Permission:** none — Throttle: 120/min

**Response (200):** `{ data: { unread_count: <int> } }`

---

### PATCH /api/notifications/{id}/read
**Auth:** Yes, **Permission:** none

Marks single notification as read.

**Response (200):** `{ success: true, message: "notification marked as read" }`

---

### PATCH /api/notifications/read-all
**Auth:** Yes, **Permission:** none — Throttle: 10/min

Marks all unread as read.

**Response (200):** `{ success: true, message: "all notifications marked as read" }`

---

### DELETE /api/notifications/{id}
**Auth:** Yes, **Permission:** none

**Response (200):** `{ success: true, message: "notification deleted" }`

---

### POST /api/fcm/register
**Auth:** Yes, **Permission:** none

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| token | string | Yes | `string` | Firebase device token |
| device_type | string | Yes | `in:android,ios,web` | Device platform |

**Response (200):** `{ success: true, message: "FCM token registered" }`

---

### DELETE /api/fcm/revoke
**Auth:** Yes, **Permission:** none

| Field | Type | Required | Description |
|---|---|---|---|
| token | string | No | Specific token to revoke |
| device_type | string | No | Device type to scope |

None provided → revokes all tokens. **Response (200):** `{ success: true, message: "FCM token revoked" }`

---

### Enums Reference (Communication)

**MessageTypeEnum:** `text` (نص), `image` (صورة), `file` (ملف)
**RoomTypeEnum:** `private` (خاص), `group` (مجموعة)
**DeviceTypeEnum:** `android` (أندرويد), `ios` (آيفون), `web` (ويب)

---

## Core Module

### Categories

---

### GET /api/categories/public
**Auth:** No — **Note:** Controller constructor registers `categories.list` permission

**Query params:** `search` (name LIKE), `type` (`property`/`car`), `per_page`

**Response (paginated):**
| Field | Type | Description |
|---|---|---|
| data[].id | integer | Category ID |
| data[].name | string | Category name |
| data[].type | string | `property` or `car` |
| data[].created_at | string (Y-m-d H:i:s) | Created |
| data[].updated_at | string (Y-m-d H:i:s) | Updated |

---

### GET /api/categories/public/{id}
**Auth:** No — **Note:** Controller registers `categories.show`

**Response (200):** Same as public list (single item).

---

### GET /api/categories
**Auth:** Yes (`auth:sanctum`), **Permission:** `categories.list`

Same response/params as public. Protected version.

---

### GET /api/categories/{category}
**Auth:** Yes (`auth:sanctum`), **Permission:** `categories.show`

**Response (200):** Same as public/{id}.

---

### POST /api/categories
**Auth:** Yes (`auth:sanctum`), **Permission:** `categories.create`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | Yes | `string`, `max:255` | Category name |
| type | string | Yes | `string`, `in:property,car` | Category type |

**Response (201):** CategoryResource.

---

### PUT|PATCH /api/categories/{category}
**Auth:** Yes (`auth:sanctum`), **Permission:** `categories.edit`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | No | `sometimes`, `string`, `max:255` | Category name |
| type | string | No | `sometimes`, `string`, `in:property,car` | Category type |

**Response (200):** CategoryResource.

---

### DELETE /api/categories/{category}
**Auth:** Yes (`auth:sanctum`), **Permission:** `categories.delete`

Soft-deletes category. **Response (200):** Deletion confirmation.

---

### Location / Countries

---

### GET /api/location/countries
**Auth:** No — **Note:** Controller registers `countries.list`

**Query params:** `search` (name LIKE), `name`, `code`, `phone_code`, `is_active`, `per_page`

**Response (paginated):**
| Field | Type | Description |
|---|---|---|
| data[].id | integer | Country ID |
| data[].name | string | Country name |
| data[].code | string | Country code (ISO, max 3 chars) |
| data[].phone_code | string | Phone dialing code |
| data[].is_active | boolean | Active status |
| data[].cities_count | integer | Cities count (when loaded) |
| data[].cities | array | CityResource array (when loaded) |
| data[].created_at | string (Y-m-d H:i:s) | Created |
| data[].updated_at | string (Y-m-d H:i:s) | Updated |

---

### GET /api/location/countries/{country}
**Auth:** No — **Note:** Controller registers `countries.show`

**Response (200):** Same as list (single item).

---

### POST /api/location/countries
**Auth:** Yes (`auth:sanctum`), **Permission:** `countries.create`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | Yes | `string`, `max:128` | Country name |
| code | string | No | `nullable`, `string`, `max:3` | Country code |
| phone_code | string | No | `nullable`, `string`, `max:4` | Dial code |
| is_active | boolean | Yes | `boolean` | Active status |

**Response (201):** CountryResource.

---

### PUT|PATCH /api/location/countries/{country}
**Auth:** Yes (`auth:sanctum`), **Permission:** `countries.edit`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | Yes | `string`, `max:128` | Country name |
| code | string | No | `nullable`, `string`, `max:3` | Country code |
| phone_code | string | No | `nullable`, `string`, `max:4` | Dial code |
| is_active | boolean | Yes | `boolean` | Active status |

**Response (200):** CountryResource.

---

### DELETE /api/location/countries/{country}
**Auth:** Yes (`auth:sanctum`), **Permission:** `countries.delete`

**Response (200):** Deletion confirmation.

---

### Location / Cities

---

### GET /api/location/cities
**Auth:** No — **Note:** Controller registers `cities.list`

**Query params:** `search` (name LIKE), `name`, `country_id`, `state_province`, `postal_code`, `is_active`, `per_page`

**Response (paginated):**
| Field | Type | Description |
|---|---|---|
| data[].id | integer | City ID |
| data[].name | string | City name |
| data[].country_id | integer | Country ID |
| data[].state_provianc | string | State/province |
| data[].postal_code | string | Postal code |
| data[].is_active | boolean | Active status |
| data[].country | object | CountryResource (when loaded) |
| data[].created_at | string (Y-m-d H:i:s) | Created |
| data[].updated_at | string (Y-m-d H:i:s) | Updated |

---

### GET /api/location/cities/{city}
**Auth:** No — **Note:** Controller registers `cities.show`

**Response (200):** Same as list (single item).

---

### POST /api/location/cities
**Auth:** Yes (`auth:sanctum`), **Permission:** `cities.create`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | Yes | `string`, `max:128` | City name |
| country_id | integer | Yes | `exists:countries,id` | Country ID |
| state_provianc | string | No | `nullable`, `string` | State/province |
| postal_code | string | No | `nullable`, `string` | Postal code |
| is_active | boolean | No | `nullable`, `boolean` | Active |

**Response (201):** CityResource.

---

### PUT|PATCH /api/location/cities/{city}
**Auth:** Yes (`auth:sanctum`), **Permission:** `cities.edit`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | Yes | `string`, `max:128` | City name |
| country_id | integer | Yes | `exists:countries,id` | Country ID |
| state_provianc | string | No | `nullable`, `string` | State/province |
| postal_code | string | No | `nullable`, `string` | Postal code |
| is_active | boolean | No | `nullable`, `boolean` | Active |

**Response (200):** CityResource.

---

### DELETE /api/location/cities/{city}
**Auth:** Yes (`auth:sanctum`), **Permission:** `cities.delete`

**Response (200):** Deletion confirmation.

---

### Search

---

### GET /api/search/{type}
**Auth:** No

| Field | Type | Required | Description |
|---|---|---|---|
| type | string | Yes (path) | `cities`, `countries`, or `users` |

**Query params:** `search` (LIKE on `name`)

**Response (200):** Array of `{ id, name }` (max 20 items). Invalid type → empty array.

---

### Core Permissions

| Permission | Endpoints |
|---|---|
| categories.list | GET /api/categories, GET /api/categories/public |
| categories.show | GET /api/categories/{id}, GET /api/categories/public/{id} |
| categories.create | POST /api/categories |
| categories.edit | PUT\|PATCH /api/categories/{id} |
| categories.delete | DELETE /api/categories/{id} |
| countries.list | GET /api/location/countries |
| countries.show | GET /api/location/countries/{id} |
| countries.create | POST /api/location/countries |
| countries.edit | PUT\|PATCH /api/location/countries/{id} |
| countries.delete | DELETE /api/location/countries/{id} |
| cities.list | GET /api/location/cities |
| cities.show | GET /api/location/cities/{id} |
| cities.create | POST /api/location/cities |
| cities.edit | PUT\|PATCH /api/location/cities/{id} |
| cities.delete | DELETE /api/location/cities/{id} |

---

## RealEstate Module

### Public Endpoints (No Authentication)

---

### GET /api/public/ads/display/standalone
**Auth:** No

Returns standalone ads (no group).

**Response (array):**
| Field | Type | Description |
|---|---|---|
| id | integer | Ad ID |
| title | string | Ad title |
| description | string\|null | Description |
| media_type | string | `video` or `image` |
| external_url | string\|null | External link |
| media_urls | array | Media file URLs |
| property_id | integer\|null | Linked property ID |
| is_linked_to_property | boolean | Has linked property |

---

### GET /api/public/ads/display/{groupId}
**Auth:** No

Returns ads for a specific group.

| Field | Type | Required | Description |
|---|---|---|---|
| groupId | integer | Yes (path) | Ad group ID |

**Response:** Same structure as standalone.

---

### GET /api/public/ads/display
**Auth:** No

Returns all available display ads.

**Response:** Same structure as standalone.

---

### POST /api/public/ads/{id}/track/view
**Auth:** No (optional user)

Records a view event (IP, User-Agent auto-captured).

**Response (200):** `{ success: true, message: "View recorded" }`

---

### POST /api/public/ads/{id}/track/visit
**Auth:** No (optional user)

Records a click-through visit event.

**Response (200):** `{ success: true, message: "Visit recorded" }`

---

### GET /api/public/properties/browse
**Auth:** No

Browse public properties with advanced filtering.

**Query params (all optional):**
| Field | Type | Validation | Description |
|---|---|---|---|
| price_min | numeric | min:0 | Min price |
| price_max | numeric | min:0 | Max price |
| area_min | numeric | min:0 | Min area |
| area_max | numeric | min:0 | Max area |
| rooms_min | integer | min:0 | Min rooms |
| rooms_max | integer | min:0 | Max rooms |
| bathrooms_min | integer | min:0 | Min bathrooms |
| bathrooms_max | integer | min:0 | Max bathrooms |
| property_type | string | `apartment, house, villa, land, commercial, office, warehouse, other` | Type filter |
| type_of_contract | string | `sale, rent` | Contract type |
| country_id | integer | exists:countries,id | Country filter |
| city_id | integer | exists:cities,id | City filter |
| search | string | max:255 | Text search |
| sort_by | string | `price, area, rooms, bathrooms, created_at, views` | Sort column |
| sort_order | string | `asc, desc` | Sort direction |
| perPage | integer | min:1, max:100 | Items per page |

**Response (paginated):**
| Field | Type | Description |
|---|---|---|
| data[].id | integer | Property ID |
| data[].name | string | Property name |
| data[].description | string | Description |
| data[].country_id | integer | Country ID |
| data[].city_id | integer | City ID |
| data[].longitude | float\|null | Longitude |
| data[].latitude | float\|null | Latitude |
| data[].type_of_contract | string | `sale` or `rent` |
| data[].property_type | string | Type enum |
| data[].rooms | integer | Rooms |
| data[].bathrooms | integer | Bathrooms |
| data[].area | numeric | Area |
| data[].detailed_info | string\|null | Details |
| data[].price | numeric | Price |
| data[].currency | string | 3-letter code (default: USD) |
| data[].formatted_price | string | Formatted price |
| data[].status | string | Property status |
| data[].is_loved | boolean | User favorite |
| data[].views | integer | View count |
| data[].main_image | string\|null | Main image URL |
| data[].main_image_thumb | string\|null | Thumbnail URL |
| data[].gallery | array | Gallery image URLs |
| data[].publisher_id | integer | Publisher user ID |
| data[].approved_by | integer\|null | Approver ID |
| data[].approved_at | string\|null | Approved date |
| data[].created_at | string (Y-m-d H:i:s) | Created |
| data[].updated_at | string (Y-m-d H:i:s) | Updated |

---

### GET /api/public/properties/random
**Auth:** No

Returns 10 random properties. **Response:** Array of PropertyResource.

---

### GET /api/public/properties/{id}/details
**Auth:** No

**Response (200):** Single PropertyResource.

---

### Dashboard Endpoints (auth:sanctum Required)

---

### GET /api/dashboard/properties
**Auth:** Yes, **Permission:** `properties.list`

Paginated dashboard properties (admin: all, user: own). Uses Filterable trait query params.

**Response (paginated):** PropertyResource (same fields as public browse).

---

### GET /api/dashboard/properties/statistics
**Auth:** Yes, **Permission:** `properties.list`

**Response:** `{ data: { ... } }` — aggregate statistics from PropertyService.

---

### GET /api/dashboard/properties/{id}
**Auth:** Yes, **Permission:** `properties.show`

**Response (200):** Single PropertyResource (scoped to user).

---

### POST /api/dashboard/properties
**Auth:** Yes, **Permission:** `properties.create`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | Yes | max:255 | Property name |
| description | string | Yes | — | Description |
| country_id | integer | Yes | exists:countries,id | Country |
| city_id | integer | Yes | exists:cities,id (matches country) | City |
| longitude | numeric | No | between:-180,180 | Longitude |
| latitude | numeric | No | between:-90,90 | Latitude |
| property_type | string | Yes | enum (8 values) | Type |
| type_of_contract | string | Yes | `sale, rent` | Contract type |
| rooms | integer | Yes | min:0 | Rooms |
| bathrooms | integer | Yes | min:0 | Bathrooms |
| area | numeric | Yes | min:0 | Area |
| detailed_info | string | No | — | Details |
| price | numeric | Yes | min:0 | Price |
| currency | string | No | size:3 (default: USD) | Currency code |
| main_image | array\|null | No | — | `{ id, temporary_folder }` |
| gallery | array\|null | No | — | Array of `{ id, temporary_folder }` |

**Response (201):** `{ data: PropertyResource, message: "Property created successfully" }`

---

### PATCH /api/dashboard/properties/{id}
**Auth:** Yes, **Permission:** `properties.edit`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | No | max:255 | Name |
| description | string | No | — | Description |
| country_id | integer | No | exists:countries,id | Country |
| city_id | integer | No | exists:cities,id | City |
| longitude | numeric | No | between:-180,180 | Longitude |
| latitude | numeric | No | between:-90,90 | Latitude |
| property_type | string | Yes | enum (8 values) | Type |
| type_of_contract | string | Yes | `sale, rent` | Contract type |
| rooms | integer | No | min:0 | Rooms |
| bathrooms | integer | No | min:0 | Bathrooms |
| area | numeric | No | min:0 | Area |
| detailed_info | string | No | — | Details |
| price | numeric | No | min:0 | Price |
| currency | string | No | size:3 | Currency code |
| status | string | No | `pending, approved, rejected, suspended, sold, archived` | Status (admin only) |
| main_image | array\|null | No | — | `{ id, temporary_folder }` |
| gallery | array\|null | No | — | Array of `{ id, temporary_folder }` |

**Response (200):** `{ data: PropertyResource, message: "Property updated successfully" }`

---

### DELETE /api/dashboard/properties/{id}
**Auth:** Yes, **Permission:** `properties.delete`

**Response (200):** `{ success: true, message: "Property deleted successfully" }`

---

### PATCH /api/dashboard/properties/{id}/status
**Auth:** Yes, **Permission:** `properties.edit`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| status | string | Yes | `pending, approved, rejected, suspended, sold, archived` | New status |
| rejection_reason | string | If `status=rejected` | max:1000 | Required when rejected |

**Response (200):** `{ data: PropertyResource, message: "Property status updated successfully" }`

---

### POST /api/dashboard/properties/{id}/favorite
**Auth:** Yes, **Permission:** `properties.list`

No body. Toggles favorite (love/unlove).

**Response (200):** `{ success: true, message: "Property favorite status updated" }`

---

### GET /api/dashboard/my-properties
**Auth:** Yes, **Permission:** none

Properties owned by authenticated user. Uses Filterable trait.

**Response (paginated):** PropertyResource.

---

### GET /api/dashboard/ad-groups
**Auth:** Yes, **Permission:** none

Paginated list. Uses Filterable trait.

**Response (paginated):**
| Field | Type | Description |
|---|---|---|
| data[].id | integer | Group ID |
| data[].name | string | Group name |
| data[].description | string\|null | Description |
| data[].status | string | `active` or `inactive` |
| data[].is_archived | boolean | Archived status |
| data[].default_ad_id | integer\|null | Default ad |
| data[].ads_count | integer\|null | Ad count (when loaded) |
| data[].ads | array\|null | Ads (when loaded) |
| data[].creator | object\|null | UserResource |
| data[].created_at | string (Y-m-d H:i:s) | Created |
| data[].updated_at | string (Y-m-d H:i:s) | Updated |

---

### POST /api/dashboard/ad-groups
**Auth:** Yes, **Permission:** none

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | Yes | max:255, unique:ad_groups | Group name |
| description | string | No | max:1000 | Description |

**Response (201):** `{ data: AdGroupResource, message: "Ad group created successfully" }`

---

### GET /api/dashboard/ad-groups/{id}
**Auth:** Yes, **Permission:** none

**Response (200):** AdGroupResource with creator + ads (including trashed) loaded.

---

### PATCH /api/dashboard/ad-groups/{id}
**Auth:** Yes, **Permission:** none

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| name | string | No | max:255, unique:ad_groups (ignore self) | Name |
| status | string | No | `active, inactive` | Status |
| is_archived | boolean | No | — | Archive flag |

**Response (200):** `{ data: AdGroupResource, message: "Ad group updated successfully" }`

---

### DELETE /api/dashboard/ad-groups/{id}
**Auth:** Yes, **Permission:** none

Soft-deletes (archives) group.

**Response (200):** `{ success: true, message: "Ad group deleted successfully" }`

---

### POST /api/dashboard/ad-groups/{id}/restore
**Auth:** Yes, **Permission:** none

Restores archived group.

**Response (200):** `{ data: AdGroupResource, message: "Ad group restored successfully" }`

---

### POST /api/dashboard/ad-groups/{id}/set-default
**Auth:** Yes, **Permission:** none

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| ad_id | integer | Yes | exists:ads,id | Ad to set as default |
| ad_group_id | integer | Yes | — | Ad group ID |

**Response (200):** `{ data: { ad_id: <id> }, message: "Default ad set successfully" }`

---

### DELETE /api/dashboard/ad-groups/{id}/default
**Auth:** Yes, **Permission:** none

Removes default ad assignment from group.

**Response (200):** `{ success: true, message: "Default ad removed successfully" }`

---

### GET /api/dashboard/ads
**Auth:** Yes, **Permission:** `ads.list`

Paginated (admin: all, user: own). Uses Filterable trait.

**Response (paginated):**
| Field | Type | Description |
|---|---|---|
| data[].id | integer | Ad ID |
| data[].title | string | Ad title |
| data[].description | string\|null | Description |
| data[].media_type | string\|null | `video` or `image` |
| data[].external_url | string\|null | External link |
| data[].status | string\|null | `draft`, `active`, `paused`, `archived` |
| data[].is_default | boolean | Default in group |
| data[].is_linked_to_property | boolean | Property linked |
| data[].start_date | string\|null | Start date |
| data[].end_date | string\|null | End date |
| data[].view_count | integer\|null | Views |
| data[].visit_count | integer\|null | Visits |
| data[].ctr | float\|null | Click-through rate |
| data[].ad_group | object\|null | AdGroupResource |
| data[].property | object\|null | PropertyResource |
| data[].creator | object\|null | UserResource |
| data[].media | array\|null | AdMediaResource |
| data[].created_at | string (Y-m-d H:i:s) | Created |
| data[].updated_at | string (Y-m-d H:i:s) | Updated |

---

### POST /api/dashboard/ads
**Auth:** Yes, **Permission:** `ads.create`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| title | string | Yes | max:255 | Ad title |
| description | string | No | max:2000 | Description |
| ad_group_id | integer | No | exists:ad_groups,id | Group |
| media_type | string | Yes | `video, image` | Media type |
| external_url | string | No | url | External link |
| property_id | integer | No | exists:properties,id | Linked property |
| status | string | No | `draft, active, paused` | Status |
| start_date | date | No | — | Start date |
| end_date | date | No | after_or_equal:start_date | End date |
| media | array | No | — | Media files |

**Response (201):** `{ data: AdResource, message: "Ad created successfully" }`

---

### GET /api/dashboard/ads/{id}
**Auth:** Yes, **Permission:** `ads.show`

**Response (200):** AdResource with all relations loaded.

---

### PATCH /api/dashboard/ads/{id}
**Auth:** Yes, **Permission:** `ads.edit`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| title | string | No | max:255 | Ad title |
| description | string | No | max:2000 | Description |
| ad_group_id | integer | No | exists:ad_groups,id | Group |
| media_type | string | No | `video, image` | Media type |
| external_url | string | No | url | External link |
| property_id | integer | No | exists:properties,id | Linked property |
| status | string | No | `draft, active, paused` | Status |
| start_date | date | No | — | Start date |
| end_date | date | No | after_or_equal:start_date | End date |
| media | array | No | — | Media files |

**Response (200):** `{ data: AdResource, message: "Ad updated successfully" }`

---

### DELETE /api/dashboard/ads/{id}
**Auth:** Yes, **Permission:** `ads.delete`

Soft-deletes (archives) ad.

**Response (200):** `{ success: true, message: "Ad deleted successfully" }`

---

### POST /api/dashboard/ads/{id}/restore
**Auth:** Yes, **Permission:** `ads.edit`

Restores archived ad.

**Response (200):** `{ data: AdResource, message: "Ad restored successfully" }`

---

### POST /api/dashboard/ads/{id}/status
**Auth:** Yes, **Permission:** `ads.edit`

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| status | string | Yes | `draft, active, paused` | New status |

**Response (200):** `{ data: AdResource, message: "Ad status updated successfully" }`

---

### POST /api/dashboard/ads/{id}/link-property
**Auth:** Yes, **Permission:** `ads.edit`

| Field | Type | Required | Description |
|---|---|---|---|
| property_id | integer | Yes (query/body) | Property ID to link |

**Response (200):** `{ data: AdResource, message: "Ad linked to property successfully" }`

---

### DELETE /api/dashboard/ads/{id}/property
**Auth:** Yes, **Permission:** `ads.edit`

Unlinks property from ad.

**Response (200):** `{ data: AdResource, message: "Ad unlinked from property successfully" }`

---

### GET /api/dashboard/analytics/dashboard
**Auth:** Yes, **Permission:** none

| Field | Type | Required | Validation | Description |
|---|---|---|---|---|
| date_from | date | No | — | Filter from date |
| date_to | date | No | — | Filter to date |
| group_id | mixed | No | — | Filter by group |
| ad_id | mixed | No | — | Filter by ad |
| period | string | No | `day, week, month` | Aggregation period |

**Response:** `{ data: { total_views, total_visits, avg_ctr, top_performing_ads, views_by_day, visits_by_day } }`

---

### GET /api/dashboard/analytics/groups/{groupId}
**Auth:** Yes, **Permission:** none

| Field | Type | Required | Description |
|---|---|---|---|
| groupId | integer | Yes (path) | Ad group ID |

Same query params as dashboard. Ownership checked (super-admin bypass).

---

### GET /api/dashboard/analytics/ads/{adId}
**Auth:** Yes, **Permission:** none

| Field | Type | Required | Description |
|---|---|---|---|
| adId | integer | Yes (path) | Ad ID |

Same query params. **Response:** `{ data: { ad_id, title, total_views, total_visits, ctr, views_trend, visits_trend } }`

---

### GET /api/dashboard/analytics/export
**Auth:** Yes, **Permission:** none

Same query params as dashboard. **Response:** Export report data.

---

### RealEstate Enums

**PropertyStatus:** `pending`, `approved`, `rejected`, `suspended`, `sold`, `archived`
**PropertyType:** `apartment`, `house`, `villa`, `land`, `commercial`, `office`, `warehouse`, `other`
**TypeOfContract:** `sale`, `rent`
**AdStatus:** `draft`, `active`, `paused`, `archived`
**AdMediaType:** `video`, `image`
**AnalyticsPeriod:** `day`, `week`, `month`

### RealEstate Permissions

| Permission | Endpoints |
|---|---|
| properties.list | GET /api/dashboard/properties, GET statistics, POST favorite |
| properties.create | POST /api/dashboard/properties |
| properties.show | GET /api/dashboard/properties/{id} |
| properties.edit | PATCH /api/dashboard/properties/{id}, PATCH status |
| properties.delete | DELETE /api/dashboard/properties/{id} |
| ads.list | GET /api/dashboard/ads |
| ads.create | POST /api/dashboard/ads |
| ads.show | GET /api/dashboard/ads/{id} |
| ads.edit | PATCH ads/{id}, POST status, restore, link-property, DELETE property |
| ads.delete | DELETE /api/dashboard/ads/{id} |
