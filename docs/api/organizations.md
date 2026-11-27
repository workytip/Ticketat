# Organizations and Members

An **organization** is an event organizer and the **tenant** of the platform: its venues, events and reports belong to it. Users join organizations as **members** with one role each.

See [conventions](conventions.md) for headers, error shapes and status codes, and [ADR 0001](../decisions/0001-row-level-tenancy-with-spatie-teams.md) for why it is built this way.

| Method | Endpoint | Who |
|---|---|---|
| GET | [`/organizations`](#list-my-organizations) | Any signed-in user |
| POST | [`/organizations`](#create-an-organization) | Any signed-in user |
| GET | [`/organizations/{slug}`](#show-an-organization) | Members |
| PATCH | [`/organizations/{slug}`](#update-an-organization) | Owner, manager |
| GET | [`/organizations/{slug}/members`](#list-members) | Members |
| POST | [`/organizations/{slug}/members`](#add-a-member) | Owner, manager (see rules) |
| PATCH | [`/organizations/{slug}/members/{userId}`](#change-a-members-role) | Owner, manager (see rules) |
| DELETE | [`/organizations/{slug}/members/{userId}`](#remove-a-member) | Owner, manager (see rules) |

All endpoints need `Authorization: Bearer <token>`.

## Roles

| Action | Owner | Manager | Staff |
|---|:---:|:---:|:---:|
| View the organization and its members | ✓ | ✓ | ✓ |
| Update name and slug | ✓ | ✓ | ✗ |
| Add, change or remove **owners and managers** | ✓ | ✗ | ✗ |
| Add, change or remove **staff** | ✓ | ✓ | ✗ |
| View venues | ✓ | ✓ | ✓ |
| Create, update, delete venues | ✓ | ✓ | ✗ |

Rules:

- A user has **one role per organization**, and can have different roles in different organizations (owner in one, staff in another). Permissions always use the role in the organization of the URL.
- An organization must keep **at least one owner**: the last owner cannot be demoted or removed (`422`).
- A manager cannot promote anyone to manager or owner.

## Tenant isolation

Every route under `/organizations/{slug}` first checks membership:

- **Not a member → `404`**, as if the organization did not exist. A `403` would confirm that it exists.
- **Member without the permission → `403`.**

---

## List my organizations

`GET /api/v1/organizations`

Only the organizations the user belongs to, sorted by name, with the user's role in each.

**200 OK**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Cairo Jazz",
      "slug": "cairo-jazz",
      "role": "owner",
      "createdAt": "2026-11-27T13:23:49+00:00"
    }
  ]
}
```

---

## Create an organization

`POST /api/v1/organizations`

The creator becomes the **owner**.

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, max 255 |
| `slug` | string | optional. Lowercase letters, numbers, `-` and `_`, max 100, unique. Generated from the name when missing: `Cairo Jazz` → `cairo-jazz`, then `cairo-jazz-2` if taken. |

```json
{ "name": "Cairo Jazz" }
```

**201 Created**

```json
{
  "data": {
    "id": 1,
    "name": "Cairo Jazz",
    "slug": "cairo-jazz",
    "role": "owner",
    "createdAt": "2026-11-27T13:23:49+00:00"
  }
}
```

**Errors:** `422` invalid or taken slug.

---

## Show an organization

`GET /api/v1/organizations/{slug}`

**200 OK:** same shape as one item of the list. `role` is the signed-in user's role, so the UI can show or hide actions.

**404** for non-members:

```json
{ "message": "Not found." }
```

---

## Update an organization

`PATCH /api/v1/organizations/{slug}`

| Field | Type | Rules |
|---|---|---|
| `name` | string | optional, max 255 |
| `slug` | string | optional, same rules as create. Changing it changes every URL of the organization. |

**200 OK:** the updated organization. **403** for staff.

---

## List members

`GET /api/v1/organizations/{slug}/members`

Sorted by name. `id` is the user id, used in the member endpoints below.

**200 OK**

```json
{
  "data": [
    { "id": 3, "name": "Omar Hassan", "email": "omar@example.com", "role": "staff" },
    { "id": 2, "name": "Sara Ali", "email": "sara@example.com", "role": "owner" }
  ]
}
```

---

## Add a member

`POST /api/v1/organizations/{slug}/members`

The user must already have an account (invitations by email come later).

| Field | Type | Rules |
|---|---|---|
| `email` | string | required, an existing user |
| `role` | string | required: `owner`, `manager` or `staff`. Managers can only give `staff`. |

```json
{ "email": "omar@example.com", "role": "staff" }
```

**201 Created**

```json
{ "data": { "id": 3, "name": "Omar Hassan", "email": "omar@example.com", "role": "staff" } }
```

**Errors**

| Code | When |
|---|---|
| `403` | Staff, or a manager giving `manager`/`owner` |
| `422` | Unknown email, invalid role, or already a member: `{ "message": "This user is already a member of the organization." }` |

---

## Change a member's role

`PATCH /api/v1/organizations/{slug}/members/{userId}`

| Field | Type | Rules |
|---|---|---|
| `role` | string | required: `owner`, `manager` or `staff` |

The actor must be allowed to manage the member's **current** role and to give the **new** one.

**200 OK**

```json
{ "data": { "id": 3, "name": "Omar Hassan", "email": "omar@example.com", "role": "manager" } }
```

**Errors**

| Code | When |
|---|---|
| `403` | Not allowed (see the roles table) |
| `404` | The user is not a member of this organization |
| `422` | Demoting the last owner: `{ "message": "The organization must keep at least one owner." }` |

---

## Remove a member

`DELETE /api/v1/organizations/{slug}/members/{userId}`

Removes the membership in **this** organization only; the user keeps their account and other memberships.

**200 OK**

```json
{ "message": "Member removed." }
```

**Errors:** `403` not allowed, `404` not a member, `422` last owner.

---

## Code map

| Concern | File |
|---|---|
| Routes | `api/routes/api.php` |
| Tenant resolution (404 + spatie team) | `app/Http/Middleware/ResolveOrganization.php` |
| Roles and their permissions | `app/Enums/OrganizationRole.php`, `OrganizationPermission.php` |
| Seeding roles and permissions | `database/seeders/RolesAndPermissionsSeeder.php` |
| Authorization rules | `app/Policies/OrganizationPolicy.php` |
| Membership logic, last-owner rule | `app/Services/MembershipService.php` |
| Organizations, slug generation | `app/Services/OrganizationService.php` |
| Controllers | `app/Http/Controllers/Api/V1/Organization*Controller.php` |
| Tests (role matrix, isolation) | `tests/Feature/Organizations/*` |

After a fresh database, seed the roles: `php artisan db:seed` (or `php artisan migrate --seed`).

When a pull request adds permissions, run the roles seeder again (safe to repeat):

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```
