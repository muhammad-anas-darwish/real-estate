# Enums Reference

All enums are native PHP 8.1+ string-backed enums. Every field sent/received via API uses the **value** (lowercase snake_case string), not the case name.

---

## RealEstate Module

### PropertyType
`apartment` | `house` | `villa` | `land` | `commercial` | `office` | `warehouse` | `other`

### TypeOfContract
`sale` | `rent`

### PropertyStatus
`pending` | `under_inspection` | `approved` | `rejected` | `suspended` | `sold` | `archived` | `draft`

### ViewingStatus
`pending` | `confirmed` | `rescheduled` | `cancelled` | `completed` | `no_show`

### ViewingType
`in_person` | `virtual` | `open_house`

### AdStatus
`draft` | `active` | `paused` | `archived`

### AdType
`banner` | `sponsored`

### AdMediaType
`video` | `image`

### PricingTier
| Tier | Rotation Weight | Prices (7d / 14d / 30d) |
|------|----------------|--------------------------|
| `basic` | 1 | 4.99 / 9.99 / 14.99 |
| `standard` | 2 | 9.99 / 19.99 / 29.99 |
| `premium` | 4 | 19.99 / 39.99 / 59.99 |

### SponsorDuration
| Value | Days |
|-------|------|
| `days_7` | 7 |
| `days_14` | 14 |
| `days_30` | 30 |

---

## Auth Module

### UserStatus
`active` | `inactive`

### PublisherType
`individual` | `office`

### ContactPreference
`chat` (Internal Chat) | `external` (External Contact — show phone/email)

---

## Communication Module

### RoomTypeEnum
`private` | `group`

### MessageTypeEnum
`text` | `image` | `file`

### NotificationTypeEnum
`new_message` | `new_offer` | `property_update` | `booking_confirmed` | `admin_alert` | `subscription_expiring`

### ConversationType
`property_inquiry` | `general`

### DeviceTypeEnum
`android` | `ios` | `web`

---

## Subscription Module

### FeatureType
`toggle` (on/off boolean) | `limit` (numeric limit)

### SubscriptionStatus
`pending` | `active` | `expired` | `cancelled`

### DiscountType
`percentage` | `fixed`

---

## ServiceProvider Module

### ServiceProviderType
`photographer` | `lawyer` | `inspector` | `marketer` | `other`

### ServiceRequestStatus
`pending` | `accepted` | `in_progress` | `completed` | `cancelled` | `rejected`

**Transition rules** (`canTransitionTo`):
```
PENDING      → ACCEPTED | REJECTED | CANCELLED
ACCEPTED     → IN_PROGRESS | CANCELLED | REJECTED
IN_PROGRESS  → COMPLETED | CANCELLED
COMPLETED    → (terminal)
CANCELLED    → (terminal)
REJECTED     → (terminal)
```

### PricingType
`fixed` | `hourly` | `negotiable`

### ServiceType
`photography` | `inspection` | `legal` | `marketing`

### ServiceTaskType
`photo_upload` | `checklist` | `report` | `verify`

---

## Ledger Module

### JournalEntryStatus
`draft` | `posted` | `reversed`

### AccountCategory
| Value | Normal Balance |
|-------|---------------|
| `asset` | debit |
| `liability` | credit |
| `equity` | credit |
| `revenue` | credit |
| `expense` | debit |

### AccountType
`user_balance` | `revenue` | `clearing` | `platform_fee` | `liability` | `expense`

### EntryType
`debit` | `credit` (has `opposite()` method)

### PayrollPaymentStatus
`pending` | `paid` | `failed`

### PayrollType
`monthly` | `per_task` | `both`
