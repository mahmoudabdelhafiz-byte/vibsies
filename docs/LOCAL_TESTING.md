# Local regression and migration verification

This project targets PHP 8.3+, Laravel 12 and PostgreSQL 16+. The test suite uses an isolated in-memory SQLite database to avoid modifying production data.

## Prerequisites
Install Composer 2 and PHP extensions: mbstring, openssl, pdo_sqlite, tokenizer, xml, ctype and fileinfo. For PostgreSQL runtime additionally install pdo_pgsql. Do not point testing environment variables at a production database.

## Run
```bash
composer install
cp .env.example .env
php artisan key:generate
bash scripts/local-regression.sh
```

The script checks prerequisites, validates composer metadata, lists routes and runs tests. For **PostgreSQL integration**, create a dedicated empty test database, set DB_CONNECTION=pgsql and credentials in a separate environment, then run `php artisan migrate:fresh --env=testing` **only** against that disposable test database. Never run migrate:fresh against production.

## Known limitations
Composer and PDO drivers were missing in the assistant execution environment as of 2026-10-16, so full framework, database, and HTTP testing has not yet been completed. Static PHP syntax checks are not equivalent to integration tests.

Payments are disabled; do not enable real settlements from these tables. An independent review is still needed for verification, private media processing, financial controls, and legal requirements.
