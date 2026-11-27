# Ticketat Documentation

## API

| Document | Covers |
|---|---|
| [Conventions](api/conventions.md) | Base URL, versioning, headers, casing, response and error shapes, status codes, localization, rate limits |
| [Authentication](api/authentication.md) | Register, login, logout, current user, sign in with Google |

## Postman

The [`postman/`](postman/) folder has a collection with every endpoint and a local environment.

1. In Postman: **Import** → select both files in `docs/postman/`.
2. Select the **Ticketat Local** environment (top right).
3. Run **Auth → Register** or **Auth → Login**. The token is saved automatically and used by every protected request.

The collection is updated in the same pull request as the endpoints it describes.
