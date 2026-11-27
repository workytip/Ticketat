# Ticketat Documentation

## API

| Document | Covers |
|---|---|
| [Conventions](api/conventions.md) | Base URL, versioning, headers, casing, response and error shapes, status codes, localization, rate limits |
| [Authentication](api/authentication.md) | Register, login, logout, current user, sign in with Google |
| [Organizations](api/organizations.md) | Organizations (tenants), members, roles and permissions |
| [Venues](api/venues.md) | Venues, sections and generated seats |

## Decisions

Architecture Decision Records: why things are built the way they are.

| # | Decision |
|---|---|
| [0001](decisions/0001-row-level-tenancy-with-spatie-teams.md) | Row-level tenancy with spatie/laravel-permission teams |

## Postman

The [`postman/`](postman/) folder has a collection with every endpoint and a local environment.

1. In Postman: **Import** → select both files in `docs/postman/`.
2. Select the **Ticketat Local** environment (top right).
3. Run **Auth → Register** or **Auth → Login**. The token is saved automatically and used by every protected request.
4. **Organizations → Create** saves `orgSlug`, **Members → Add** saves `memberId`, and **Venues → Create** saves `venueId`, for the requests that need them.

The collection is updated in the same pull request as the endpoints it describes.
