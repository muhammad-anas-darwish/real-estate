# API Response Format

All API endpoints follow a consistent response format.

---

## Success Response

```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful"
}
```

For creation/update/deletion, the message is localized:
```json
{
  "success": true,
  "data": { ... },
  "message": "Property created successfully."
}
```

HTTP status codes are used correctly:
- `200` — Success (read/update)
- `201` — Created (with `->created('model')` in chain)
- `204` — No content (deletion, when configured)

---

## Paginated Response

```json
{
  "success": true,
  "data": [
    { ... },
    { ... }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72,
    "from": 1,
    "to": 15
  }
}
```

---

## Error Responses

### Validation Error (422)
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email has already been taken."],
    "price": ["The price must be at least 0."]
  }
}
```

### Authentication Error (401)
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
  "success": false,
  "message": "You are not authorized to perform this action."
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Resource not found."
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "An unexpected error occurred."
}
```

---

## Date Format

All timestamps are returned in: `Y-m-d H:i:s` (e.g. `"2026-08-02 14:30:00"`)

---

## Query Parameters for Filtering

Used across list endpoints (via Filterable trait on models):

| Parameter | Example | Description |
|-----------|---------|-------------|
| `search` | `?search=villa` | LIKE search on searchable columns |
| `status` | `?status=active` | Exact match filter |
| `property_type` | `?property_type=apartment` | Exact match |
| `country_id` | `?country_id=3` | Exact match |
| `id` | `?id=1,2,3` | WHERE IN (multi-value) |
| `created_at[from]` | `?created_at[from]=2026-01-01` | Date range start |
| `created_at[to]` | `?created_at[to]=2026-12-31` | Date range end |
| `perPage` | `?perPage=50` | Items per page (max 100) |
| `page` | `?page=2` | Page number |
| `sort_by` | `?sort_by=price` | Sort column |
| `sort_order` | `?sort_order=desc` | Sort direction |

---

## Rate Limiting

| Area | Limit |
|------|-------|
| Chat endpoints | 60 requests/minute |
| Typing indicator | 1 request/second |
| Notifications index | 60 requests/minute |
| Unread count | 120 requests/minute |
| Mark all read | 10 requests/minute |
| Login | 5 attempts/minute |
| Email verification | 6 requests/minute |

---

## Real-Time Events (Pusher)

Broadcast channels (private, authenticated):
- `user.{userId}` — User-specific events
- `chat.{roomId}` — Chat room events
- `property.{propertyId}` — Property-related events

Events broadcast:
- `message.sent` — New chat message (MessageSentEvent)
- `user.typing` — User is typing (UserTypingEvent)
- `notification.received` — New notification (NotificationReceivedEvent)
- `subscription.status.changed` — Subscription status change (SubscriptionStatusChangedEvent)

---

## FCM Push Notifications

When user is offline, notifications are delivered via Firebase Cloud Messaging:
1. Frontend registers device token: `POST /api/fcm/register { token, device_type }`
2. Server sends push to FCM when relevant events occur
3. Token revoked on logout: `DELETE /api/fcm/revoke`

---

## Websocket Connection Flow

1. Authenticate via `POST /auth/login` → receive token
2. Frontend establishes Pusher connection
3. Subscribe to private channels
4. Auth callback verifies user identity via Sanctum
5. Real-time events flow as they happen
