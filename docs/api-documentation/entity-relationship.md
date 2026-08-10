# Entity Relationship Overview

Text-based ERD describing all models and their relationships across the 12 modules of the platform. Based on the actual entities and migrations in the current codebase.

---

## Auth Module

```
User
  ├── belongsToMany → Role (Spatie, via model_has_roles)
  ├── belongsToMany → Property (lovedProperties, via property_user pivot)
  ├── hasMany → Property (publisher_id, polymorphic publisher_type)
  ├── hasMany → PropertyView (user_id)
  ├── hasMany → Review (reviewer_id, reviewed_id)
  ├── hasMany → Conversation (initiator_id / recipient_id)
  ├── hasMany → Message (sender_id)
  ├── hasMany → Appointment (user_id / agent_id / cancelled_by)
  ├── hasMany → Subscription (user_id)
  ├── hasMany → PublisherUpgradeRequest (user_id / reviewed_by)
  ├── hasMany → OtpCode (user_id)
  ├── hasMany → UserFcmToken (user_id)
  ├── hasMany → UserNotificationPreference (user_id)
  ├── hasMany → RentalCard (owner_id / tenant_user_id / ended_by)
  ├── hasMany → Lead (trader_id / assigned_to)
  ├── hasMany → LeadNote (author_id)
  ├── hasOne  → ServiceProviderProfile (user_id)
  ├── hasOne  → StorageLimit (user_id)
  ├── hasMany → UserFolder / UserFile (user_id)
  ├── morphOne → Account (owner, ledger)
  └── morphMany → Media (Spatie Media Library)

Role (Spatie)
  ├── belongsToMany → User
  └── belongsToMany → Permission

PublisherUpgradeRequest
  ├── belongsTo → User (user_id)
  └── belongsTo → User (reviewed_by)

OtpCode
  └── belongsTo → User (user_id)
```

---

## RealEstate Module

```
Property
  ├── belongsTo → User (publisher, publisher_id)
  ├── belongsTo → User (approver, approved_by)
  ├── belongsTo → Country (country_id)
  ├── belongsTo → City (city_id)
  ├── belongsTo → Category (category_id)
  ├── belongsToMany → User (favoritedBy, property_user pivot)
  ├── hasMany → PropertyView (property_id)
  ├── hasMany → Appointment (property_id)
  ├── hasMany → RentalCard (property_id)
  ├── hasOne  → RentalCard (activeRentalCard)
  ├── hasMany → Ad (property_id)
  ├── hasMany → ServiceRequest (property_id)
  ├── hasMany → Deposit (property_id)
  └── morphMany → Media (Spatie Media Library)

Appointment  (replaces the old PropertyViewing; includes viewings + follow-ups + general)
  ├── belongsTo → Property (property_id)
  ├── belongsTo → User (user_id, requester)
  ├── belongsTo → User (agent_id, agent)
  ├── belongsTo → User (cancelled_by, nullable)
  └── morphTo   → followable (Lead, Property, etc.)

RentalCard
  ├── belongsTo → Property (property_id)
  ├── belongsTo → User (owner_id, owner)
  ├── belongsTo → User (tenant_user_id, tenant)
  └── belongsTo → User (ended_by, nullable)

PropertyView
  ├── belongsTo → Property (property_id)
  └── belongsTo → User (user_id, nullable)

Review
  ├── belongsTo → User (reviewer, reviewer_id)
  ├── belongsTo → User (reviewed, reviewed_id)
  └── belongsTo → Property (property_id, nullable)

Ad
  ├── belongsTo → AdGroup (ad_group_id)
  ├── belongsTo → Property (property_id, nullable)
  ├── belongsTo → User (creator, created_by)
  ├── belongsTo → User (user_id, for sponsored)
  ├── hasMany → AdMedia (ad_id)
  ├── hasMany → AdView (ad_id)
  └── hasMany → AdVisit (ad_id)

AdGroup
  ├── hasMany → Ad (ad_group_id)
  └── belongsTo → User (creator, created_by)

AdMedia   → belongsTo → Ad (ad_id)
AdView    → belongsTo → Ad + belongsTo → User (nullable)
AdVisit   → belongsTo → Ad + belongsTo → User (nullable)
```

---

## Communication Module

```
ChatRoom
  ├── belongsTo → Property (property_id, nullable)
  ├── belongsToMany → User (participants, via chat_room_participants pivot)
  └── hasMany → Message (room_id)

Message
  ├── belongsTo → ChatRoom (room_id)
  ├── belongsTo → User (sender, sender_id)
  ├── belongsTo → Message (parent, parent_id, nullable)
  └── hasMany → Message (replies, parent_id)

Conversation  (direct 1-1 chat between two users about a property)
  ├── belongsTo → Property (property_id)
  ├── belongsTo → User (initiator, initiator_id)
  ├── belongsTo → User (recipient, recipient_id)
  ├── hasMany → Message (via conversation)
  └── hasMany → Message (lastMessage, via latest)

UserFcmToken
  └── belongsTo → User (user_id)

UserNotificationPreference
  └── belongsTo → User (user_id)
```

---

## Subscription Module

