# FCM Notifications Specifications

## Overview

Firebase Cloud Messaging (FCM) is used for push notifications to mobile devices (Android, iOS) and web browsers. This document outlines the specifications for implementing and integrating FCM notifications.

## Configuration

### Environment Variables

The following environment variables are required (do not include actual values):

| Variable | Description |
|----------|-------------|
| `FCM_SERVER_KEY` | Firebase server key for HTTP API |
| `FCM_PROJECT_ID` | Firebase project ID |
| `FCM_CREDENTIALS_PATH` | Path to Firebase service account JSON |

### Backend Configuration

FCM is configured in `config/broadcasting.php` and `Modules/Communication/Config/communication.php`:

```php
'fcm' => [
    'driver' => 'http',
    'project_id' => env('FCM_PROJECT_ID', ''),
    'server_key' => env('FCM_SERVER_KEY', ''),
    'credentials_path' => env('FCM_CREDENTIALS_PATH', ''),
],
```

## FCM Token Management

### Register Token

When a user logs into a mobile app or web app, register their FCM token:

**Endpoint**: `POST /api/fcm/register`

**Request Body**:

```json
{
  "token": "firebase-device-token",
  "device_type": "android|ios|web"
}
```

**Response**:

```json
{
  "success": true,
  "message": "Token registered successfully"
}
```

### Revoke Token

When a user logs out or removes the app, revoke their token:

**Endpoint**: `DELETE /api/fcm/revoke`

**Request Body**:

```json
{
  "token": "firebase-device-token"
}
```

## Notification Types

| Notification Type | Description | Channels |
|-------------------|-------------|----------|
| `new_message` | New chat message received | pusher, database |
| `new_offer` | New property offer received | pusher, fcm, database |
| `booking_confirmed` | Booking has been confirmed | fcm, database |
| `property_update` | Property status changed | pusher, database |
| `admin_alert` | Admin notification/alert | fcm, pusher, database |

## Notification Payloads

### New Message Notification

```json
{
  "notification": {
    "title": "New Message",
    "body": "John Doe: Hello!"
  },
  "data": {
    "type": "new_message",
    "room_id": "1",
    "message_id": "123",
    "sender_id": "2",
    "sender_name": "John Doe"
  },
  "android": {
    "priority": "high",
    "notification": {
      "channel_id": "chat_messages"
    }
  },
  "apns": {
    "payload": {
      "aps": {
        "badge": 1,
        "sound": "default"
      }
    }
  }
}
```

### New Offer Notification

```json
{
  "notification": {
    "title": "New Offer",
    "body": "$500,000 offer received for Luxury Apartment"
  },
  "data": {
    "type": "new_offer",
    "offer_id": "45",
    "property_id": "5",
    "property_title": "Luxury Apartment",
    "offer_amount": "500000"
  },
  "android": {
    "priority": "high"
  }
}
```

### Booking Confirmed Notification

```json
{
  "notification": {
    "title": "Booking Confirmed",
    "body": "Your booking for Luxury Apartment has been confirmed"
  },
  "data": {
    "type": "booking_confirmed",
    "booking_id": "78",
    "property_id": "5",
    "check_in": "2026-06-01",
    "check_out": "2026-06-05"
  }
}
```

### Property Status Changed Notification

```json
{
  "notification": {
    "title": "Property Update",
    "body": "Luxury Apartment is now marked as 'Sold'"
  },
  "data": {
    "type": "property_update",
    "property_id": "5",
    "old_status": "available",
    "new_status": "sold"
  }
}
```

## FCM Channel Configuration

### Priority Levels

| Priority | Use Case |
|----------|----------|
| `high` | New messages, offers, urgent notifications |
| `normal` | Property updates, general alerts |

### Android Channel IDs

| Channel ID | Description |
|------------|-------------|
| `chat_messages` | New chat messages |
| `property_offers` | New offers |
| `bookings` | Booking-related notifications |
| `property_updates` | Property status changes |
| `admin_alerts` | Admin notifications |

### Data Payload Structure

All FCM notifications include a `data` payload for handling in the app:

```json
{
  "type": "new_message|new_offer|booking_confirmed|property_update|admin_alert",
  "...": "additional type-specific fields"
}
```

## Frontend Implementation

### Handling Notifications

#### Android (Native)

```kotlin
class MyMessagingService : FirebaseMessagingService() {
    override fun onMessageReceived(remoteMessage: RemoteMessage) {
        val type = remoteMessage.data["type"]
        
        when (type) {
            "new_message" -> handleNewMessage(remoteMessage.data)
            "new_offer" -> handleNewOffer(remoteMessage.data)
            "booking_confirmed" -> handleBookingConfirmed(remoteMessage.data)
            "property_update" -> handlePropertyUpdate(remoteMessage.data)
            "admin_alert" -> handleAdminAlert(remoteMessage.data)
        }
    }
}
```

#### iOS (Swift)

