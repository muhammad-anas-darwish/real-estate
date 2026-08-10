# Real Estate Platform — Application Scenarios & Business Flows

## Table of Contents
1. [User Roles & Actor Types](#1-user-roles--actor-types)
2. [Authentication & Profile Scenarios](#2-authentication--profile-scenarios)
3. [Property Lifecycle Scenarios](#3-property-lifecycle-scenarios)
4. [Property Search & Discovery](#4-property-search--discovery)
5. [Viewing & Scheduling Scenarios](#5-viewing--scheduling-scenarios)
6. [Advertising System Scenarios](#6-advertising-system-scenarios)
7. [Sponsored Ads Scenarios](#7-sponsored-ads-scenarios)
8. [Chat & Communication Scenarios](#8-chat--communication-scenarios)
9. [Subscription & Billing Scenarios](#9-subscription--billing-scenarios)
10. [Service Provider Scenarios](#10-service-provider-scenarios)
11. [Publisher Upgrade Scenarios](#11-publisher-upgrade-scenarios)
12. [Office Verification Scenarios](#12-office-verification-scenarios)
13. [Review & Rating Scenarios](#13-review--rating-scenarios)
14. [Ledger & Accounting Scenarios](#14-ledger--accounting-scenarios)
15. [Payroll Scenarios](#15-payroll-scenarios)
16. [Admin & Moderation Scenarios](#16-admin--moderation-scenarios)
17. [Notification Scenarios](#17-notification-scenarios)
18. [Permission Matrix](#18-permission-matrix)
19. [State Machines](#19-state-machines)
20. [Map Scenarios](#20-map-scenarios)
21. [Rental Card Scenarios](#21-rental-card-scenarios)
22. [Deposit (Escrow) Scenarios](#22-deposit-escrow-scenarios)
23. [CRM (Leads) Scenarios](#23-crm-leads-scenarios)
24. [File System Scenarios](#24-file-system-scenarios)
25. [Statistics & Dashboards Scenarios](#25-statistics--dashboards-scenarios)
26. [AI Scenarios](#26-ai-scenarios)

---

## 1. User Roles & Actor Types

| Actor | Description |
|-------|-------------|
| **Guest (Anonymous)** | Unauthenticated user browsing properties, ads, service providers |
| **Registered User (Individual Publisher)** | Email-verified user who can list properties, chat, book viewings |
| **Office Publisher** | Verified real estate office with advanced features, analytics, badge |
| **Service Provider** | Photographer, lawyer, inspector, or marketer offering services |
| **Admin / Super Admin** | Full system access: moderation, user management, ledger, payroll |
| **System (Automated)** | Webhooks, scheduled jobs, FCM push, Pusher broadcasts |

---

## 2. Authentication & Profile Scenarios

### 2.1 Registration Flow
1. Guest fills registration form (name, email, password)
2. POST `/auth/register` → receives `{ user, token }`
3. Verification email sent automatically
4. Guest clicks link: GET `/auth/email/verify/{id}/{hash}` → email verified
5. If expired/missing: POST `/auth/email/verification-notification` to resend

### 2.2 Login Flow (OTP)
1. User submits phone/email → POST `/auth/login` (AuthController `sendOtp`)
2. System generates a 6-digit OTP (`OtpPurpose::LOGIN`) and stores it (expiry ~5 min)
3. OTP delivered via notification (SMS/email/notification)
4. User submits `phone/email + code` → POST `/auth/verify-otp`
5. Response: `{ user: {...}, token: "1|abc123..." }`
6. All subsequent requests include header `Authorization: Bearer 1|abc123...`
7. Token never expires (Sanctum API token)

### 2.3 Two-Factor Authentication Flow
1. POST `/auth/user/two-factor-authentication` → enables 2FA
2. GET `/auth/user/two-factor-qr-code` → SVG QR code
3. GET `/auth/user/two-factor-secret-key` → text secret
4. User scans QR in authenticator app, enters code
5. POST `/auth/user/confirmed-two-factor-authentication` → confirms
6. GET `/auth/user/two-factor-recovery-codes` → save these!
7. On next login: POST `/auth/two-factor-challenge` with `code` or `recovery_code`
8. POST `/auth/user/two-factor-recovery-codes` to regenerate

### 2.4 Password Reset Flow
1. POST `/auth/forgot-password` with email
2. User receives password reset link via email
3. POST `/auth/reset-password` with email, password, password_confirmation, token

### 2.5 Profile Management
- PUT `/auth/user/profile-information` — update name/email (email re-verification required)
- PUT `/auth/user/password` — change password (requires current_password)
- POST `/auth/user/confirm-password` — confirm before sensitive actions

### 2.6 Publisher Profile
- PUT `/api/publisher/profile` — update name, phone, website, description, social_links, avatar
- PUT `/api/publisher/contact-preference` — choose `chat` (internal messaging) or `external` (display phone/email)
- GET `/api/publisher/statistics` — property counts, views, favorites summary
- GET `/api/publisher/analytics` — detailed analytics (requires `advanced_analytics` feature in subscription)

---

## 3. Property Lifecycle Scenarios

### 3.1 Property Creation (Publisher)
1. POST `/api/dashboard/properties` with:
   - name, description, property_type (apartment/house/villa/land/commercial/office/warehouse/other)
   - type_of_contract (sale/rent)
   - country_id, city_id, longitude, latitude
   - rooms, bathrooms, area
   - detailed_info (optional JSON/markdown)
   - price, currency (default: USD)
   - main_image, gallery (uploaded via Spatie Media Library)
2. Property created with status `pending` → triggers admin review notification

### 3.2 Property with Photographer (Publisher)
1. POST `/api/dashboard/properties/with-photographer` — same as property creation plus:
   - provider_id: specific photographer
   - scheduled_at: when photographer should visit
   - photographer_notes: instructions
   - photographer_price: agreed price
2. Property saved as `draft` + Service Request created for photographer
3. Photographer can accept/reject the request → property moves to `pending` once photos uploaded

### 3.3 Property Approval (Admin)
1. Admin reviews pending properties
2. PATCH `/api/dashboard/properties/{id}/status` with:
   - status: `under_inspection` → triggers inspection workflow
   - status: `approved` → property goes public, visible in browse
   - status: `rejected` + rejection_reason: "Inappropriate images" etc.
   - status: `suspended` → temporary removal
3. Publisher receives notification on status change

### 3.4 Property Editing (Publisher)
- PATCH `/api/dashboard/properties/{id}` — update any field
- Status may reset to `pending` if significant changes detected

### 3.5 Property Sold/Archived (Publisher)
- PATCH `/api/dashboard/properties/{id}/status` with `status: sold` or `status: archived`
- Sold properties remain visible as historical records
- Archived properties are hidden from browse

### 3.6 Property Deletion
- DELETE `/api/dashboard/properties/{id}` — soft delete

### 3.7 My Properties
- GET `/api/dashboard/my-properties` — all properties owned by current user

---

## 4. Property Search & Discovery

### 4.1 Public Browsing
GET `/api/properties/browse` with any combination of:
- **Range filters**: price_min, price_max, area_min, area_max, rooms_min, rooms_max, bathrooms_min, bathrooms_max
- **Exact match**: property_type, type_of_contract, country_id, city_id
- **Text**: search (LIKE search on name, description)
- **Sort**: sort_by={price|area|rooms|bathrooms|created_at|views}, sort_order={asc|desc}
- **Pagination**: perPage (1-100), page
- Response: `{ data: [Property...], pagination: { current_page, last_page, per_page, total } }`

### 4.2 Random Properties
GET `/api/properties/random` — returns 10 random approved properties (for homepage/hero section)

### 4.3 Property Detail
GET `/api/properties/{id}/details` — full property with:
- All fields, country, city, publisher info
- `is_loved` boolean (if user is logged in)
- `publisher_is_verified` badge indicator
- main_image URL, gallery array of URLs, thumbnails

### 4.4 Favorite (Love) Toggle
POST `/api/dashboard/properties/{id}/favorite` — toggles property in user's loved list
- Uses `belongsToMany` relation on User model
- No separate "unfavorite" endpoint — toggle handles both

---

## 5. Viewing & Scheduling Scenarios

### 5.1 Book a Viewing (Buyer/Tenant)
1. POST `/api/dashboard/viewings` with:
   - property_id, scheduled_at (datetime, must be in future)
   - duration_minutes (15-240, default 60)
   - buffer_minutes (0-60, for travel time between viewings)
   - viewing_type: in_person | virtual | open_house
   - contact_name, contact_phone (optional)
   - notes: "I'm interested in the garden view"
   - max_attendees: 1-50
2. Status starts as `pending`
3. Publisher/Agent receives notification

### 5.2 Viewing Lifecycle (Agent/Publisher)

```
PENDING → CONFIRMED → COMPLETED
                   → NO_SHOW
PENDING → RESCHEDULED → CONFIRMED → ...
PENDING → CANCELLED
```

1. **Confirm**: PATCH `/api/dashboard/viewings/{id}/confirm`
2. **Reschedule**: PATCH `/api/dashboard/viewings/{id}/reschedule` with new `scheduled_at`
3. **Cancel**: PATCH `/api/dashboard/viewings/{id}/cancel` with optional `cancellation_reason`
4. **Complete**: PATCH `/api/dashboard/viewings/{id}/complete`
5. **No-Show**: PATCH `/api/dashboard/viewings/{id}/no-show`

### 5.3 Viewing Management
- GET `/api/dashboard/viewings` — all viewings with filters (status, property_id, date)
- GET `/api/dashboard/viewings/my` — my booked viewings
- GET `/api/dashboard/viewings/calendar` — calendar format with from/to date range
- GET `/api/dashboard/viewings/schedule` — agent's schedule
- DELETE `/api/dashboard/viewings/{id}` — soft delete

### 5.4 Conflict Prevention
- System prevents double-booking: no two viewings for same property at overlapping times
- Buffer minutes are respected when checking availability

---

## 6. Advertising System Scenarios

### 6.1 Ad Group Management
1. **Create group**: POST `/api/dashboard/ad-groups` { name (unique), description }
2. **Add ads to group**: When creating ads, specify ad_group_id
3. **Set default ad**: POST `/api/dashboard/ad-groups/{id}/set-default` { ad_group_id, ad_id }
4. **Remove default**: DELETE `/api/dashboard/ad-groups/{id}/default`
5. **Archive group**: DELETE `/api/dashboard/ad-groups/{id}` → is_archived=true
6. **Restore group**: POST `/api/dashboard/ad-groups/{id}/restore`

### 6.2 Ad Creation & Management
1. POST `/api/dashboard/ads`:
   - title (required), description (optional, 2000 chars)
   - media_type: video | image
   - ad_group_id: which group (optional)
   - external_url: click target (optional URL)
   - property_id: link to property (optional)
   - status: draft | active | paused (emitted if not provided)
   - start_date, end_date: scheduling (optional)
   - media[]: files or references
2. **Status management**: POST `/api/dashboard/ads/{id}/status` { status: active | paused | draft }
3. **Link property**: POST `/api/dashboard/ads/{id}/link-property` { property_id }
4. **Unlink property**: DELETE `/api/dashboard/ads/{id}/property`
5. **Archive**: DELETE `/api/dashboard/ads/{id}` → archived
6. **Restore**: POST `/api/dashboard/ads/{id}/restore`

### 6.3 Ad Display (Public)
- GET `/api/public/ads/display` — all active ads
- GET `/api/public/ads/display/standalone` — ads not in any group
- GET `/api/public/ads/display/{groupId}` — ads for specific group
- Returns: title, description, media_type, external_url, media_urls[], has_link

### 6.4 Ad Tracking
- POST `/api/ads/{id}/track/view` — records view (IP, user agent, user_id if authenticated)
- POST `/api/ads/{id}/track/visit` — records click-through

### 6.5 Ad Analytics
- GET `/api/dashboard/analytics/dashboard` — overview: total views, visits, CTR, by group
- GET `/api/dashboard/analytics/groups/{groupId}` — per-group analytics
- GET `/api/dashboard/analytics/ads/{adId}` — per-ad analytics
- GET `/api/dashboard/analytics/export` — export data (CSV/Excel)
- All accept `from` and `to` date filters

---

## 7. Sponsored Ads Scenarios

### 7.1 View Pricing
GET `/api/sponsored-ads/pricing` — returns tiers:
- **Basic**: rotation 1 (lowest priority), $4.99–$14.99
- **Standard**: rotation 2 (medium priority), $9.99–$29.99
- **Premium**: rotation 4 (highest priority), $19.99–$59.99

### 7.2 Create Sponsored Ad
1. POST `/api/dashboard/sponsored-ads`:
   - Required: title, media_type, pricing_tier, sponsor_duration, payment_method, currency
   - Optional: description, external_url, property_id, idempotency_key, media[]
   - payment_method: stripe (card) or balance (wallet deduction)
   - sponsor_duration: days_7 (7 days), days_14 (14 days), days_30 (30 days)
   - currency: usd, sar, aed
   - media: jpg/jpeg/png/webp/mp4/mov/webm (max 20MB each)
2. **Payment**: If `stripe` → redirect to Stripe checkout; if `balance` → deduct from user's ledger wallet
3. **Idempotency**: If idempotency_key provided, prevents duplicate charges
4. Ad becomes active immediately or at starts_at if scheduled

### 7.3 Cancel Sponsored Ad
POST `/api/dashboard/sponsored-ads/{id}/cancel` → partial refund based on remaining duration

### 7.4 Active Sponsored Ads (Public)
GET `/api/sponsored-ads/active` — currently active sponsored ads for display

---

## 8. Chat & Communication Scenarios

### 8.1 Start a Conversation
1. User wants to inquire about a property
2. POST `/api/chat/rooms` with `{ property_id, type: "group" }` → creates property inquiry room
3. Or POST `/api/chat/rooms` with `{ recipient_id, type: "private" }` → private chat

### 8.2 Chat Room List
GET `/api/chat/rooms` — all rooms with:
- Room type, name, property info
- Participants list
- Last message preview
- Unread count per room

### 8.3 Messaging
1. GET `/api/chat/rooms/{roomId}/messages` — paginated message history
2. POST `/api/chat/rooms/{roomId}/messages`:
   - body: message text
   - type: text | image | file
   - parent_id: for threaded replies (optional)
3. DELETE `/api/chat/rooms/{roomId}/messages/{messageId}` — delete own message
4. POST `/api/chat/rooms/{roomId}/typing` — broadcasts typing indicator via Pusher

### 8.4 Real-Time Integration
- Pusher channels: `user.{userId}`, `chat.{roomId}`, `property.{propertyId}`
- Events: new message, typing, message deleted, room created
- FCM push notification sent when user is offline

### 8.5 Rate Limiting
- Chat endpoints: 60 requests per minute
- Typing indicator: 1 request per second

---

## 9. Subscription & Billing Scenarios

### 9.1 Browse Plans
GET `/api/plans` — all active plans with features:
- **Individual Basic** ($9.99/30d): 5 listings, 10 images, 2 sponsored ads
- **Individual Pro** ($29.99/30d): 15 listings, 20 images, 3 featured, external contact, 5 sponsored ads, 5% discount
- **Office Basic** ($39.99/30d): 25 listings, 20 images, 5 featured, 10 sponsored ads, 10% discount
- **Office Pro** ($99.99/30d): 100 listings, 50 images, 15 featured, advanced analytics, verified badge, 25 sponsored ads, 15% discount
- **Office Enterprise** ($199.99/30d): unlimited, priority support, 25% discount

### 9.2 Checkout
1. POST `/api/checkout` { plan_id, discount_code? }
2. Server creates Stripe Checkout Session
3. Response: `{ checkout_url: "https://checkout.stripe.com/..." }`
4. User completes payment on Stripe
5. Stripe POST `/api/stripe/webhook` → server processes:
   - Creates Subscription record (status: active)
   - Creates journal entries for revenue
   - Updates user's feature limits

### 9.3 Validate Coupon
POST `/api/coupons/validate` { code, plan_id? } → returns discount amount and validity

### 9.4 Current Subscription
- GET `/api/subscription/current` — active plan, remaining days, feature usage
- GET `/api/subscription/history` — all past subscriptions
- POST `/api/subscription/cancel` — cancel auto-renewal
- GET `/api/subscription/features` — list all features with enabled/limit status
- GET `/api/subscription/features/{slug}` — check specific feature
- GET `/api/subscription/{id}/status-logs` — status transition audit log

### 9.5 Feature Gating
Subscription features checked before actions:
- **property_listing_limit**: Can user create another property?
- **property_images**: Max images per property
- **featured_property**: Can user mark property as featured?
- **advanced_analytics**: Access to detailed analytics
- **verified_badge**: Show verified badge
- **external_contact**: Display external contact info
- **priority_support**: Priority service request handling
- **sponsored_ads_limit**: Max sponsored ads
- **sponsored_ads_discount_pct**: Discount on sponsored ad prices

### 9.6 Admin Subscription Management
Full CRUD for: Plans, Features, Plan-Feature links, Discounts at `/api/admin/subscription/*`
- POST `/api/admin/subscription/plans/{id}/features` — sync features to plan

---

## 10. Service Provider Scenarios

### 10.1 Browse Service Providers (Public)
GET `/api/public/service-providers` filtered by type, city, search

### 10.2 Register as Service Provider
1. POST `/api/service-provider/register`:
   - type: photographer | lawyer | inspector | marketer | other
   - bio, experience_years, license_number
   - price_type: fixed | hourly | negotiable
   - price_per_task
   - coverage_city_ids[]: cities they serve
2. Status: unverified until admin approves

### 10.3 Provider Profile Management
- GET `/api/service-provider/profile` — my profile
- PUT `/api/service-provider/profile` — update
- POST `/api/service-provider/availability` — toggle available/busy

### 10.4 Service Request Lifecycle (Provider View)

```
PENDING → ACCEPTED → IN_PROGRESS → COMPLETED
PENDING → REJECTED
PENDING → CANCELLED (by client)
```

1. Provider sees new requests: GET `/api/service-provider/service-requests?status=pending`
2. **Accept**: POST `/api/service-provider/service-requests/{id}/accept`
3. **Start Work**: POST `/api/service-provider/service-requests/{id}/start`
4. **Complete Task**: POST `/api/service-provider/service-request-tasks/{taskId}/complete`
5. **Complete Request**: POST `/api/service-provider/service-requests/{id}/complete`
   - Triggers payment release to provider balance
6. **Reject**: POST `/api/service-provider/service-requests/{id}/reject`

### 10.5 Provider Billing
- GET `/api/service-provider/balance` — available wallet balance
- GET `/api/service-provider/earnings` — earnings history
- POST `/api/service-provider/withdraw` — request withdrawal (creates ledger debit)

### 10.6 Service Request Lifecycle (Client View)
1. POST `/api/service-requests`:
   - service_type: photography | inspection | legal | marketing
   - property_id, provider_id (optional), scheduled_at, client_notes, price
2. GET `/api/service-requests` — my requests with status filter
3. GET `/api/service-requests/{id}` — detail with tasks, provider info
4. POST `/api/service-requests/{id}/cancel` — cancel (only if pending/accepted)

### 10.7 Admin Oversight
- GET `/api/admin/service-providers` — all providers
- POST `/api/admin/service-providers/{id}/verify` — approve provider
- POST `/api/admin/service-providers/{id}/unverify` — revoke
- GET `/api/admin/service-requests` — all requests
- GET `/api/admin/service-requests/{id}` — detail

### 10.8 Commission System
- Platform takes commission from service request price
- Configurable per service_type via `ServiceCommissionConfig`
- Remaining amount credited to provider's ledger balance

---

## 11. Publisher Upgrade Scenarios

### 11.1 Upgrade Request Flow
1. Individual publisher wants to become an "Office"
2. POST `/api/publisher/upgrade-request` → status: pending
3. GET `/api/publisher/upgrade-status` → check current status
4. Admin reviews: GET `/api/admin/office-upgrade-requests`
5. Admin approves: POST `/api/admin/upgrade-requests/{id}/approve`
   - User's publisher_type becomes "office"
   - Gains ability to add employees_count, office info
6. Admin rejects: POST `/api/admin/upgrade-requests/{id}/reject` with rejection_reason

---

## 12. Office Verification Scenarios

### 12.1 Verification Flow
1. Office publisher applies
2. Admin verifies: POST `/api/admin/offices/{id}/verify`
   - User.is_verified = true
   - Office gets "verified" badge on public profile
3. Admin can unverify: POST `/api/admin/offices/{id}/unverify`

### 12.2 Public Office Profile
- GET `/api/dashboard/publishers/offices` — list of offices
- GET `/api/dashboard/publishers/offices/{id}` — office detail with:
  - name, email, phone, website, description
  - is_verified badge
  - average_rating, reviews_count
  - avatar, properties_count

---

## 13. Review & Rating Scenarios

1. GET `/api/dashboard/offices/{officeId}/reviews` — all reviews for an office
2. POST `/api/reviews`:
   - reviewed_id: office/publisher being reviewed
   - property_id: related property (optional)
   - rating: 1-5 integer
   - comment: text
3. DELETE `/api/reviews/{id}` — admin or review owner
4. Review affects office's average_rating (calculated on User model)

---

## 14. Ledger & Accounting Scenarios

### 14.1 Double-Entry System
- Every financial transaction creates balanced journal entries
- Account categories: Asset, Liability, Equity, Revenue, Expense
- Account types: user_balance, revenue, clearing, platform_fee, liability, expense

### 14.2 User Balance
- GET `/api/ledger/balance` — current user's wallet balance
- GET `/api/ledger/statement` — transaction history with date filter

### 14.3 Chart of Accounts
- GET `/api/accounts/tree` — hierarchical account structure (5-level hierarchy)
- Full CRUD at `/api/accounts`

### 14.4 Journal Entries
- GET/POST `/api/journal-entries` — create draft entries
- GET/PUT/DELETE `/api/journal-entries/{id}` — manage draft entries
- POST `/api/journal-entries/{journalEntry}/post` — finalize (creates account entries, updates balances)
- Once posted, cannot be edited (must reverse)

### 14.5 Trial Balance
- GET `/api/trial-balance?as_of=2026-01-31` — debits vs credits per account

### 14.6 Key Financial Events
| Event | Ledger Impact |
|-------|--------------|
| Sponsored Ad payment via balance | Debit user_balance, Credit platform_fee + revenue |
| Stripe subscription payment | Debit clearing, Credit revenue → clearing settled via webhook |
| Service request completion | Debit client balance, Credit clearing → provider balance + platform fee |
| Provider withdrawal | Debit provider balance, Credit clearing → external payment |
| Payroll run | Debit expense, Credit provider balance |

---

## 15. Payroll Scenarios

### 15.1 Payroll Setup
1. GET `/api/payroll/providers` — providers eligible for payroll
2. POST `/api/payroll/setup`:
   - service_provider_profile_id, type (monthly/per_task/both)
   - base_salary (for monthly), per_task_rate (for per-task)
   - start_date, end_date

### 15.2 Run Payroll
POST `/api/payroll/run` { period_start, period_end }
- Calculates: salary portion + tasks completed * per_task_rate
- Creates journal entries
- Credits provider balance

### 15.3 Payment Management
- GET `/api/payroll/payments` — all payment records
- GET `/api/payroll/{payroll}` — specific payroll detail
- PUT `/api/payroll/{payroll}` — update setup

---

## 16. Admin & Moderation Scenarios

### 16.1 User Management
- List/Create/Update/Delete users at `/api/users`
- Toggle status active/inactive: `/api/users/{id}/toggle-status`

### 16.2 Role & Permission Management
- Full CRUD on roles at `/api/roles`
- Assign permissions: POST `/api/roles/{id}/permissions` { permissions: [...] }
- List all permissions: GET `/api/permissions`

### 16.3 Property Moderation
- PATCH `/api/dashboard/properties/{id}/status` — change status with gate policies
- Different users may have different status transition permissions

### 16.4 Office Verification (see sections 11-12)

### 16.5 Service Provider Verification (see section 10.7)

### 16.6 Category Management
- Full CRUD on categories at `/api/categories`

### 16.7 Location Management
- Full CRUD on countries and cities at `/api/location/countries` and `/api/location/cities`

---

## 17. Notification Scenarios

### 17.1 Notification Types
| Type | Trigger |
|------|---------|
| NEW_MESSAGE | User receives a chat message |
| NEW_OFFER | New offer on a property |
| PROPERTY_UPDATE | Property status changed, price changed |
| BOOKING_CONFIRMED | Viewing confirmed by agent |
| ADMIN_ALERT | New property pending review, upgrade request |
| SUBSCRIPTION_EXPIRING | Subscription about to expire |

### 17.2 Notification API
- GET `/api/notifications` — paginated list
- GET `/api/notifications/unread-count` — badge count
- PATCH `/api/notifications/{id}/read` — single
- PATCH `/api/notifications/read-all` — all
- DELETE `/api/notifications/{id}` — remove

### 17.3 Push Notifications (FCM)
1. Frontend/mobile registers FCM token: POST `/api/fcm/register` { token, device_type }
2. Server sends push via Firebase when user is offline
3. Revoke on logout: DELETE `/api/fcm/revoke`

### 17.4 Real-Time (Pusher)
- Channel auth via broadcasting
- Events pushed in real-time to connected users
- WebSocket connection maintained by frontend

---

## 18. Permission Matrix

### 18.1 Full Permission String List
```
# Roles
roles.list, roles.show, roles.create, roles.edit, roles.delete, roles.get-all-permissions

# Users
users.list, users.show, users.create, users.edit, users.delete, users.toggle-status

# Properties
properties.list, properties.show, properties.create, properties.edit, properties.delete

# Viewings
viewings.list, viewings.show, viewings.create, viewings.edit, viewings.delete,
viewings.confirm, viewings.cancel, viewings.reschedule, viewings.complete

# Ad Groups
ad_groups.list, ad_groups.show, ad_groups.create, ad_groups.edit, ad_groups.delete,
ad_groups.archive, ad_groups.restore, ad_groups.set-default

# Ads
ads.list, ads.show, ads.create, ads.edit, ads.delete, ads.archive, ads.restore,
ads.set-status, ads.link-property, ads.view-analytics, ads.export

# Countries & Cities
countries.list, countries.show, countries.create, countries.edit, countries.delete
cities.list, cities.show, cities.create, cities.edit, cities.delete

# Subscription Plans/Features/Discounts
subscription_plans.list, subscription_plans.show, subscription_plans.create,
subscription_plans.edit, subscription_plans.delete
subscription_features.list, subscription_features.show, subscription_features.create,
subscription_features.edit, subscription_features.delete
subscription_plan_features.list, subscription_plan_features.show,
subscription_plan_features.create, subscription_plan_features.edit,
subscription_plan_features.delete
subscription_discounts.list, subscription_discounts.show, subscription_discounts.create,
subscription_discounts.edit, subscription_discounts.delete

# Offices
offices.list, offices.show, offices.verify, offices.unverify,
offices.list-upgrade-requests, offices.approve-upgrade, offices.reject-upgrade

# Publishers
publishers.list, publishers.show

# Reviews
reviews.list, reviews.show, reviews.delete

# Service Providers
service_providers.list, service_providers.show, service_providers.verify, service_providers.unverify

# Service Requests
service_requests.list, service_requests.show

# Accounts (Ledger)
accounts.list, accounts.show, accounts.create, accounts.edit, accounts.delete

# Journal Entries
journal_entries.list, journal_entries.show, journal_entries.create, journal_entries.edit,
journal_entries.delete, journal_entries.post

# Trial Balance & Payroll
trial_balance.view, payroll.list, payroll.manage, payroll.run
```

### 18.2 Default Super Admin
- Role: "super-admin"
- Has ALL permissions above
- Default user: admin@admin.com

---

## 19. State Machines

### 19.1 Property Status
```
DRAFT ──────→ PENDING ──→ UNDER_INSPECTION ──→ APPROVED
                │                    │              │
                │                    │              ├──→ SOLD
                │                    │              └──→ ARCHIVED
                │                    │
                ├──→ REJECTED        └──→ SUSPENDED
                └──→ SUSPENDED
```

### 19.2 Viewing Status
```
PENDING ──→ CONFIRMED ──→ COMPLETED
  │                        │
  │                        └──→ NO_SHOW
  │
  ├──→ RESCHEDULED ──→ (back to PENDING)
  │
  └──→ CANCELLED
```

### 19.3 Service Request Status
```
PENDING ──→ ACCEPTED ──→ IN_PROGRESS ──→ COMPLETED
  │            │
  │            └──→ CANCELLED
  │
  ├──→ REJECTED
  └──→ CANCELLED
```

### 19.4 Ad Status
```
DRAFT ──→ ACTIVE ──→ ARCHIVED
  │         │
  │         └──→ PAUSED ──→ (back to ACTIVE)
  │
  └──→ ARCHIVED
```

### 19.5 Subscription Status
```
PENDING ──→ ACTIVE ──→ EXPIRED
              │
              └──→ CANCELLED
```

### 19.6 Journal Entry Status
```
DRAFT ──→ POSTED
           │
           └──→ REVERSED
```

### 19.7 Payroll Payment Status
```
PENDING ──→ PAID
  │
  └──→ FAILED
```

---

## 20. Map Scenarios

### 20.1 Properties on Map (Public)
1. GET `/api/map/properties?bounds=...&city_id=...&property_type=...&min_price=...&max_price=...`
2. Returns properties with lat/lng within the requested bounds, optionally filtered
3. Used for the map explorer view with geocoding and clustering

### 20.2 Trader Competitive Map
1. GET `/api/dashboard/trader/competitive-map`
2. Returns competitor density & market positioning around the trader's own listings

---

## 21. Rental Card Scenarios

### 21.1 Create Rental Card
1. POST `/api/dashboard/rental-cards` with `property_id`, `tenant_user_id`, `start_date`, `end_date`, `rent_amount`
2. Card created with status `active`; property marked as rented
3. Only one active rental card per property

### 21.2 View Active & History
- GET `/api/dashboard/properties/{propertyId}/rental-cards/active`
- GET `/api/dashboard/properties/{propertyId}/rental-cards/history`

### 21.3 End / Renew
- PATCH `/api/dashboard/rental-cards/{id}/end` — closes the card (status `ended`), frees the property
- PATCH `/api/dashboard/rental-cards/{id}/renew` — extends `end_date` (status `renewed`)

---

## 22. Deposit (Escrow) Scenarios

### 22.1 Create Deposit
1. POST `/api/dashboard/deposits` with `property_id`, `amount`
2. System identifies buyer (requester) & seller (property publisher)
3. Deposit created with status `pending`

### 22.2 Pay / Hold
1. POST `/api/dashboard/deposits/{id}/pay` → status becomes `held`
2. Buyer is charged; funds held in escrow

### 22.3 Release / Refund / Cancel
- POST `/api/dashboard/deposits/{id}/release` → released to seller (`released_by` recorded)
- POST `/api/dashboard/deposits/{id}/refund` → returned to buyer (`refunded_by` recorded)
- POST `/api/dashboard/deposits/{id}/cancel` → cancelled before hold
- If buyer/seller disagree → status `disputed`

### 22.4 My Deposits / My Sales
- GET `/api/dashboard/my-deposits` — deposits where I'm buyer
- GET `/api/dashboard/my-sales` — deposits where I'm seller

---

## 23. CRM (Leads) Scenarios

### 23.1 Create Lead
1. POST `/api/dashboard/crm/leads` with `name`, `phone`, `email`, `source`, `status`
2. Trader role only (`EnsureUserIsTrader` middleware)
3. Duplicate detection available: GET `/api/dashboard/crm/leads/check-duplicate?phone=&email=`

### 23.2 Lead Lifecycle
1. Lead status: `new → contacted → qualified → won | lost`
2. PATCH `/api/dashboard/crm/leads/{id}/status` — move between states
3. POST `/api/dashboard/crm/leads/{id}/archive` / `restore` — archive soft
4. GET `/api/dashboard/crm/leads/export` — CSV export

### 23.3 Lead Notes & Follow-ups
1. Notes: POST `/api/dashboard/crm/leads/{leadId}/notes`, PATCH/DELETE `/api/dashboard/crm/notes/{noteId}`
2. Follow-ups: POST `/api/dashboard/appointments/follow-ups` (morphTo `followable` → lead)

### 23.4 CRM Dashboard
- GET `/api/dashboard/crm/dashboard/summary` — KPIs
- GET `/api/dashboard/crm/dashboard/today` — today snapshot

---

## 24. File System Scenarios

### 24.1 Folders
1. POST `/api/folders` → create folder (root or under `parent_id`)
2. GET `/api/folders` → tree of user folders (auto-created General + per-property folders)
3. POST `/api/folders/{id}/move` / `rename` — reorganize
4. DELETE `/api/folders/{id}` — recursive delete

### 24.2 Files
- POST `/api/files/text` — create text file (content)
- POST `/api/files/image` — upload image (multipart)
- PUT `/api/files/{id}/text` — edit text file
- POST `/api/files/{id}/move` / `rename` / DELETE `/api/files/{id}`
- GET `/api/properties/{propertyId}/files` — property folder contents

### 24.3 Storage Quota
- GET `/api/storage/status` — used/max bytes
- GET `/api/storage/packages` — available upgrade packages
- POST `/api/storage/upgrade` — upgrade to a package

---

## 25. Statistics & Dashboards Scenarios

### 25.1 Trader Dashboard
- GET `/api/dashboard/trader/summary` — KPIs (listings, views, leads, appointments, rentals, ads)
- GET `/api/dashboard/trader/views-trend` — time series of property views
- GET `/api/dashboard/trader/leads-by-status` / `properties-by-status` — distributions
- GET `/api/dashboard/trader/top-properties` — best performers
- GET `/api/dashboard/trader/recent-leads` / `upcoming-appointments` / `expiring-rentals` / `sponsored-ads-summary`
- GET `/api/dashboard/trader/export/properties` — CSV export

### 25.2 Property Stats
- GET `/api/dashboard/properties/{property}/stats` — views, favorites, leads per property

### 25.3 Market Stats (Public)
- GET `/api/market/overview` — market KPIs
- GET `/api/market/by-city` / `by-category` / `by-price-range` — distributions
- GET `/api/market/top-viewed` / `top-saved` — trending properties
- GET `/api/market/listings-trend` — listings over time

### 25.4 Admin Dashboard
- GET `/api/admin/statistics/overview` — platform KPIs
- GET `/api/admin/statistics/properties` / `crm` / `ads` / `subscriptions` / `moderation` / `communication`

---

## 26. AI Scenarios

### 26.1 Smart Search
1. POST `/api/ai/search` with natural-language query (e.g., "شقة إيجار في دبي بميزانية 5000")
2. `SmartSearchService` extracts requirements → queries properties → returns ranked `SearchResult`s
3. Rate limited via `throttle:ai-search`

### 26.2 Description Assistant
- POST `/api/ai/description/generate` — draft property description from structured inputs
- POST `/api/ai/description/improve` — improve an existing description
- POST `/api/ai/description/suggest-title` — title suggestions
- POST `/api/ai/description/suggest-features` — feature bullets
- Powered by Kimi API via `AiService`; rate limited via `throttle:ai-description`

---

## End of Document

**Total Endpoints**: 260+
**Total Models**: 60+ (across 12 modules)
**Total Enums**: 43
**Real-Time Channels**: user.{id}, chat.{roomId}, property.{propertyId}
**Auth Method**: Bearer token (Sanctum) via OTP login
**API Docs**: Available at `/docs` (Scribe auto-generated)
