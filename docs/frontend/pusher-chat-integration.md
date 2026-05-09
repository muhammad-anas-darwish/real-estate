# Real-Time Chat via Pusher — Frontend Integration

## Pusher Credentials

| Variable | Value |
|----------|-------|
| `PUSHER_APP_KEY` | `ed7888c4a56cc56f47ca` |
| `PUSHER_APP_CLUSTER` | `ap1` |
| `PUSHER_APP_ID` | `2149211` |
| `PUSHER_APP_SECRET` | `77b1ec9bc70687b9d062` |
| `PUSHER_PORT` | `443` |
| `PUSHER_SCHEME` | `https` |
| `PUSHER_HOST` | `api-ap1.pusher.com` (derived from cluster) |
| `BROADCAST_CONNECTION` | `pusher` |
| `BROADCAST_DRIVER` | `redis` |

## Echo Configuration

Echo is already set up in `resources/js/echo.js` using Vite env variables. The resolved values are:

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: "ed7888c4a56cc56f47ca",
    cluster: "ap1",
    forceTLS: true,
    wsHost: "",
    wsPort: 443,
    wssPort: 443,
    enabledTransports: ["ws", "wss"],
});
```

## Authentication

All broadcasting channels are **private** and require authentication via `auth:sanctum`.

### Broadcasting Auth Endpoint

- **URL:** `POST /broadcasting/auth`
- **Headers:** `Authorization: Bearer {sanctum_token}`
- **Body (URL-encoded or JSON):**
  ```json
  {
    "socket_id": "123456.7890123",
    "channel_name": "private-chat.5"
  }
  ```

Laravel Echo handles this automatically — just ensure `axios` has the `Authorization` header set globally:

```js
window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
```

The CSRF token must also be available (usually via meta tag or cookie). Sanctum handles SPA auth via cookies if using the same domain; otherwise use API tokens.

## Channels

### 1. Chat Room — `private-chat.{roomId}`

Used for real-time messaging within a specific room. The user must be a participant of the room to subscribe.

**Events listened on this channel:**

#### a) New Message — `message.sent`

Broadcast by `MessageSentEvent` (`Modules\Communication\Events\MessageSentEvent`)

Payload:
```json
{
  "message_id": 123,
  "room_id": 5,
  "sender": {
    "id": 1,
    "name": "John Doe"
  },
  "body": "Hello!",
  "type": "text",
  "created_at": "2026-05-08T10:00:00+03:00"
}
```

- `type` can be: `text`, `image`, `file`
- `created_at` is ISO 8601 format
- The event implements `ShouldBroadcastNow` (instant, no queue)

#### b) User Typing — `user.typing`

Broadcast by `UserTypingEvent` (`Modules\Communication\Events\UserTypingEvent`)

Payload:
```json
{
  "user_id": 1,
  "user_name": "John Doe",
  "is_typing": true
}
```

- This event uses the `broadcast` queue
- The typing API endpoint is throttled to 1 request/second

### 2. User Notifications — `private-user.{userId}`

Used for real-time push notifications (admin alerts, booking confirmations, new offers, etc.).

```js
Echo.private(`user.${userId}`)
    .notification((notification) => {
        // Handle in-app notification
    });
```

The payload contains the notification data from the server-side `BroadcastableNotification`.

### 3. Property Updates — `private-property.{propertyId}`

Used for real-time property status changes and other property-related broadcasts.

## Listening in JavaScript (Vue / React)

### Subscribe to a Chat Room

```js
Echo.private(`chat.${roomId}`)
    .listen('.message.sent', (e) => {
        // Append message to local state
        messages.push({
            id: e.message_id,
            roomId: e.room_id,
            sender: e.sender,
            body: e.body,
            type: e.type,
            createdAt: e.created_at,
            isMine: e.sender.id === currentUserId,
        });
    })
    .listen('.user.typing', (e) => {
        if (e.user_id !== currentUserId) {
            showTypingIndicator(e.user_name);
            setTimeout(() => hideTypingIndicator(e.user_id), 3000);
        }
    });
```

### Leave a Channel (when navigating away)

```js
Echo.leave(`chat.${roomId}`);
```

### Listen to User Notifications

```js
Echo.private(`user.${userId}`)
    .notification((notification) => {
        // Update unread count, show toast, etc.
        unreadCount.value++;
        notifications.unshift(notification);
    });
```

## Chat API Endpoints

All endpoints require `auth:sanctum`. Base URL: `http://localhost:8000`

| Method | Endpoint | Description | Body / Params |
|--------|----------|-------------|---------------|
| `GET` | `/api/chat/rooms` | List user's chat rooms (paginated) | — |
| `POST` | `/api/chat/rooms` | Create a new chat room | `{ type: "private", recipient_id: 2 }` or `{ type: "property", property_id: 5 }` |
| `GET` | `/api/chat/rooms/{roomId}` | Get room details with participants | — |
| `GET` | `/api/chat/rooms/{roomId}/messages` | Get room messages (paginated, 20 per page) | `?page=1` |
| `POST` | `/api/chat/rooms/{roomId}/messages` | Send a message | `{ body: "text", type: "text", parent_id?: null }` or multipart with `attachment` file |
| `DELETE` | `/api/chat/rooms/{roomId}/messages/{messageId}` | Delete own message | — |
| `POST` | `/api/chat/rooms/{roomId}/typing` | Broadcast typing indicator | (empty body, throttled to 1/sec) |

### Send Message Response

```json
{
  "success": true,
  "message": "message created",
  "data": {
    "id": 123,
    "room_id": 5,
    "sender_id": 1,
    "body": "Hello!",
    "type": "text",
    "is_mine": true,
    "is_read": false,
    "attachment_url": null,
    "parent_message": null,
    "created_at": "2026-05-08T10:00:00+03:00",
    "read_at": null,
    "sender": { ... }
  }
}
```

### Chat Room Response

```json
{
  "id": 5,
  "type": "private",
  "name": null,
  "property_id": null,
  "participants": [
    { "id": 1, "name": "John Doe", ... },
    { "id": 2, "name": "Jane Smith", ... }
  ]
}
```

## Important Notes

1. **Queue Worker:** The backend must have `php artisan queue:work` running for queued events (typing, notifications). Redis is the queue driver.
2. **Message Attachment:** For file attachments, send a `multipart/form-data` request with the `attachment` field (max 20MB).
3. **Typing Indicator:** The typing endpoint is throttled to 1 request/second — implement client-side debouncing (e.g., fire only every 2-3 seconds).
4. **Sanctum Token:** Pass the token in the `Authorization: Bearer` header for all API requests. Echo uses Axios internally for auth, so setting the header globally on `window.axios` is sufficient.
5. **CSRF Protection:** For Sanctum SPA auth, include the CSRF token from the `XSRF-TOKEN` cookie in the `X-XSRF-TOKEN` header (Axios does this automatically).
6. **Event Name Dot Prefix:** When using `.listen('.message.sent')`, the leading dot is required because Laravel prefixes events with `Illuminate\\Broadcasting\\BroadcastEvent`. Echo strips the dot and listens for the short event name.
