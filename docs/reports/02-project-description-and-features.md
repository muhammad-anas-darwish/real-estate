# Project Description and Features Report

## Project Overview

**Real Estate Platform** is a comprehensive, modular real estate management system built with **Laravel 12**. It serves as a dual-sided marketplace connecting property owners and real estate agents (publishers) with potential buyers and tenants. The platform provides property listing management, classified advertisements, real-time communication, subscription-based monetization, and detailed analytics — all exposed through a RESTful API.

The system is organized into five independent but interconnected modules (Auth, Core, RealEstate, Communication, Subscription), following a service-oriented architecture with DTOs, API resources, and caching layers.

---

## Features

### 1. Authentication & User Management

- **Full authentication cycle**: Register, login, logout, email verification, password reset, two-factor authentication (via Laravel Fortify).
- **Sanctum API tokens**: Secure token-based authentication for mobile and SPA clients.
- **Role-based access control (RBAC)**: Granular permissions per resource (e.g., `properties.create`, `ads.delete`) using Spatie Laravel Permission.
- **User types**: Two publisher types — regular users and administrators — with distinct permission sets.
- **Account management**: Profile updates, password changes, account status toggling.

### 2. Property & Real Estate Listings

- **Full CRUD**: Create, read, update, archive properties with soft deletes.
- **Property details**: Name, description, type (apartment, house, villa, land, commercial, office, warehouse, other), contract type (sale/rent), price, currency, area, rooms, bathrooms, location (country/city), coordinates (longitude/latitude), detailed info.
- **Media uploads**: Main image + gallery with automatic thumbnails and responsive image conversions (thumb 300×200, medium 800×600) via Spatie Media Library.
- **Advanced search & filtering**:
  - Single-value filters: `property_type`, `type_of_contract`, `country_id`, `city_id`, `status`, `rooms`, `bathrooms`, `publisher_id`
  - Multi-value filters: `id` (WHERE IN)
  - Searchable columns: `name`, `description` (LIKE query)
  - Date range filters: `created_at`
  - Range filters: `price_min`/`price_max`, `area_min`/`area_max`, `rooms_min`/`rooms_max`, `bathrooms_min`/`bathrooms_max`
- **Property status workflow**: Pending → Approved/Rejected → Sold/Archived/Suspended.
- **Favorite/toggle**: Users can favorite properties and query `is_loved` status via `withExists`.
- **View tracking**: Automatic view increment with IP-based deduplication tracking via `PropertyView` model.

### 3. Classifieds & Advertisement System

- **Ad groups**: Organize ads into named groups for campaign management.
- **Ad scheduling**: Start/end date targeting for time-specific campaigns.
- **Media types**: Image and video ad support.
- **Ad statuses**: Draft → Active → Paused → Archived lifecycle.
- **Default ads**: Fallback ad configuration per group.
- **Ad-property linking**: Associate ads with specific property listings.
- **Standalone ads**: Ads without group membership for flexible placement.

### 4. Statistics, Reports & Analytics

- **Dashboard analytics**: Total ads, active ads, total views, total visits, click-through rate (CTR).
- **Group-level analytics**: Per-group performance metrics.
- **Ad-level analytics**: Individual ad views, visits, CTR with time-series data (daily breakdown).
- **Trend data**: Date-range filtering for custom reporting periods.
- **Report export**: CSV-style data export with configurable date ranges and full ad performance details.

### 5. Map Display

Property listings include geographic coordinates (latitude/longitude) for map-based visualization and location-aware browsing.

### 6. Advanced Search Features

- Combined query parameter filtering (equality + range + search + date).
- Sortable results with configurable sort field and direction.
- Paginated responses with metadata.
- Separate public (approved/sold only) vs. dashboard (admin/all statuses) search endpoints.

### 7. Two Types of Publishers

- **Regular publishers**: Can list properties, manage their own ads, and use platform features based on subscription plan limits.
- **Admin publishers**: Full access to all properties, ability to approve/reject listings, manage all ads and analytics, and configure subscription plans and system settings.

### 8. Real-Time Chat

