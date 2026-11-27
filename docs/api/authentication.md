# Authentication

Token authentication with [Laravel Sanctum](https://laravel.com/docs/sanctum). Every successful sign in (register, login, Google) returns a **personal access token** for one device. Send it on protected requests:

```
Authorization: Bearer 1|3lEglZqnyBINolAx7CoIYw633HYXbfIa9CLwY4yGc064c14e
```

Each device has its own token (`deviceName`), so logging out on one device keeps the others signed in.

See [conventions](conventions.md) for headers, error shapes and rate limits.

| Method | Endpoint | Auth |
|---|---|---|
| POST | [`/auth/register`](#register) | Guest, rate limited |
| POST | [`/auth/login`](#login) | Guest, rate limited |
| GET | [`/auth/me`](#current-user) | Bearer token |
| POST | [`/auth/logout`](#logout) | Bearer token |
| GET | [`/auth/{provider}/redirect`](#sign-in-with-google) | Guest |
| POST | [`/auth/{provider}/callback`](#sign-in-with-google) | Guest, rate limited |

---

## Register

`POST /api/v1/auth/register`

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 255 |
| `email` | string | required, valid email, unique |
| `password` | string | required, at least 8 characters |
| `passwordConfirmation` | string | required, same as `password` |
| `deviceName` | string | required, max 255. Shown to the user in a future "active sessions" list, for example `web` or `Sara's iPhone`. |

```json
{
  "name": "Sara Ali",
  "email": "sara@example.com",
  "password": "Secret-Pass-2026",
  "passwordConfirmation": "Secret-Pass-2026",
  "deviceName": "web"
}
```

**201 Created**

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Sara Ali",
      "email": "sara@example.com",
      "emailVerifiedAt": null,
      "createdAt": "2026-11-27T13:09:21+00:00"
    },
    "token": "1|3lEglZqnyBINolAx7CoIYw633HYXbfIa9CLwY4yGc064c14e",
    "tokenType": "Bearer"
  }
}
```

**Errors:** `422` invalid fields or email already taken, `429` too many attempts.

---

## Login

`POST /api/v1/auth/login`

| Field | Type | Rules |
|---|---|---|
| `email` | string | required, valid email |
| `password` | string | required |
| `deviceName` | string | required, max 255 |

**200 OK:** same body as [register](#register).

**401 Unauthorized:** wrong email or password. The message is the same for both, so an attacker cannot learn which emails are registered.

```json
{ "message": "These credentials do not match our records." }
```

Users who signed up with Google and never set a password always get `401` here.

---

## Current user

`GET /api/v1/auth/me`

**200 OK**

```json
{
  "data": {
    "id": 1,
    "name": "Sara Ali",
    "email": "sara@example.com",
    "emailVerifiedAt": null,
    "createdAt": "2026-11-27T13:09:21+00:00"
  }
}
```

**401:** missing, invalid or revoked token.

```json
{ "message": "Unauthenticated." }
```

---

## Logout

`POST /api/v1/auth/logout`

Revokes **only the token used in this request**. Other devices stay signed in.

**200 OK**

```json
{ "message": "Logged out." }
```

---

## Sign in with Google

Supported providers: `google`. Any other value for `{provider}` returns `404`.

### Flow

```
Frontend                    API                          Google
   │  GET /auth/google/redirect │                            │
   │───────────────────────────>│                            │
   │  { data: { url } }         │                            │
   │<───────────────────────────│                            │
   │  open url ─────────────────────────────────────────────>│ user approves
   │  redirected to /auth/google/callback?code=...&state=... │
   │<────────────────────────────────────────────────────────│
   │  POST /auth/google/callback { code, deviceName }        │
   │───────────────────────────>│  exchange code ───────────>│
   │                            │  profile <─────────────────│
   │  { data: { user, token } } │                            │
   │<───────────────────────────│                            │
```

The API is **stateless** (no session), so it does not check the OAuth `state` parameter. The frontend must generate `state` before opening the URL and compare it when Google redirects back.

### Get the consent URL

`GET /api/v1/auth/google/redirect`

**200 OK**

```json
{
  "data": {
    "url": "https://accounts.google.com/o/oauth2/auth?client_id=...&redirect_uri=http%3A%2F%2Flocalhost%3A5173%2Fauth%2Fgoogle%2Fcallback&scope=openid+profile+email&response_type=code"
  }
}
```

### Exchange the code

`POST /api/v1/auth/google/callback`

| Field | Type | Rules |
|---|---|---|
| `code` | string | required. The `code` query parameter Google added to the redirect. |
| `deviceName` | string | required, max 255 |

**200 OK:** same body as [register](#register).

What happens to the account:

| Situation | Result |
|---|---|
| Google account already linked | Signs in the linked user, even if the email changed at Google |
| A user with the same email exists | Links Google to that user, then signs in |
| New email | Creates a user **without a password**, email marked as verified |

**Errors**

| Code | When |
|---|---|
| `401` | Google rejected the code (expired, already used, or invalid) |
| `422` | Google did not verify the email. Linking an unverified email would let anyone take over an account by registering that address at the provider. |
| `429` | Too many attempts |

### Configuration

In `api/.env` (free, from [Google Cloud Console](https://console.cloud.google.com/) → APIs & Services → Credentials → OAuth client ID, type **Web application**):

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:5173/auth/google/callback
```

`GOOGLE_REDIRECT_URI` points to the **frontend** page, and must also be added to **Authorized redirect URIs** in Google Cloud Console.

---

## Code map

| Concern | File |
|---|---|
| Routes | `api/routes/api.php` |
| Email/password endpoints | `app/Http/Controllers/Api/V1/AuthController.php` → `app/Services/AuthService.php` |
| Google endpoints | `app/Http/Controllers/Api/V1/SocialAuthController.php` → `app/Actions/Auth/AuthenticateWithSocialProvider.php` |
| Validation | `app/Http/Requests/Auth/*` |
| Response shape | `app/Http/Resources/UserResource.php`, `AuthTokenResource.php` |
| Errors | `app/Exceptions/Auth/*` |
| Rate limit | `app/Providers/AppServiceProvider.php` |
| Tests | `tests/Feature/Auth/*` |
