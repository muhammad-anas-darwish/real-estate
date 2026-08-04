# Entity Relationship Overview

Text-based ERD describing all models and their relationships.

---

## Auth Module

```
User
  ├── belongsToMany → Role (Spatie, via model_has_roles)
  ├── hasMany → PublisherUpgradeRequest (user_id)
  ├── hasMany → Review (reviewer_id, reviewed_id)
  ├── hasMany → Property (publisher_id, polymorphic publisher_type)
  ├── hasMany → ChatRoom (via chat_room_user pivot)
  ├── hasMany → Message (sender_id)
  ├── hasMany → PropertyViewing (user_id, agent_id)
  ├── hasMany → Subscription (user_id)
  ├── hasOne  → ServiceProviderProfile (user_id)
  ├── hasMany → FcmToken (user_id)
  ├── belongsToMany → Property (lovedProperties pivot)
  ├── morphOne → Account (owner, ledger)
  └── hasMany → Ad (created_by/user_id)

Role (Spatie)
  ├── belongsToMany → User
  └── belongsToMany → Permission

PublisherUpgradeRequest
  ├── belongsTo → User (user_id)
  └── belongsTo → User (reviewed_by)
```

---

## RealEstate Module

```
Property
  ├── belongsTo → User (publisher, polymorphic)
  ├── belongsTo → Country (country_id)
  ├── belongsTo → City (city_id)
  ├── belongsTo → User (approver, approved_by)
  ├── belongsToMany → User (favoritedBy pivot)
  ├── hasMany → Ad (property_id)
  ├── hasMany → PropertyView (property_id)
  ├── hasMany → PropertyViewing (property_id)
  ├── hasMany → ServiceRequest (property_id)
  └── morphMany → Media (Spatie Media Library)

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

AdMedia
  └── belongsTo → Ad (ad_id)

AdView
  ├── belongsTo → Ad (ad_id)
  └── belongsTo → User (user_id, nullable)

AdVisit (same as AdView)

PropertyView
  ├── belongsTo → Property (property_id)
  └── belongsTo → User (user_id, nullable)

PropertyViewing
  ├── belongsTo → Property (property_id)
  ├── belongsTo → User (user_id, viewer)
  ├── belongsTo → User (agent_id, agent)
  └── belongsTo → User (cancelled_by, nullable)

Review
  ├── belongsTo → User (reviewer, reviewer_id)
  ├── belongsTo → User (reviewed, reviewed_id)
  └── belongsTo → Property (property_id, nullable)
```

---

## Communication Module

```
ChatRoom
  ├── belongsToMany → User (participants, with pivot)
  ├── hasMany → Message (room_id)
  ├── hasOne  → Message (lastMessage, via latest)
  └── belongsTo → Property (property_id, nullable)

Message
  ├── belongsTo → ChatRoom (room_id)
  ├── belongsTo → User (sender, sender_id)
  ├── belongsTo → Message (parent, parent_id, nullable)
  └── hasMany → Message (replies, parent_id)

Conversation
  ├── belongsTo → Property (property_id)
  ├── belongsTo → User (initiator, initiator_id)
  ├── belongsTo → User (recipient, recipient_id)
  ├── hasMany → Message (via morph?)
  └── hasOne  → Message (lastMessage)

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
  ├── belongsToMany → SubscriptionFeature (via plan_features pivot)
  ├── hasMany → SubscriptionPlanFeature (plan_id)
  ├── hasMany → Subscription (plan_id)
  └── hasMany → SubscriptionDiscount (plan_id)

SubscriptionFeature
  ├── belongsToMany → SubscriptionPlan (via plan_features pivot)
  ├── hasMany → SubscriptionPlanFeature (feature_id)

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
  ├── belongsTo → ServiceProviderProfile (provider, provider_id, nullable)
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
  (manual morph via reference_type/reference_id)

JournalEntry
  ├── hasMany → JournalEntryLine (journal_entry_id)
  ├── belongsTo → User (created_by, created_by_id)
  └── belongsTo → User (posted_by, posted_by_id)

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

## Core Module

```
Category
  (standalone — lookup table)

Country
  └── hasMany → City (country_id)

City
  ├── belongsTo → Country (country_id)
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
| AccountEntry | `reference` | JournalEntry, PayrollPayment, etc. |

---

## Pivot Tables

| Pivot | Table | Columns |
|-------|-------|---------|
| User ↔ Role | `model_has_roles` | role_id, model_type, model_id |
| User ↔ Property (loved) | `property_user` (or similar) | user_id, property_id |
| User ↔ ChatRoom | `chat_room_user` | room_id, user_id, (timestamps) |
| Plan ↔ Feature | `subscription_plan_features` | plan_id, feature_id, is_enabled, limit_value |
