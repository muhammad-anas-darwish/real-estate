# Permissions Reference

All permissions use the format: `{resource}.{action}`. Permissions are managed via Spatie Permission package with `web` guard. The `super-admin` role has ALL permissions.

> Generated from `database/seeders/PermissionSeeder.php` in the current codebase.

---

## Roles
```
roles.list                 — List all roles
roles.show                 — View a role
roles.create               — Create a role
roles.edit                 — Update a role
roles.delete               — Delete a role
roles.get-all-permissions  — Get complete permissions list
```

## Users
```
users.list                 — List all users
users.show                 — View a user
users.create               — Create a user
users.edit                 — Update a user
users.delete               — Delete a user
users.toggle-status        — Activate/deactivate a user
```

## Properties
```
properties.list            — List properties (dashboard)
properties.show            — View property detail (dashboard)
properties.create          — Create a property
properties.edit            — Update a property
properties.delete          — Delete a property
```

## Viewings (legacy alias → Appointments)
```
viewings.list              — List viewings
viewings.show              — View viewing detail
viewings.create            — Book a viewing
viewings.edit              — Edit a viewing
viewings.delete            — Delete a viewing
viewings.confirm           — Confirm a viewing
viewings.cancel            — Cancel a viewing
viewings.reschedule        — Reschedule a viewing
viewings.complete          — Mark viewing as completed
```

## Appointments (generalized viewings: viewing / follow-up / general)
```
appointments.list          — List appointments
appointments.show          — View appointment detail
appointments.create        — Book an appointment
appointments.edit          — Update an appointment
appointments.delete        — Delete an appointment
appointments.confirm       — Confirm an appointment
appointments.cancel        — Cancel an appointment
appointments.reschedule    — Reschedule an appointment
appointments.complete      — Mark appointment as completed
```

## Ad Groups
```
ad_groups.list             — List ad groups
ad_groups.show             — View ad group
ad_groups.create           — Create ad group
ad_groups.edit             — Update ad group
ad_groups.delete           — Delete ad group
ad_groups.archive          — Archive ad group
ad_groups.restore          — Restore archived ad group
ad_groups.set-default      — Set default ad for group
```

## Ads
```
ads.list                   — List ads
ads.show                   — View ad
ads.create                 — Create ad
ads.edit                   — Update ad
ads.delete                 — Delete ad
ads.archive                — Archive ad
ads.restore                — Restore archived ad
ads.set-status             — Change ad status
ads.link-property          — Link ad to property
ads.view-analytics         — View ad analytics
ads.export                 — Export analytics data
```

## Countries & Cities
```
countries.list             — List countries
countries.show             — View country
countries.create           — Create country
countries.edit             — Update country
countries.delete           — Delete country

cities.list                — List cities
cities.show                — View city
cities.create              — Create city
cities.edit                — Update city
cities.delete              — Delete city
```

## Subscription Plans
```
subscription_plans.list     — List plans
subscription_plans.show     — View plan
subscription_plans.create   — Create plan
subscription_plans.edit     — Update plan
subscription_plans.delete   — Delete plan
```

## Subscription Features
```
subscription_features.list     — List features
subscription_features.show     — View feature
subscription_features.create   — Create feature
subscription_features.edit     — Update feature
subscription_features.delete   — Delete feature
```

## Subscription Plan Features
```
subscription_plan_features.list     — List plan-feature links
subscription_plan_features.show     — View link
subscription_plan_features.create   — Create link
subscription_plan_features.edit     — Update link
subscription_plan_features.delete   — Delete link
```

## Subscription Discounts
```
subscription_discounts.list     — List discounts
subscription_discounts.show     — View discount
subscription_discounts.create   — Create discount
subscription_discounts.edit     — Update discount
subscription_discounts.delete   — Delete discount
```

## Offices & Publisher Management
```
offices.list                    — List offices
offices.show                    — View office
offices.verify                  — Verify an office
offices.unverify                — Unverify an office
offices.list-upgrade-requests   — List upgrade requests
offices.approve-upgrade         — Approve upgrade request
offices.reject-upgrade          — Reject upgrade request

publishers.list                 — List publishers
publishers.show                 — View publisher
```

