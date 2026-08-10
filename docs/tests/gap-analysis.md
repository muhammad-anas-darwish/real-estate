# Gap Analysis — Test Coverage

**Date:** 2026-07-25
**Total test files:** 56
**Controllers total:** 59
**Services total:** 73
**Models/Entities total:** 46

---

## Summary per Module

### ✅ Ai — COMPLETE (2 controllers, 3 services, 5 tests)

| Test File | Covers |
|---|---|
| `AiServiceTest` | AiService (chat, retry, error handling) |
| `SmartSearchServiceTest` | SmartSearchService (search extraction, caching) |
| `SmartSearchControllerTest` | SmartSearchController (search endpoint, validation, fallback) |
| `DescriptionAssistantServiceTest` | DescriptionAssistantService (generate, improve, suggest-title, suggest-features) |
| `DescriptionAssistantControllerTest` | DescriptionAssistantController (auth, endpoints, error handling) |

---

### ✅ Crm — COMPLETE (4 controllers, 3 services, 5 tests)

| Test File | Covers |
|---|---|
| `FoundationTest` | Basic CRM auth/role setup |
| `LeadTest` | Lead CRUD, archive, restore, duplicate check, status transitions, assignment |
| `LeadDashboardTest` | CRM dashboard summary, today's stats |
| `LeadExportTest` | Lead export (CSV, filters) |
| `LeadNoteTest` | LeadNote CRUD |

---

### ✅ Deposit — COMPLETE (1 controller, 1 service, 1 test)

| Test File | Covers |
|---|---|
| `DepositTest` | Deposit CRUD, pay, release, refund, cancel, my-deposits, my-sales |

---

### ✅ FileSystem — COMPLETE (3 controllers, 3 services, 4 tests)

| Test File | Covers |
|---|---|
| `FileTest` | File CRUD, text files, move, rename, media |
| `FolderTest` | Folder CRUD, move, rename |
| `StorageQuotaTest` | Storage status, packages, upgrade |
| `PropertyFolderIntegrationTest` | Property-folder integration |

---

### ✅ Statistics — COMPLETE (4 controllers, 7 services, 6 tests)

| Test File | Covers |
|---|---|
| `FoundationTest` | Basic setup |
| `TraderDashboardTest` | Trader dashboard summary, views, leads, properties |
| `PropertyStatsTest` | Property statistics |
| `MarketStatsTest` | Market overview, by-city, by-category, by-price-range |
| `AdminDashboardTest` | Admin statistics |
| `TraderJourneyTest` | Trader journey analytics |

---

### ✅ RealEstate — MOSTLY COMPLETE (18 tests, 11 controllers, 14 services)

| Test File | Covers |
|---|---|
| `PropertyTest` | Property CRUD, filters, search, status transitions |
| `RentalCardTest` | RentalCard CRUD (unit) |
| `RentalCardApiTest` | RentalCard API endpoints |
| `RentalCardServiceTest` | RentalCardService unit |
| `RentalCardPublicFilterTest` | Public rental card filtering |
| `RentalCardMediaTest` | RentalCard media upload/management |
| `AdTest` | Ad CRUD |
| `AdGroupTest` | AdGroup CRUD |
| `AdDisplayTest` | Ad display (public) |
| `AdTrackingTest` | Ad tracking (visits, views) |
| `AdAnalyticsTest` | Ad analytics |
| `AppointmentTest` | Appointment CRUD, status transitions |
| `MapControllerTest` | Map controller endpoints |
| `MapBoundsTest` | Map bounds calculation |
| `MapExplorerIntegrationTest` | Map explorer integration |
| `MapEdgeCasesTest` | Map edge cases |
| `TraderCompetitiveMapTest` | Trader competitive map |
| `GeocodingServiceTest` | Geocoding service |

**Missing in RealEstate:**
| Component | Status |
|---|---|
| `SponsoredAdController` | ❌ NO TESTS |
| `SponsoredAdService` | ❌ NO TESTS |
| `ReviewController` | ❌ NO TESTS |
| `ReviewService` | ❌ NO TESTS |
| `MapFilterService` | ❌ NO DIRECT TESTS |
| `PropertyStatusService` | ❌ NO DIRECT TESTS |
| `AnalyticsService` | ❌ NO DIRECT TESTS |

---

### 🟡 Core/Location SUBMODULE (2 controllers, 2 services, 2 tests)

| Test File | Covers |
|---|---|
| `CountryTest` | Country CRUD |
| `CityTest` | City CRUD |

---

### 🟡 Subscription — PARTIAL (5 tests, 9 controllers, 8 services)

| Test File | Covers |
|---|---|
| `PublicPlanTest` | Public plan listing/show |
| `SubscriptionPlanTest` | Admin plan CRUD |
| `SubscriptionFeatureTest` | Admin feature CRUD |
| `SubscriptionDiscountTest` | Admin discount CRUD |
| `SubscriptionTest` | User subscription (current, history, cancel, features) |

**Missing in Subscription:**
| Component | Status |
|---|---|
| `CheckoutController` | ❌ NO TESTS |
| `CheckoutService` | ❌ NO TESTS |
| `CouponController` | ❌ NO TESTS |
| `StripeWebhookController (WebhookController)` | ❌ NO TESTS |
| `StripeWebhookService` | ❌ NO TESTS |
| `SubscriptionAccess` | ❌ NO TESTS |

---

### 🟡 ServiceProvider — PARTIAL (3 tests, 5 controllers, 3 services)

