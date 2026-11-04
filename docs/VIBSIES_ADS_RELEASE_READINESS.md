# Vibsies Ads — controlled release and rollback checklist

Status: **not approved for production**. This is a checklist, not evidence of deployment.

## Scope and safeguards
- Deploy only reviewed changes from the Vibsies Ads draft PR after a documented security/finance review.
- Keep `VIBSIES_ADS_ENABLED=false` and `VIBSIES_ADS_FINANCE_POSTING_ENABLED=false` in all production environments until explicit approval.
- Do not expose payment collection, payouts or a gateway webhook: a vetted provider adapter does not yet exist.
- Back up the PostgreSQL production database and verify restore procedures and backup retention before applying migrations.
- Deploy via the existing application's documented method; never run `migrate:fresh` or drop tables on production.

## Release gates (must all pass)
- [ ] Confirm an up-to-date and reproducible Composer lockfile and pinned runtime versions.
- [ ] SQLite and PostgreSQL full suites pass on the exact release commit, including authorization, referral and finance safety tests.
- [ ] Test all migrations and backward-compatible rollback paths with a copy of the production **schema** and representative anonymized volumes.
- [ ] Assess locking, index creation and expected data growth under PostgreSQL production traffic.
- [ ] Independently review admin permissions, referrals, promotion disclosures, ownership evidence, and audit logs.
- [ ] Review Arabic and English UI, RTL, accessibility and mobile layout.
- [ ] Define terms, privacy retention/deletion policy, sponsor disclosure and complaint handling.
- [ ] Confirm funds-flow licensing, provider-signed webhooks, reconciliations, disputes, chargebacks, tax and payout policies before enabling financial actions.
- [ ] Approve a manual controlled pilot on administrator-owned publishers, not with real advertiser money.
- [ ] Record production change owner, maintenance/communication plan, monitoring and rollback decision authority.

## Schema-only deployment procedure (future, after signoff)
1. Confirm baseline commit and health metrics; take encrypted database snapshot and test a restore.
2. Deploy reviewed code with both Ads feature flags OFF.
3. Run `php artisan migrate --force` on the intended production database only after the approved backup/rollback plan is verified.
4. Verify existing creator marketplace, bookings, contracts and login, plus new schema presence.
5. Keep Ads OFF until security and pilot acceptance are recorded.

## Rollback strategy
- **Immediate functional stop:** switch both feature flags OFF; ensure no active queues/processes use the Ads tables.
- **Code rollback:** redeploy the known-good prior commit. Additive Ads migrations should remain in place if old code ignores them.
- **Data rollback:** never blindly call `migrate:rollback` in production, since it can remove real or unrelated data. Use a reviewed, data-aware migration or restore only with explicit impact assessment and stakeholder approval.
- Preserve finance and audit records; never delete data to conceal discrepancies.
- Notify affected publishers and advertisers if a public pilot was running.

## Verification and release evidence
- Dual database CI for previous phase: GitHub Actions 41914827737 (139 tests/378 assertions per DB).
- Phase 8B security CI: see the latest workflow on PR #3; do not assume green until status has been verified.
- No production database migrations, staging rollout, payment collection, payouts or deployment has occurred in this task.