```swift
func application(_ application: UIApplication, didReceiveRemoteNotification userInfo: [AnyHashable: Any], fetchCompletionHandler completionHandler: @escaping (UIBackgroundFetchResult) -> Void) {
    guard let type = userInfo["type"] as? String else { return }
    
    switch type {
    case "new_message": handleNewMessage(userInfo)
    case "new_offer": handleNewOffer(userInfo)
    case "booking_confirmed": handleBookingConfirmed(userInfo)
    case "property_update": handlePropertyUpdate(userInfo)
    case "admin_alert": handleAdminAlert(userInfo)
    default: break
    }
}
```

#### Web (JavaScript)

```javascript
import { initializeApp } from 'firebase/messaging';

const messaging = firebase.messaging();

messaging.onMessage((payload) => {
    const type = payload.data.type;
    
    switch (type) {
        case 'new_message':
            handleNewMessage(payload.data);
            break;
        case 'new_offer':
            handleNewOffer(payload.data);
            break;
        case 'booking_confirmed':
            handleBookingConfirmed(payload.data);
            break;
        case 'property_update':
            handlePropertyUpdate(payload.data);
            break;
        case 'admin_alert':
            handleAdminAlert(payload.data);
            break;
    }
});
```

### Handling Notification Clicks

```javascript
messaging.onNotificationClick((event) => {
    event.notification.close();
    
    const data = event.notification.data;
    
    // Navigate to appropriate screen
    switch (data.type) {
        case 'new_message':
            window.location.href = `/chat/rooms/${data.room_id}`;
            break;
        case 'new_offer':
            window.location.href = `/property/${data.property_id}/offers`;
            break;
        case 'booking_confirmed':
            window.location.href = `/bookings/${data.booking_id}`;
            break;
        case 'property_update':
            window.location.href = `/property/${data.property_id}`;
            break;
    }
});
```

## API Endpoints

### FCM Token Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/fcm/register` | Register a device FCM token |
| `DELETE` | `/api/fcm/revoke` | Revoke a device FCM token |

### Notifications

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/notifications` | List notifications for the authenticated user |
| `GET` | `/api/notifications/unread-count` | Get count of unread notifications |
| `PATCH` | `/api/notifications/{id}/read` | Mark a notification as read |
| `PATCH` | `/api/notifications/read-all` | Mark all notifications as read |
| `DELETE` | `/api/notifications/{id}` | Delete a notification |

## Notification Data Models

### Notification Entity

```json
{
  "id": 1,
  "user_id": 2,
  "type": "new_message",
  "title": "New Message",
  "body": "John Doe: Hello!",
  "data": {
    "room_id": 1,
    "message_id": 123
  },
  "read_at": null,
  "created_at": "2026-05-03T10:30:00Z"
}
```

### UserFcmToken Entity

```json
{
  "id": 1,
  "user_id": 2,
  "token": "firebase-device-token",
  "device_type": "android",
  "last_used_at": "2026-05-03T10:30:00Z",
  "created_at": "2026-04-01T00:00:00Z"
}
```

## Queue Configuration

FCM notifications are processed asynchronously via Laravel queues:

```php
// config/communication.php
'jobs' => [
    'notifications-high' => 'notifications-high-queue',
    'notifications-low' => 'notifications-low-queue',
],

// FCM retry configuration
'fcm' => [
    'retry_attempts' => 3,
    'retry_backoff' => 10, // seconds
    'timeout' => 30, // seconds
],
```

### Job Classes

| Job | Description |
|-----|-------------|
| `SendFcmNotificationJob` | Sends FCM notification asynchronously |
| `CleanExpiredFcmTokensJob` | Cleans up expired FCM tokens |

## Error Handling

### Invalid Token Handling

If FCM returns an `InvalidRegistration` error, the token should be automatically removed:

```php
// FcmTokenService handles this automatically
// Invalid tokens are deleted from the database
```

### Retry Logic

Failed FCM sends are retried up to 3 times with exponential backoff.

### Error Responses

```json
{
  "success": false,
  "error": {
    "code": "INVALID_TOKEN",
    "message": "The FCM token is invalid"
  }
}
```

## Testing

### Testing FCM Locally

1. Use Firebase Console to send test notifications
2. Check Firebase Console for delivery statistics
3. Monitor Laravel logs for errors

### Debugging

Enable FCM debug mode in development:

```php
'fcm' => [
    'debug' => env('FCM_DEBUG', false),
],
```

## Security Considerations

1. **Token Validation**: Validate token format before storing
2. **User Association**: Associate tokens with authenticated users only
3. **Token Refresh**: Handle token refresh events from FCM
4. **HTTPS**: Ensure FCM API calls use HTTPS
5. **Server Key Security**: Never expose server key in frontend code

## Best Practices

1. **Batch Sending**: Use batch API for sending to multiple users
2. **Topic Messaging**: Use topics for broadcast notifications
3. **Payload Size**: Keep notification payloads under 4KB
4. **Silent Notifications**: Use silent pushes for background data sync
5. **Cleanup**: Regularly clean expired tokens with `CleanExpiredFcmTokensJob`