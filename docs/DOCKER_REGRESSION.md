# Reproducible local Laravel regression

Prerequisites: Docker Engine with Compose plugin and permission to build images.

From the root of the Laravel repository run:

```bash
docker compose -f compose.test.yaml run --rm --build regression
```

The container includes PHP 8.4, Composer 2, SQLite PDO, PostgreSQL PDO and XML-related extensions. Its test environment uses an in-memory SQLite database and does not connect to production.

A successful image build is not a successful test run: inspect command exit status and PHPUnit output. The first install requires internet access to download Composer dependencies. A PostgreSQL-specific migration run against a **disposable** database is still needed separately.

Do not use this Compose file for public hosting or payment processing.