| Test File | Covers |
|---|---|
| `ServiceProviderTest` | Provider registration, public listing, admin verify/unverify |
| `ProviderBillingTest` | Provider billing, withdraw, ledger integration |
| `ServiceRequestTest` | Service request lifecycle (create, accept, reject, start, complete, tasks) |

**Missing in ServiceProvider:**
| Component | Status |
|---|---|
| `AdminServiceRequestController` | ❌ NO TESTS |
| `ClientServiceRequestController` (some flows) | ⚠️ PARTIALLY covered |
| `ProviderServiceRequestController` (some flows) | ⚠️ PARTIALLY covered |

---

### 🔴 Auth — WEAK (2 tests, 4 controllers, 5 services)

| Test File | Covers |
|---|---|
| `AuthenticationTest` | Register, login, logout, forgot-password |
| `AuthExampleTest` | Stub (asserts true) |

**Missing in Auth:**
| Component | Status |
|---|---|
| Email verification endpoint | ❌ NO TESTS |
| Reset password endpoint | ❌ NO TESTS |
| User info (`/user`) endpoint | ❌ NO TESTS |
| `PublisherController` | ❌ NO TESTS |
| `PublisherService` | ❌ NO TESTS |
| `RoleController` | ❌ NO TESTS |
| `RoleService` | ❌ NO TESTS |
| `UserController` | ❌ NO TESTS |
| `UserManagementService` | ❌ NO TESTS |
| `UpgradeRequestService` | ❌ NO TESTS |
| `OtpService` | ❌ NO TESTS |

---

### 🔴 Communication — WEAK (2 tests, 6 controllers, 16 services)

| Test File | Covers |
|---|---|
| `ChatApiTest` | Chat rooms CRUD, messages, conversation creation |
| `NotificationApiTest` | Notification list, unread-count, mark-read, destroy, FCM token register/revoke |

**Missing in Communication:**
| Component | Status |
|---|---|
| `TypingController` | ❌ NO TESTS |
| `FcmTokenController` (some flows) | ⚠️ PARTIALLY in NotificationApiTest |
| `FcmService` | ❌ NO TESTS |
| `FcmTokenService` | ❌ NO TESTS |
| `FcmChannel` | ❌ NO TESTS |
| `FcmPayload` | ❌ NO TESTS |
| `UserPresenceService` | ❌ NO TESTS |
| `ChannelAuthService` | ❌ NO TESTS |
| `NotificationFormatter` | ❌ NO TESTS |
| `PusherNotificationChannel` | ❌ NO TESTS |
| `MessageAttachmentService` | ❌ NO TESTS |
| `NotificationPreferenceService` | ❌ NO TESTS |

---

### 🔴 Core (Main + SubModules) — WEAK (2 tests, 5 controllers, 6 services)

| Test File | Covers |
|---|---|
| `CountryTest` | Country CRUD |
| `CityTest` | City CRUD |

**Missing in Core:**
| Component | Status |
|---|---|
| `CategoryController` | ❌ NO TESTS |
| `CategoryService` | ❌ NO TESTS |
| `SearchController` | ❌ NO TESTS |
| `SearchService` | ❌ NO TESTS |
| `UploadFileController` | ❌ NO TESTS |
| `TemporaryFileService` | ❌ NO TESTS |
| `MediaSyncService` | ❌ NO TESTS |

---

### 🔴 Ledger — PARTIAL (2 tests, 4 controllers, 4 services)

| Test File | Covers |
|---|---|
| `AccountTest` | Account CRUD, tree, entries, balances |
| `LedgerIntegrationTest` | Ledger balance, statement, journal entries, payroll integration |

**Missing in Ledger:**
| Component | Status |
|---|---|
| `JournalEntryController` | ❌ NO TESTS |
| `JournalEntryService` | ⚠️ PARTIALLY tested via integration |
| `LedgerController` | ❌ NO TESTS |
| `LedgerService` | ❌ NO TESTS |
| `PayrollController` | ❌ NO TESTS |
| `PayrollService` | ❌ NO TESTS |

---

## Grand Summary — Missing Test Targets

| Priority | Module | What's Missing |
|---|---|---|
| 🔴 HIGH | **Auth** | PublisherController, RoleController, UserController, all Auth services (Otp, Publisher, Role, UpgradeRequest, UserManagement) |
| 🔴 HIGH | **Communication** | TypingController, FcmTokenController, all Fcm services, UserPresence, ChannelAuth, NotificationFormatter, PusherChannel, MessageAttachment, NotificationPreference |
| 🔴 HIGH | **Core** | CategoryController+Service, SearchController+Service, UploadFileController, TemporaryFileService, MediaSyncService |
| 🟡 MEDIUM | **Subscription** | CheckoutController+Service, CouponController, WebhookController+Service, SubscriptionAccess |
| 🟡 MEDIUM | **Ledger** | JournalEntryController+Service, LedgerController+Service, PayrollController+Service |
| 🟡 MEDIUM | **RealEstate** | SponsoredAdController+Service, ReviewController+Service, MapFilterService, PropertyStatusService, AnalyticsService |
| 🟡 MEDIUM | **ServiceProvider** | AdminServiceRequestController (dedicated tests) |

**Totals:**
- ❌ Controllers without tests: **20**
- ❌ Services without dedicated tests: **25+**
- ✅ Tested controllers: **39**
- ✅ Tested services: **~48**
