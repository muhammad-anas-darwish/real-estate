# Real-time Communication Module

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        API Layer                                 │
│  /api/v1/chat/*         /api/v1/notifications/*    /api/v1/fcm/* │
└──────────────────────┬──────────────────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┬─────────────────┐
        ▼              ▼              ▼                 │
│  ChatService    NotificationService  FcmService   │
│  (Business Logic)                               │
└──────────┬────────────────────────────────────┘
           │
    ┌──────┴──────┐
    ▼             ▼
┌─────────┐ ┌──────────┐
│ Pusher  │ │   FCM    │
│(real-time)│ │(push)   │
└─────────┘ └──────────┘
```

## When to Use Pusher vs FCM

### Pusher (Real-time)
- **Online users**: User has app open in foreground
- **Message sent**: Instant delivery
- **Typing indicator**: Real-time typing status
- **Presence**: See who's online

**Channels**:
- `user.{userId}` - Private notifications
- `chat.{roomId}` - Chat room messages
- `property.{propertyId}` - Property viewers

### FCM (Push Notifications)
- **Offline users**: App closed/not in foreground
- **Offline message**: Deliver via push
- **Important alerts**: High priority, retries

**Priority**:
- `HIGH`: New messages, offers, bookings
- `NORMAL`: Property updates, general notices

## Queue System

```
┌─────────────────────┐     ┌─────────────────────┐
│ notifications-high │────▶│ FCM (3 retries)     │
│ (time-sensitive)   │     │ backoff: 10s        │
└─────────────────────┘     └─────────────────────┘
         │
         ▼
┌─────────────────────┐     ┌─────────────────────┐
│ notifications-low  │────▶│ Database writes    │
│ (background)       │     │ (async)           │
└─────────────────────┘     └─────────────────────┘
         │
         ▼
┌─────────────────────┐     ┌─────────────────────┐
│ chat               │────▶│ Pusher broadcast    │
│ (real-time)        │     │ (no retry)         │
└─────────────────────┘     └─────────────────────┘
```

## Local Testing

### Pusher Debug Console

1. Open: https://dashboard.pusher.com
2. Select your app
3. Go to Debug Console
4. Subscribe to channel: `chat-1`
5. Send message via API, see event appear

**Event testing**:
```javascript
// Console subscription
var channel = pusher.subscribe('chat-1');
channel.bind('message.sent', function(data) {
  console.log('Message received:', data);
});
```

### FCM Testing

1. Download Firebase JSON credentials
2. Set in `.env`:
```
FCM_CREDENTIALS_PATH=/path/to/firebase-adminsdk.json
```
3. Test endpoint:
```bash
curl -X POST http://localhost:8000/api/v1/fcm/register \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"token": "TEST_TOKEN", "device_type": "android"}'
```

4. Send test notification via Firebase Console:
   - Firebase Console → Messaging → New campaign

### Events Testing

```bash
# Test chat message
php artisan test --filter=chat

# Test notification
php artisan test --filter=notification

# Test FCM
php artisan test --filter=fcm
```

## API Endpoints Summary

### Chat
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/chat/rooms` | List user's chat rooms |
| POST | `/api/v1/chat/rooms` | Create new chat |
| GET | `/api/v1/chat/rooms/{id}` | Get room details |
| GET | `/api/v1/chat/rooms/{id}/messages` | Get messages |
| POST | `/api/v1/chat/rooms/{id}/messages` | Send message |
| DELETE | `/api/v1/chat/rooms/{id}/messages/{msgId}` | Delete message |
| POST | `/api/v1/chat/rooms/{id}/typing` | Typing indicator |

### Notifications
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/notifications` | List notifications |
| GET | `/api/v1/notifications/unread-count` | Unread badge count |
| PATCH | `/api/v1/notifications/{id}/read` | Mark as read |
| PATCH | `/api/v1/notifications/read-all` | Mark all read |
| DELETE | `/api/v1/notifications/{id}` | Delete notification |

### FCM Tokens
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/fcm/register` | Register token |
| DELETE | `/api/v1/fcm/revoke` | Revoke token |

## Horizon Supervisors

```bash
# Start Horizon
php artisan horizon

# Production
php artisan horizon --supervisor=supervisor-chat --daemon
```

## Scheduled Jobs

| Command | Schedule | Description |
|--------|----------|-------------|
| `communication:clean-fcm-tokens` | Sundays 3am | Clean old tokens |
| CleanReadNotificationsJob | Daily 2am | Clean old notifications |

## Files Structure

```
Modules/Communication/
├── Config/communication.php       # Module config
├── Database/Migrations/         # Database
├── DTOs/                    # Data Transfer Objects
├── Entities/                 # Eloquent models
│   ├── ChatRoom.php
│   ├── Message.php
│   ├── UserFcmToken.php
│   └── HasFcmTokens.php
├── Enums/                    # Enumerations
├── Events/                   # Broadcast events
│   ├── MessageSentEvent.php
│   └── UserTypingEvent.php
├── Exceptions/               # Custom exceptions
├── Http/
│   ├── Controllers/
│   ├── Resources/
│   └── Requests/
├── Jobs/                     # Queue jobs
├── Listeners/               # Event listeners
├── Notifications/          # Notification classes
├── Policies/               # Authorization
├── Providers/              # Service providers
├── Routes/api.php         # API routes
└── Services/             # Business logic
    ├── ChatService.php
    ├── FcmService.php
    └── NotificationService.php
```

## Environment Variables

```env
# Pusher
PUSHER_APP_ID=
PUSHER_KEY=
PUSHER_SECRET=
PUSHER_CLUSTER=mt1

# Firebase Cloud Messaging
FCM_PROJECT_ID=
FCM_CREDENTIALS_PATH=

# Chat Attachments
CHAT_ATTACHMENT_DISK=local
```