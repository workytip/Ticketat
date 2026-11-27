# Ticketat

Open-source live event ticketing platform with a real-time seat map.

> 🚧 Work in progress.

## Stack

- **api/**: Laravel 13 (API only), Pest
- **web/**: React 19, TypeScript, Vite
- MySQL 8.4, Redis 7

## Run with Docker

```bash
docker-compose up -d --build
docker-compose exec api php artisan migrate
```

- API: http://localhost:8000
- Web: http://localhost:5173

## Run locally (without Docker)

Requires PHP 8.3, Composer and Node 24.

```bash
# API
cd api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

# Web (another terminal)
cd web
npm install
npm run dev
```

## Tests

```bash
cd api && vendor/bin/pest
cd web && npm run lint && npm run build
```

## License

[MIT](LICENSE)
