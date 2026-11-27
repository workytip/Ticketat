# API Conventions

Rules that apply to every endpoint. Feature documents only describe what is specific to them.

## Base URL and versioning

```
http://localhost:8000/api/v1
```

The version is part of the URL. A new version (`v2`) is created only for a **breaking change** (removing or renaming a field, changing a response shape). Adding endpoints or optional fields stays in `v1`.

## Headers

| Header | Value | Required |
|---|---|---|
| `Accept` | `application/json` | Always. Without it, errors may come back as HTML redirects. |
| `Content-Type` | `application/json` | For requests with a body |
| `Authorization` | `Bearer <token>` | For protected endpoints |
| `Accept-Language` | `en` (default) or `ar` | Optional. Selects the language of messages. |

## Casing

- **Request and response JSON use camelCase:** `deviceName`, `passwordConfirmation`, `createdAt`.
- Validation errors use the same camelCase field names, so a form can map them directly.
- The database uses snake_case. The conversion happens in `ApiRequest::prepareForDatabase()` (input) and in API Resources (output).

## Response shapes

**Success** always has a `data` key, for a single item and for lists:

```json
{ "data": { "id": 1, "name": "Sara Ali" } }
```

**Action without a resource** (for example logout):

```json
{ "message": "Logged out." }
```

**Error:**

```json
{ "message": "These credentials do not match our records." }
```

**Validation error (422):** `message` summarizes, `errors` lists every field:

```json
{
  "message": "The name field is required. (and 1 more error)",
  "errors": {
    "name": ["The name field is required."],
    "passwordConfirmation": ["The password confirmation field must match password."]
  }
}
```

## Status codes

| Code | Meaning |
|---|---|
| `200` | Success |
| `201` | Resource created |
| `401` | Missing or invalid token, or wrong credentials |
| `403` | Authenticated but not allowed |
| `404` | Not found, or exists but belongs to another organization (we do not reveal it) |
| `422` | Validation failed, or a business rule rejected the request |
| `429` | Too many requests. See the `Retry-After` header (seconds). |

## Dates

ISO 8601 with offset: `2026-11-27T13:09:21+00:00`. Clients convert to the user's timezone for display.

## Localization

Send `Accept-Language: ar` to receive messages in Arabic:

```json
{ "message": "بيانات الدخول غير صحيحة." }
```

Supported: `en` (default), `ar`. Translations live in `api/lang/{en,ar}`.

## Rate limits

| Endpoints | Limit | Key |
|---|---|---|
| Register, login, social callback | 5 per minute | email + IP |
