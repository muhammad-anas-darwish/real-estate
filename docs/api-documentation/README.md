# Real Estate Platform — API Documentation Package

This package contains everything frontend and mobile developers need to build the client applications.

---

## Files Included

| File | Purpose |
|------|---------|
| **`openapi.yaml`** | Complete OpenAPI 3.0.3 specification — all endpoints, request/response schemas, enums. Import into Postman, Swagger UI, or any API client. |
| **`application-scenarios.md`** | Business logic reference — user roles, all flows, state machines, feature descriptions, every scenario in the application. |
| **`auth-flow.md`** | Authentication details — login, register, 2FA, password reset, token management, headers, rate limits. |
| **`api-response-format.md`** | Standard response formats, error formats, filtering query params, real-time events, FCM push setup. |
| **`enums-reference.md`** | Every enum used in the API — exact string values to send/receive for every field. |
| **`permissions-reference.md`** | Complete permission matrix — all permission strings required for admin/role management. |
| **`entity-relationship.md`** | Database entity relationships — models, foreign keys, polymorphic relations, pivot tables. |

---

## Quick Start

1. **Import OpenAPI spec**: Load `openapi.yaml` into Postman, Insomnia, or any OpenAPI-compatible tool
2. **Auth first**: Implement `POST /auth/register` and `POST /auth/login` — all protected endpoints need `Authorization: Bearer {token}`
3. **Read scenarios**: `application-scenarios.md` explains every flow and when to call which endpoint
4. **Check enums**: `enums-reference.md` has all valid values for every enum field

---

## Auth Flow Summary

1. `POST /auth/register` → `{ user, token }`
2. Store token, add header: `Authorization: Bearer {token}`
3. `GET /user` → verify token works
4. Token never expires, but logout deletes all tokens
5. Support 2FA flow if returned by login

---

## API Base URL

- Development: `http://localhost:8000`
- Production: `https://api.yourdomain.com`

---

## Real-Time

- **Pusher** for WebSocket events (chat messages, typing, notifications)
- **FCM** for push notifications when app is in background
- Register device: `POST /api/fcm/register { token, device_type }`

---

## Key Facts

- **150+ endpoints** across 7 modules
- **36 models/entities**
- **33 enums**
- **120+ permission strings**
- Auth: Sanctum API tokens (Bearer)
- Payments: Stripe
- Media: Spatie Media Library
- Accounting: Double-entry ledger