## Reviews
```
reviews.list          — List reviews
reviews.show          — View review
reviews.delete        — Delete review
```

## Service Providers
```
service_providers.list     — List service providers
service_providers.show     — View service provider
service_providers.verify   — Verify a provider
service_providers.unverify — Unverify a provider
```

## Service Requests
```
service_requests.list     — List service requests (admin)
service_requests.show     — View service request (admin)
```

## Rental Cards
```
rental_cards.list         — List rental cards
rental_cards.show         — View rental card
rental_cards.create       — Create rental card
rental_cards.edit         — Update rental card
rental_cards.delete       — Delete rental card
rental_cards.end          — End a rental card
rental_cards.renew        — Renew a rental card
```

## CRM — Leads
```
leads.list                — List leads
leads.show                — View lead
leads.create              — Create lead
leads.edit                — Update lead
leads.delete              — Delete lead
leads.change-status       — Change lead status
leads.archive             — Archive lead
leads.restore             — Restore archived lead
leads.export              — Export leads
```

## CRM — Lead Notes
```
lead_notes.list           — List lead notes
lead_notes.show           — View lead note
lead_notes.create         — Create lead note
lead_notes.edit           — Update lead note
lead_notes.delete         — Delete lead note
```

## CRM — Lead Follow-ups
```
lead_follow_ups.list      — List follow-ups
lead_follow_ups.show      — View follow-up
lead_follow_ups.create    — Create follow-up
lead_follow_ups.edit      — Update follow-up
lead_follow_ups.delete    — Delete follow-up
lead_follow_ups.complete  — Complete a follow-up
```

## CRM — Dashboard
```
crm_dashboard.view        — View CRM dashboard
```

## Deposits (Escrow)
```
deposits.list             — List deposits
deposits.show             — View deposit
deposits.create           — Create deposit
deposits.edit             — Update deposit
deposits.delete           — Delete deposit
deposits.release          — Release deposit to seller
deposits.refund           — Refund deposit to buyer
```

## FileSystem
```
files.list                — List user files
files.show                — View file
files.create              — Create/upload file
files.edit                — Update file
files.delete              — Delete file
files.move                — Move file to folder
files.rename              — Rename file
files.quota               — View/manage storage quota
```

## Statistics
```
statistics.view           — View statistics dashboards
statistics.export         — Export statistics data
admin_statistics.view     — View admin statistics dashboards
```

## Ledger — Accounts
```
accounts.list          — List accounts
accounts.show          — View account
accounts.create        — Create account
accounts.edit          — Update account
accounts.delete        — Delete account
```

## Ledger — Journal Entries
```
journal_entries.list   — List journal entries
journal_entries.show   — View journal entry
journal_entries.create — Create journal entry
journal_entries.edit   — Update draft journal entry
journal_entries.delete — Delete draft journal entry
journal_entries.post   — Post (finalize) journal entry
```

## Ledger — Trial Balance & Payroll
```
trial_balance.view     — View trial balance report
payroll.list           — List payroll setups
payroll.manage         — Create/update payroll setups
payroll.run            — Run payroll processing
```

---

## Default Roles

| Role | Permissions |
|------|------------|
| **super-admin** | ALL permissions (from the seeder) |
| **trader** | `leads.*`, `lead_notes.*`, `lead_follow_ups.*`, `crm_dashboard.view`, `statistics.view`, `statistics.export` |
| *(other roles created by admin as needed)* | Assigned via `POST /api/roles/{id}/permissions` |

---

## Controller Permission Mapping

The `ApplyPermissions` trait in the base Controller maps CRUD methods to permissions:

| HTTP Method | Controller Method | Permission Suffix |
|-------------|------------------|-------------------|
| GET (index) | `index()` | `.list` |
| GET (single) | `show()` | `.show` |
| POST (store) | `store()` | `.create` |
| PUT/PATCH | `update()` | `.edit` |
| DELETE | `destroy()` | `.delete` |

Additional methods are mapped explicitly in `__construct()` via:
```php
$this->applyPermissions('resource_name', ['store', 'update', 'destroy'], [
    'customMethod' => 'permission_suffix',
]);
```

> Note: `training_categories` and `health_warnings` groups exist in the seeder for backward compatibility but are not used by any current module.
