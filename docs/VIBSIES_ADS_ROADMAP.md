# Vibsies Ads — implementation roadmap

Target: Laravel 12 / PostgreSQL on `develop`. The legacy Node.js prototype is not the implementation target.

## Completed in first feature branch
- Multi-property publisher schema: Facebook page, group, professional profile; Instagram creator account.
- Publisher onboarding with self-reported category, country, city, follower count, public account URL and ownership evidence.
- Manual admin verification queue; property is pending by default.
- Optional founding-promoter post/video URL submission with disclosure confirmation and unique referral code.
- Manual admin promotion review. Approval does **not** issue money or automatically change commissions.
- Feature flag `VIBSIES_ADS_ENABLED=false` by default, protected authenticated routes and admin-only moderation.

## Remaining (implement sequentially)
1. Complete and stabilize Laravel baseline migration and tests (authentication, existing bookings, deliveries, contracts, financial controls).
2. Implement proper verification challenges and evidence review history, secure asset uploads and resubmission.
3. Property-specific ad package types: feed post, story, video, group pin; category and regional targeting.
4. Audience metrics (self-reported versus verified fields), time series and evidence provenance.
5. Advertiser campaigns, budget, creative review, category/location filters and explainable relevance score.
6. Reservation/acceptance/cancellation and publication evidence; manual group/profile posting.
7. Founding program template library, referral redirect tracking, anti-abuse rules, expiration, optional reward entitlement (80% for first 3 approved fulfilled placements).
8. Payment gateway approval, ledger, taxes/fees, payout provider, reconciliation and dispute workflow.
9. Arabic/English translations and accessibility, security tests and integration/regression tests.
10. Pilot with two owned Facebook pages and genuine advertising results, then controlled release.

## Policies and safeguards
- A public post promoting Vibsies is **never required** for account verification or standard publisher access.
- Do not claim follower counts have been verified unless approved evidence supports them.
- Promotions involving incentives require applicable disclosure. Do not create automated group/profile publishing.
- A referral code is an identifier, not yet an active conversion tracking or payout mechanism.
- Do not enable the feature or deploy to production until application tests, security checks and policy review pass.

## Routes (when flag enabled)
- `GET /ads/publisher`
- `POST /ads/publisher/properties`
- `POST /ads/publisher/properties/{property}/promotions`
- `GET /admin/ads`
- `POST /admin/ads/properties/{property}/verify`
- `POST /admin/ads/promotions/{promotion}/review`

## Local verification
1. `composer install` and configure PostgreSQL and Laravel according to `docs/MIGRATION.md`.
2. `php artisan migrate` on an isolated development DB.
3. `php artisan route:list --path=ads`.
4. `php artisan test`.
5. Confirm 404 when `VIBSIES_ADS_ENABLED=false`; enable only in development for manual flow testing.

No claim is made that these commands have been executed by this change set.