# Authentication Flow

## Overview

- **Framework**: Laravel Fortify (custom overridden response contracts)
- **Token Type**: Laravel Sanctum API tokens (not SPA/cookie auth)
- **Guard**: `auth:sanctum` on protected routes
- **Token Expiry**: Never expires (standard Sanctum API tokens)
- **Login**: OTP-based (`/auth/login` sends code → `/auth/verify-otp` returns token)
- **Two-Factor**: Supported via Fortify 2FA (Google Authenticator etc.)
- **Password Reset**: Built-in Fortify flow
- **Email Verification**: Built-in Fortify (signed URL verification)

---

## Authentication Headers

All authenticated requests must include:
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

---

## Endpoints Summary

### Public (No Auth Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register new user |
| POST | `/auth/login` | Send OTP to phone/email (`AuthController::sendOtp`) |
| POST | `/auth/verify-otp` | Verify OTP code, receive token |
| POST | `/auth/two-factor-challenge` | Complete 2FA after login |
| POST | `/auth/forgot-password` | Request password reset email |
| POST | `/auth/reset-password` | Reset password with token |
| GET | `/auth/email/verify/{id}/{hash}` | Verify email (signed URL) |

### Authenticated (Bearer Token Required)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/logout` | Logout (deletes ALL tokens) |
| POST | `/auth/email/verification-notification` | Resend verification email |
| PUT | `/auth/user/profile-information` | Update name/email |
| PUT | `/auth/user/password` | Change password |
| POST | `/auth/user/confirm-password` | Confirm password |
| POST | `/auth/user/two-factor-authentication` | Enable 2FA |
| POST | `/auth/user/confirmed-two-factor-authentication` | Confirm 2FA code |
| DELETE | `/auth/user/two-factor-authentication` | Disable 2FA |
| GET | `/auth/user/two-factor-qr-code` | Get QR code (SVG) |
| GET | `/auth/user/two-factor-secret-key` | Get secret key text |
| GET | `/auth/user/two-factor-recovery-codes` | Get recovery codes |
| POST | `/auth/user/two-factor-recovery-codes` | Regenerate recovery codes |
| GET | `/user` | Get current authenticated user |

---

## Request/Response Examples

### Register
**Request:**
```json
POST /auth/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "status": "active",
      "publisher_type": "individual",
      "is_verified": false,
      "roles": [],
      "created_at": "2026-08-02 10:00:00",
      "updated_at": "2026-08-02 10:00:00"
    },
    "token": "1|abc123def456ghi789..."
  }
}
```

### Login (OTP-based)
**Step 1 — Send OTP:**
```json
POST /auth/login
{
  "phone": "+971501234567"   // or "email": "john@example.com"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "OTP sent successfully"
}
```

**Step 2 — Verify OTP:**
```json
POST /auth/verify-otp
{
  "phone": "+971501234567",
  "code": "123456"
}
```

**Response (200):**
```json
{
  "data": {
    "user": { "...same as register..." },
    "token": "1|xyz789..."
  }
}
```

**Error Response (422):**
```json
{
  "message": "Invalid or expired OTP code.",
  "errors": {
    "code": ["Invalid or expired OTP code."]
  }
}
```

### Login with 2FA Enabled
1. POST `/auth/login` → sends OTP, response indicates 2FA is required
2. POST `/auth/two-factor-challenge` with `code` from authenticator app
3. → returns `{ user, token }`

### Logout
**Request:** `POST /auth/logout` with Bearer token
**Response (200):** `{ "data": null, "message": "Logged out successfully" }`

---

## Token Management Notes

1. **Token format**: Sanctum returns plain-text tokens like `1|base64hash...`. Only the plain-text version is returned ONCE (at login/register). Store it securely.
2. **Logout deletes ALL tokens**: POST `/auth/logout` deletes every token for the user (not just the current one).
3. **No refresh tokens**: Sanctum API tokens do not refresh. Create a new login session to get a new token.
4. **Token abilities**: Default tokens have no ability restrictions (full access). Not currently using Sanctum abilities.

---

## Rate Limiting

| Endpoint | Limit |
|----------|-------|
| `/auth/login` | 5 attempts per minute |
| `/auth/verify-otp` | 5 attempts per minute |
| `/auth/email/verify/{id}/{hash}` | 6 per minute |
| `/auth/email/verification-notification` | 6 per minute |

---

## Fortify Customization

The project overrides ALL Fortify response contracts via `Modules/Auth/Providers/FortifyServiceProvider.php`:
- Default Fortify routes are disabled via `Fortify::ignoreRoutes()`
- Custom routes defined in `routes/api.php` and `Modules/Auth/Routes/api.php`
- All auth responses return JSON with `{ user, token }` format
- Fortify features enabled: registration, password reset, email verification, profile update, 2FA
- Fortify views disabled (API-only)
- Fortify guard: `web`, prefix: `api/auth`, middleware: `api`
