#!/bin/sh
set -e

# First run inside the container: install dependencies and create .env.
[ -d vendor ] || composer install --no-interaction --prefer-dist
[ -f .env ] || { cp .env.example .env && php artisan key:generate --ansi; }

exec "$@"
