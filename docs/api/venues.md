# Venues

A **venue** is a place where an organization holds events. It is split into **sections** (VIP, Balcony), and each section has a grid of **seats** generated from its rows and seats per row.

See [conventions](conventions.md) for headers and error shapes, and [organizations](organizations.md) for roles.

| Method | Endpoint | Who |
|---|---|---|
| GET | [`/organizations/{slug}/venues`](#list-venues) | Members |
| POST | [`/organizations/{slug}/venues`](#create-a-venue) | Owner, manager |
| GET | [`/organizations/{slug}/venues/{id}`](#show-a-venue) | Members |
| PATCH | [`/organizations/{slug}/venues/{id}`](#update-a-venue) | Owner, manager |
| DELETE | [`/organizations/{slug}/venues/{id}`](#delete-a-venue) | Owner, manager |

All endpoints need `Authorization: Bearer <token>`. Staff can view, but not change, venues (`403`).

## Seat layout

Seats are generated when the venue is created:

- Rows are lettered **A, B, C...** (max 26 rows per section).
- Seats are numbered **1..seatsPerRow** in each row (max 100).
- Each section starts again at row A: seat labels are unique **inside a section**, so a seat is identified as `VIP · B7`.

```
Section "VIP": rows 2, seatsPerRow 10

  A  [1][2][3][4][5][6][7][8][9][10]
  B  [1][2][3][4][5][6][7][8][9][10]     → 20 seats
```

**Sections and seats cannot be changed after creation.** Events sell tickets for exact seats, so changing the layout would break existing tickets. To change the layout, create a new venue.

## Tenant isolation

Venues are tenant data. Another organization's venue id returns **`404`** on every endpoint, even if the id exists:

```json
{ "message": "Not found." }
```

---

## List venues

`GET /api/v1/organizations/{slug}/venues`

Sorted by name. Sections are not included; use [show](#show-a-venue).

**200 OK**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Cairo Opera House",
      "address": "Gezira",
      "city": "Cairo",
      "seatCount": 120,
      "createdAt": "2026-11-27T13:35:22+00:00"
    }
  ]
}
```

---

## Create a venue

`POST /api/v1/organizations/{slug}/venues`

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 255 |
| `address` | string | optional, max 255 |
| `city` | string | required, max 100 |
| `sections` | array | required, 1 to 20 sections, in display order |
| `sections[].name` | string | required, max 100, unique in the venue (case-insensitive) |
| `sections[].rows` | integer | required, 1 to 26 |
| `sections[].seatsPerRow` | integer | required, 1 to 100 |

```json
{
  "name": "Cairo Opera House",
  "address": "Gezira",
  "city": "Cairo",
  "sections": [
    { "name": "VIP", "rows": 2, "seatsPerRow": 10 },
    { "name": "Balcony", "rows": 5, "seatsPerRow": 20 }
  ]
}
```

**201 Created**

```json
{
  "data": {
    "id": 1,
    "name": "Cairo Opera House",
    "address": "Gezira",
    "city": "Cairo",
    "seatCount": 120,
    "sections": [
      { "id": 1, "name": "VIP", "rows": ["A", "B"], "seatsPerRow": 10, "seatCount": 20 },
      { "id": 2, "name": "Balcony", "rows": ["A", "B", "C", "D", "E"], "seatsPerRow": 20, "seatCount": 100 }
    ],
    "createdAt": "2026-11-27T13:35:22+00:00"
  }
}
```

`rows` in the response is the list of row letters, ready to draw the grid.

**422 Validation error**

```json
{
  "message": "The rows field must not be greater than 26. (and 1 more error)",
  "errors": {
    "sections.0.rows": ["The rows field must not be greater than 26."],
    "sections.0.seatsPerRow": ["The seats per row field must be at least 1."]
  }
}
```

---

## Show a venue

`GET /api/v1/organizations/{slug}/venues/{id}`

**200 OK:** same shape as the create response, with sections.

---

## Update a venue

`PATCH /api/v1/organizations/{slug}/venues/{id}`

| Field | Type | Rules |
|---|---|---|
| `name` | string | optional, max 255 |
| `address` | string or null | optional, max 255 |
| `city` | string | optional, max 100 |

`sections` is ignored ([seat layout](#seat-layout) is fixed).

**200 OK:** the updated venue with sections.

---

## Delete a venue

`DELETE /api/v1/organizations/{slug}/venues/{id}`

Deletes the venue, its sections and seats.

**200 OK**

```json
{ "message": "Venue deleted." }
```

> When events are added (next phase), a venue with events will not be deletable.

---

## Code map

| Concern | File |
|---|---|
| Routes | `api/routes/api.php` (`Route::apiResource('venues', ...)`) |
| Seat generation | `app/Actions/Venue/CreateVenue.php` |
| List, update, delete | `app/Services/VenueService.php` |
| Permissions | `app/Policies/VenuePolicy.php`, `venues.view` / `venues.manage` in `app/Enums/OrganizationPermission.php` |
| Tenant scope | `app/Models/Concerns/BelongsToOrganization.php`, `app/Models/Scopes/OrganizationScope.php` |
| Tests | `tests/Feature/Venues/*`, `tests/Feature/Tenancy/*` |
