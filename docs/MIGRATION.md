# Node / SQLite → Laravel / PostgreSQL

The legacy prototype remains at `server.js` as a reference, but is not the Laravel entry point.

## Laravel setup
1. Install PHP 8.3+, Composer 2, PostgreSQL 16+ with PHP pdo_pgsql.
2. Run `composer install`.
3. Copy `.env.example` to `.env` and configure DB credentials.
4. Run `php artisan key:generate` then `php artisan migrate`.
5. Run `php artisan serve` and open http://localhost:8000.
6. Run `php artisan test` after installing dependencies.

## Status
The repository contains a **Laravel application foundation**, not a completed migration. No data migration or production cutover has been performed. Existing Node SQLite records are not imported. Marketplace read-only discovery and booking-request controller foundations are included. Authentication registration screens, contract consent endpoints, delivery and disputes, financial ledger, approved payment provider, bilingual UX, admin moderation and complete test coverage remain to be rebuilt.

The old Node prototype is intentionally retained for parity comparison. Do not start both applications as production services. No payment processing is enabled.
