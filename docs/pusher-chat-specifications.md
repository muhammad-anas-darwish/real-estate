# Pusher Chat Feature Specifications

## Overview

The chat feature uses Pusher Channels for real-time WebSocket communication. This document outlines the specifications for implementing and integrating the chat functionality.

## Configuration

### Environment Variables

The following environment variables are required (do not include actual values):

| Variable | Description |
|----------|-------------|
| `VITE_PUSHER_APP_KEY` | Pusher app key |
| `VITE_PUSHER_APP_CLUSTER` | Pusher cluster (e.g., `ap2`) |
| `PUSHER_APP_ID` | Pusher app ID |
| `PUSHER_APP_SECRET` | Pusher app secret |

### Frontend Configuration

In `resources/js/echo.js`, Pusher is initialized with:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true,
    authEndpoint: '/api/broadcasting/auth',
});
```

## Channels

### Chat Room Channels

Messages are broadcast on private channels per chat room:

- **Channel Format**: `chat.{roomId}` (Private Channel)
- **Event Name**: `message.sent`

### Authorization

Private channels require authentication. The backend provides authorization via `ChannelAuthService`.

**Authorization Endpoint**: `POST /api/broadcasting/auth`

The request must include:
- `socket_id`: The Pusher socket ID
- `channel_name`: The channel being subscribed to

### User Notification Channel

For user-specific real-time notifications:

- **Channel Format**: `user.{userId}` (Private Channel)

## Events

### MessageSentEvent

Triggered when a new message is sent in a chat room.

**Channel**: `chat.{roomId}`

**Event**: `message.sent`

**Payload**:

```json
{
  "message_id": 123,
  "room_id": 1,
  "sender": {
    "id": 2,
    "name": "John Doe",
    "avatar_url": "https://..."
  },
  "body": "Hello!",
  "type": "text",
  "attachment": {
    "url": "https://...",
    "name": "file.pdf",
    "type": "application/pdf"
  },
  "created_at": "2026-05-03T10:30:00Z"
}
```

### Message Types

| Type | Description |
|------|-------------|
| `text` | Plain text message |
| `image` | Image message with URL |
| `file` | File attachment |

## Frontend Implementation

### Subscribing to Chat Room

```javascript
import Echo from '../echo';

// Subscribe to a chat room
const channel = Echo.private(`chat.${roomId}`);

// Listen for new messages
channel.listen('message.sent', (data) => {
    console.log('New message:', data.message);
    // Update UI with new message
});

// Listen for typing indicators (if implemented)
channel.listen('typing', (data) => {
    // Show typing indicator
});
```

### Unsubscribing

```javascript
Echo.leave(`chat.${roomId}`);
```

### Presence Channel (Optional)

For features like "who is online":

```javascript
const channel = Echo.join(`chat.${roomId}`);

channel
    .here((users) => {
        console.log('Online users:', users);
    })
    .joining((user) => {
        console.log(`${user.name} joined`);
    })
    .leaving((user) => {
        console.log(`${user.name} left`);
    });
```

## API Endpoints

### Chat Rooms

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/chat/rooms` | List all chat rooms for the authenticated user |
| `POST` | `/api/chat/rooms` | Create a new chat room |
| `GET` | `/api/chat/rooms/{roomId}` | Get a specific chat room |

### Messages

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/chat/rooms/{roomId}/messages` | Get paginated messages for a room |
| `POST` | `/api/chat/rooms/{roomId}/messages` | Send a new message |
| `DELETE` | `/api/chat/rooms/{roomId}/messages/{messageId}` | Delete a message |

### Typing Indicator

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/chat/rooms/{roomId}/typing` | Send typing indicator |

## Data Models

### ChatRoom

```json
{
  "id": 1,
  "type": "private",
  "name": null,
  "property_id": 5,
  "property": {
    "id": 5,
    "title": "Luxury Apartment"
  },
  "participants": [
    {
      "id": 2,
      "name": "John Doe",
      "avatar_url": "https://..."
    }
  ],
  "last_message": {
    "id": 123,
    "body": "Hello!",
    "created_at": "2026-05-03T10:30:00Z"
  },
  "unread_count": 3,
  "created_at": "2026-04-01T00:00:00Z",
  "updated_at": "2026-05-03T10:30:00Z"
}
```

### Message

```json
{
  "id": 123,
  "room_id": 1,
  "sender": {
    "id": 2,
    "name": "John Doe",
    "avatar_url": "https://..."
  },
  "body": "Hello!",
  "type": "text",
  "attachment": null,
  "read_at": null,
  "created_at": "2026-05-03T10:30:00Z"
}
```

## Error Handling

### Connection Errors

```javascript
Echoconnector.pusher.connection.bind('error', (err) => {
    console.error('Pusher connection error:', err);
});
```

### Connection States

Monitor connection state changes:

```javascript
Echoconnector.pusher.connection.bind('state_change', (states) => {
    console.log('Connection state:', states.current);
    // states.current can be: 'connected', 'connecting', 'disconnected', 'unavailable'
});
```

## Best Practices

1. **Reconnection**: Pusher handles reconnection automatically, but implement UI feedback for users
2. **Unsubscribe**: Always unsubscribe when leaving chat rooms to prevent memory leaks
3. **Queue Messages**: If connection is lost, queue messages locally and send when reconnected
4. **Rate Limiting**: Be mindful of typing indicator events to avoid flooding the channel
5. **Authentication**: Ensure the user is authenticated before subscribing to private channels

## Queue Configuration

Chat-related jobs are processed via the `chat` queue. Configure in `config/communication.php`:

```php
'queue' => [
    'chat' => 'chat-queue-connection',
],
```

## Testing

### Testing Pusher Locally

Use Pusher debug console to verify messages are being sent correctly.

### Simulating Messages

You can broadcast test events using Laravel tinker:

```php
event(new \Modules\Communication\Events\MessageSentEvent($message));
```