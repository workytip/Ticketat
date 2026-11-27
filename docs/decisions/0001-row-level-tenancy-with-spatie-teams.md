# 0001. Row-level tenancy with spatie/laravel-permission teams

- **Status:** Accepted
- **Date:** 2026-11-27

## Context

Organizers (organizations) are the tenants: each one owns its venues, events and reports, and its members have roles (owner, manager, staff) that only apply inside it. A user can be an owner in one organization and staff in another.

Customers, on the other hand, are platform-wide: the home page lists events from **all** organizers, and one checkout can include any of them.

## Options considered

1. **Database per tenant** (for example `stancl/tenancy` in multi-database mode). Strong isolation, but cross-organizer pages (event listing, search, platform reports) would need to query every tenant database or a separate central index.
2. **Shared database, `organization_id` on tenant rows** (row-level tenancy). One query lists events of every organizer. Isolation is enforced in code, so it needs discipline and tests.

For roles:

1. **Plain spatie roles:** global per user, so "owner in A, staff in B" is impossible.
2. **spatie with the teams feature:** role assignments carry `organization_id`, and permission checks use the current team.
3. **Custom role column on a membership table:** simple, but loses the spatie API the team already knows.

## Decision

- **Row-level tenancy**: a single database, `organization_id` on every tenant-owned table.
- **spatie/laravel-permission with teams**, team = organization, `team_foreign_key = organization_id`.
- **Membership is the role assignment itself** (a row in `model_has_roles` with the organization id). There is no separate members table that could disagree with the roles.
- Roles are global (`roles.organization_id = null`), assignments are scoped. Roles and their permissions are defined in `OrganizationRole` / `OrganizationPermission` enums and synced by `RolesAndPermissionsSeeder`.
- The current tenant is set in **one place**: the `ResolveOrganization` middleware. It returns `404` to non-members and calls `setPermissionsTeamId()`.

## Consequences

- Every query on tenant data must be scoped. Top-level tenant models (venues, events) use the `BelongsToOrganization` trait: `OrganizationScope` filters every query by the current organization and **throws** when there is none, instead of returning every organization's rows. New rows get the current `organization_id` automatically. Tests check that another organization's ids return `404`.
- Child tables (sections, seats) have no `organization_id`: they are only reached through their scoped parent. If a child ever needs to be queried on its own, it gets the column and the trait.
- `ResolveOrganization` runs **before** route model binding (middleware priority in `bootstrap/app.php`), so nested route models are loaded through the scope. The current organization lives in a scoped container binding (`CurrentOrganization`), reset for every request and job.
- Code that runs outside a request (queued jobs, commands) must set the team explicitly, never rely on a previous one. `MembershipService::inOrganization()` does this and restores the previous team.
- Roles and permissions must be seeded after every fresh database (`php artisan db:seed`).
- **Moving to database-per-tenant later** stays possible because the tenant is resolved in one middleware and every tenant row has `organization_id`. The likely path is hybrid: large organizers get their own database, a central catalog keeps cross-organizer listing. That would be a project of its own, not a configuration change.