- **Chat rooms**: Private rooms between users with property context.
- **Messages**: Text messages with attachment support, soft deletes, reply threading.
- **Typing indicators**: Real-time "user is typing" events via Pusher WebSockets.
- **Authorization**: Private channel authentication for chat rooms.
- **Attachment support**: Files stored on configurable disk (local/S3) with temporary URLs for S3.

### 9. Push Notifications (Pusher + FCM)

- **Pusher (WebSocket)**: Real-time in-app notifications delivered via Laravel broadcasting — new messages, property status changes, booking confirmations, admin alerts, new offers.
- **FCM (Firebase Cloud Messaging)**: Mobile push notifications for Android/iOS devices — token registration, device-type targeting, active token management, automatic cleanup of expired tokens.
- **Notification preferences**: Per-channel (database, pusher, FCM) enable/disable for each notification type.
- **Unread count**: Real-time unread notification count endpoint.
- **Rich notifications**: Different notification types (NewMessage, PropertyStatusChanged, BookingConfirmed, NewOffer, AdminAlert) with structured data payloads.

### 10. Monthly & Annual Subscription System

- **Subscription plans**: Configurable plans with name, slug, description, price, currency, duration (in days), active status, and sort order.
- **Features per plan**: Toggle-type (on/off) and limit-type (numeric cap) features mapped to plans via pivot table.
- **Coupons & discounts**: Percentage or fixed-amount discounts with per-plan or global scope, usage limits, and expiration dates.
- **Stripe integration**: Full checkout flow via Stripe Checkout Sessions with webhook handling for session completion, expiration, and refunds.
- **Subscription lifecycle**: Pending → Active → Expired/Cancelled with status change logging.
- **Feature gating**: Middleware (`EnsureFeature`, `EnsureActiveSubscription`) to restrict access based on subscription status and feature availability.
- **Expiration handling**: Console commands to automatically expire subscriptions and notify users before expiry.
- **Discount coupon validation**: Public endpoint to validate coupon codes with real-time pricing calculations.

### 11. Calls & Emails

The platform provides contact information integration allowing users to initiate calls and send emails to property publishers and experts directly from property listings.

### 12. Property Comparison

Users can compare multiple properties side-by-side to evaluate features, pricing, location, and specifications before making a decision.

### 13. Settings Section

A dedicated settings area allowing users to manage their profile, notification preferences, subscription details, and account configurations.

### 14. Experts Section

A curated directory of real estate professionals — including lawyers, engineers, and other experts — that users can search, browse, and contact directly through the platform's communication channels.

### 15. Communication Module

- **In-app notifications**: Database-backed notification history with read/unread tracking.
- **Notification management**: Mark individual or all notifications as read, delete notifications.
- **Broadcast channels**: Pusher channels for `user.{userId}`, `chat.{roomId}`, and `property.{propertyId}` events.
- **Chat authorization**: Server-side channel authentication ensuring only room participants receive messages.

### 16. Testing & Feature Testing

- **PHPUnit test suites**: 4 test suites — `Unit`, `Feature`, `UserModule` (Auth), `Modules` (all modules).
- **RealEstate tests**: Property CRUD, ad creation/archiving/restoring, ad group management, ad display logic, ad view/visit tracking, analytics/export — with permission-based access control validation.
- **Communication tests**: Chat API (room creation, messaging, authorization), notification API (list, unread count, mark read, delete), FCM token registration.
- **Auth tests**: Registration, login, logout, password reset — all with Sanctum token verification.
- **Test factories**: Model factories for all major entities using Faker for realistic test data.
- **SQLite in-memory database**: Tests run on isolated in-memory SQLite for speed and reproducibility.

### 17. Docker Development Environment

- **Nginx + PHP-FPM + MySQL 8.0 + Redis** stack via docker-compose.
- Separate ports: Nginx on 8000, MySQL on 3307, Redis on 6380.
- Custom PHP Dockerfile with required extensions.

### 18. API Documentation (Scribe)

- Automatically generated interactive API documentation.
- OpenAPI 3.0 and Postman collection export.
- Bearer token authentication in the docs interface.
- Try-it-out functionality for testing endpoints directly from the browser.