```
Subscription
  ├── belongsTo → User (user_id)
  ├── belongsTo → SubscriptionPlan (plan_id)
  ├── belongsTo → SubscriptionDiscount (discount_id, nullable)
  └── hasMany → SubscriptionStatusLog (subscription_id)

SubscriptionPlan
  ├── belongsToMany → SubscriptionFeature (via subscription_plan_features pivot)
  ├── hasMany → SubscriptionPlanFeature (plan_id)
  ├── hasMany → Subscription (plan_id)
  └── hasMany → SubscriptionDiscount (plan_id)

SubscriptionFeature
  ├── belongsToMany → SubscriptionPlan (via plan_features pivot)
  └── hasMany → SubscriptionPlanFeature (feature_id)

SubscriptionPlanFeature (pivot extension)
  ├── belongsTo → SubscriptionPlan (plan_id)
  └── belongsTo → SubscriptionFeature (feature_id)

SubscriptionDiscount
  ├── belongsTo → SubscriptionPlan (plan_id, nullable)
  └── hasMany → Subscription (discount_id)

SubscriptionStatusLog
  └── belongsTo → Subscription (subscription_id)

StripeEvent
  (standalone — stores raw Stripe webhook events)
```

---

## ServiceProvider Module

```
ServiceProviderProfile
  ├── belongsTo → User (user_id)
  ├── hasMany → ServiceProviderCoverageArea (profile_id)
  ├── hasMany → ServiceRequest (provider_id)
  └── hasMany → Payroll (service_provider_profile_id)

ServiceRequest
  ├── belongsTo → User (client, client_id)
  ├── belongsTo → User (provider, provider_id)
  ├── belongsTo → Property (property_id)
  └── hasMany → ServiceRequestTask (service_request_id)

ServiceRequestTask
  └── belongsTo → ServiceRequest (service_request_id)

ServiceProviderCoverageArea
  ├── belongsTo → ServiceProviderProfile (profile_id)
  └── belongsTo → City (city_id)

ServiceCommissionConfig
  (standalone — configuration table)
```

---

## Ledger Module

```
Account
  ├── morphTo → owner (polymorphic: User, etc.)
  ├── belongsTo → Account (parent, parent_id, self-referencing)
  ├── hasMany → Account (children, parent_id)
  └── hasMany → AccountEntry (account_id)

AccountEntry
  └── belongsTo → Account (account_id)

JournalEntry
  ├── hasMany → JournalEntryLine (journal_entry_id)
  ├── belongsTo → User (created_by)
  └── belongsTo → User (posted_by)

JournalEntryLine
  ├── belongsTo → JournalEntry (journal_entry_id)
  └── belongsTo → Account (account_id)

Payroll
  ├── belongsTo → ServiceProviderProfile (service_provider_profile_id)
  └── hasMany → PayrollPayment (payroll_id)

PayrollPayment
  ├── belongsTo → Payroll (payroll_id)
  ├── belongsTo → ServiceProviderProfile (service_provider_profile_id)
  └── belongsTo → JournalEntry (journal_entry_id, nullable)
```

---

## Deposit Module (Escrow)

```
Deposit
  ├── belongsTo → Property (property_id)
  ├── belongsTo → User (buyer, buyer_id)
  ├── belongsTo → User (seller, seller_id)
  ├── belongsTo → User (cancelled_by, nullable)
  ├── belongsTo → User (released_by, nullable)
  └── belongsTo → User (refunded_by, nullable)
```

---

## CRM Module

```
Lead
  ├── belongsTo → User (trader, trader_id)
  ├── belongsTo → User (assigned_to, nullable)
  ├── hasMany → LeadNote (lead_id)
  └── morphMany → Appointment (followUps)

LeadNote
  ├── belongsTo → Lead (lead_id)
  └── belongsTo → User (author, author_id)
```

---

## FileSystem Module

```
UserFolder
  ├── belongsTo → User (user_id)
  ├── belongsTo → UserFolder (parent, parent_id, nullable)
  ├── hasMany → UserFolder (children, parent_id)
  └── hasMany → UserFile (folder_id)

UserFile
  ├── belongsTo → User (user_id)
  └── belongsTo → UserFolder (folder_id, nullable)

StorageLimit
  └── belongsTo → User (user_id)
```

---

## Core Module

```
Category
  ├── belongsTo → Category (parent, self-referencing)
  └── hasMany → Category (children)

Country
  └── hasMany → City (country_id)

City
  ├── belongsTo → Country (country_id)
  ├── hasMany → Property (city_id)
  └── hasMany → ServiceProviderCoverageArea (city_id)

TemporaryFile
  └── morphMany → Media (Spatie Media Library)
```

---

## Key Polymorphic Relations

| Model | Morph Name | Related To |
|-------|-----------|------------|
| User (ledger) | `owner` | Account |
| Property | (Spatie) | Media |
| TemporaryFile | (Spatie) | Media |
| Appointment | `followable` | Lead, Property, ... |
| Notification | `notifiable` | User, ... |

---

## Pivot Tables

| Pivot | Table | Columns |
|-------|-------|---------|
| User ↔ Role | `model_has_roles` | role_id, model_type, model_id |
| User ↔ Permission | `model_has_permissions` | permission_id, model_type, model_id |
| Role ↔ Permission | `role_has_permissions` | role_id, permission_id |
| User ↔ Property (loved) | `property_user` | user_id, property_id |
| User ↔ ChatRoom | `chat_room_participants` | chat_room_id, user_id |
| Plan ↔ Feature | `subscription_plan_features` | plan_id, feature_id, value |
