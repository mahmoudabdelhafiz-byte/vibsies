# Vibsies launch readiness gate (2026-10-17)

## Current decision: NO-GO

The Laravel migration is under active development on `develop`. Do not merge into `main` or enable production checkout until every gate below is verified with dated evidence.

| Gate | Requirement | Present status |
| --- | --- | --- |
| Database | Clean PostgreSQL migration and rollback on a disposable instance | NOT VERIFIED |
| Application | Composer install, PHP lint, route listing and full automated test suite | NOT VERIFIED |
| Workflow | Booking -> creator acceptance -> two-party agreement -> video upload -> customer approval, revisions and disputes | NOT VERIFIED |
| Authorization | Foreign-user access denial for bookings, agreements, deliveries, notifications, moderation and sample editing | NOT VERIFIED |
| Concurrency | Duplicate booking decisions, submissions, revision requests, verification submissions and dispute resolutions | NOT VERIFIED |
| Files | Upload MIME/content controls, permission isolation, safe previews, cleanup and size limits | NOT VERIFIED |
| Money | Provider selected; authenticated sandbox callbacks, refunds, payouts, idempotency and settlement reconciliation tested | NOT IMPLEMENTED |
| Operations | Worker/scheduler, database backups/restores, secret handling, error monitoring and deployment rollback | NOT VERIFIED |
| Legal | Privacy, commercial terms, refund/dispute policy and local payment requirements reviewed | NOT VERIFIED |

## Reproducible local regression

On a machine with Docker:

```bash
docker compose -f compose.test.yaml run --rm regression
```

This existing Docker harness runs PHP preflight, syntax lint, Composer validation, route listing and PHPUnit with SQLite. **SQLite success is insufficient for release**: also run migrations, transactions and concurrency integration tests against disposable PostgreSQL with supported PHP extensions. Never point test commands at production.

## Specific code risks tracked

1. Historic agreements created before revision-limit snapshots may still rely on a mutable package setting. Do not silently rewrite consent terms; plan a controlled migration after examining actual records.
2. Dispute recovery must not bypass stored agreement hash verification or signatures. Regression cases added on develop; execution pending.
3. Payment gateway and webhook contracts are intentionally fail-closed. Provider choice, sandbox credentials, webhook signature verification, refunds/payouts, and legal approvals are prerequisites for enabling any money movement.
4. Local agent runtime has PHP 8.4 but no Composer/Docker or required extensions (mbstring, PDO SQLite/PostgreSQL, XML/DOM); a complete Laravel test was not performed there.
5. Media previews rely on configured storage and optional FFmpeg; browser acceptance, failed-processing recovery and malicious-file scenarios remain unverified.

## Merge checklist

- [ ] Successful fresh PostgreSQL migration with evidence and no schema drift
- [ ] All feature/unit tests green and static/security checks clear
- [ ] Manual English/Arabic and mobile end-to-end walkthrough
- [ ] Verified isolated media storage and authorization
- [ ] Payments remain explicitly disabled until sandbox and operations are approved
- [ ] Backup/restore and rollback rehearsed
- [ ] Independent review of release diff
- [ ] Explicit release approval

Do **not** interpret the existence of tests as proof that they passed.
