# Vibsies — Laravel migration in progress

Laravel 12 and PostgreSQL are the target architecture. Read [migration instructions](docs/MIGRATION.md).

The original Node.js prototype is retained in `server.js` as a reference. It is not the production application and its records have not been migrated.

**No real payments or certified signing are enabled.**

For Laravel: `composer install`, copy `.env.example` to `.env`, configure PostgreSQL, `php artisan key:generate`, `php artisan migrate`, `php artisan serve`.

Legacy prototype: `npm start` (development reference only).
