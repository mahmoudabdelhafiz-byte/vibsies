# Vibsies Ads — implementation roadmap

Target: Laravel 12 / PostgreSQL on `develop`. The legacy Node.js prototype is not the implementation target.

## Completed in first feature branch
- Multi-property publisher schema: Facebook page, group, professional profile; Instagram creator account.
- Publisher onboarding with self-reported category, country, city, follower count, public account URL and ownership evidence.
- Manual admin verification queue; property is pending by default.
- Optional founding-promoter post/video URL submission with disclosure confirmation and unique referral code.
- Manual admin promotion review. Approval does **not** issue money or automatically change commissions.
- Feature flag `VIBSIES_ADS_ENABLED=false` by default, protected authenticated routes and admin-only moderation.

## Added in phase 2 (publisher inventory and metrics)
- Verified publishers can submit placements with type, duration, delivery window, country-aligned currency and pricing in minor units.
- New packages are inactive and pending moderation; group placements restrict formats.
- Dated publisher metric snapshots track content category, reach/views/interactions, provenance and evidence URL; all start self-reported and unverified.
- Admin-only review actions; no automatic public publishing or automatic conversion of self-reported metrics into verified claims.
- Publisher/admin forms and six additional feature test cases (not executed in this connector session).

## Added in phase 3 (advertiser drafts and matching)
- Advertiser can create an unpaid draft with objective, category, country/city, format and budget.
- Existing business ownership is checked before a campaign can reference an organization.
- Publisher recommendations include only active approved packages on verified properties, within the budget, correct currency and country.
- Explainable ranking uses exact category/city, recently reviewed metric evidence and price; no projected impressions or outcomes.
- Campaign detail is restricted to its owning user. Five feature tests committed, not executed.

## Added in phase 4 (campaign offer and manual publication workflow)
- New ad campaign offers table snapshots the agreed placement amount, currency, publisher and status.
- Advertisers submit draft campaigns for administrator approval and can send offers to eligible publisher packages after approval.
- Publisher can accept or decline, then submit a public sponsored post URL with disclosure confirmation.
- Advertiser can confirm or dispute the publication evidence. Disputes remain unresolved until a future moderation/settlement workflow exists.
- Offer creation locks the campaign row and enforces its remaining nominal budget; no wallet, funds reservation, settlement or payment is implemented.
- Six feature tests committed but NOT EXECUTED.
- This is a feature-flagged development prototype only. Do not solicit real paid publications using it before payment, agreements and operational policies are ready.

## Added in phase 5A (lifecycle safeguards)
- Preliminary offers now carry a seven-day expiry timestamp; publishers cannot accept overdue offers.
- Advertisers may withdraw offers before acceptance; publishers may cancel pending or accepted offers before publication, with a mandatory explanation.
- Administrators can mark overdue offers expired and resolve disputed publication submissions with recorded reasons.
- Cancellation, expiry and dispute resolution produce append-only event records with the actor and status transition.
- The admin and publisher dashboards expose these controls. Seven additional feature test cases committed, not run.
- Limitations: expiry marking is admin-triggered, not yet scheduled; dispute decisions never trigger payments. Existing workflow transitions need comprehensive event logging, and formal terms are not implemented.

## Added in phase 5B (Founding Publisher referral and eligibility foundation)
- An approved social-property owner receives a unique referral link before publishing a Vibsies promotional post or video.
- The link opens registration with a seven-day attribution session and no IP address or device fingerprint tracking.
- Each newly registered user may have only one recorded referral; registration attribution is not itself a qualified conversion or paid reward.
- Publisher submits a disclosed post URL for admin moderation. Approval creates at most one nonfinancial eligibility record per publisher property.
- Proposed eligibility: 80% publisher share on at most three future eligible paid placements, valid 90 days; no allocation or payout processing is enabled.
- Referral and reward objects, migrations, routes, owner dashboard display and initial feature tests have been committed but NOT EXECUTED.
- Outstanding: reliable click analytics, terms acceptance, anti-fraud review, real placement settlement, entitlement redemption under financial transactions, and campaign reconciliation.

## Added in phase 6A (financial safeguards and internal ledger foundation)
- Separate finance flag `VIBSIES_ADS_FINANCE_POSTING_ENABLED=false` by default, in addition to the main disabled Vibsies Ads flag.
- Payment record schema tracks provider, external reference, amount, status and verification timestamp. There is NO payment provider adapter or API capable of setting verified capture.
- One settlement batch per offer; ledger entries balance gross receivable debit to publisher payable and platform revenue credits in integer minor units.
- Internal-only posting requires confirmed delivery and independently verified fully captured funds, rejects disputes and prevents double posting.
- Standard publisher share 70%; optional valid/approved founding entitlement 80% for at most three placements. Redemptions occur only in a single transaction with ledger creation.
- Amounts are preliminary *before* taxes, provider processing fees, refunds, withholding and regulatory treatment; settlement rows remain held and no payouts can occur.
- Five unit test cases and two integration safety cases committed. These tests have NOT been executed. No funds have been captured, credited or transferred.

## Remaining (implement sequentially)
1. Complete and stabilize Laravel baseline migration and tests (authentication, existing bookings, deliveries, contracts, financial controls).
2. Implement proper verification challenges and evidence review history, secure asset uploads and resubmission.
3. ~~Property-specific ad package types~~ Initial inventory foundation committed. Enhance descriptions, upload evidence, revision and auditing.
4. ~~Dated audience metrics~~ Initial snapshots and manual review committed. Add robust platform evidence validation and reporting.
5. ~~Advertiser campaign drafts and initial matching~~ Added. Pending: creative review, campaign approvals, purchasing and fulfillment.
6. ~~Offer, acceptance, publication evidence, cancellation, expiry and basic dispute decisions~~ Initial code added. Pending: comprehensive audit logging, scheduling, resubmission, formal agreements and financial reconciliation.
7. ~~Referral links, registration attribution and eligibility record~~ Initial code committed. Pending: creative templates, fraud/qualification checks, click reporting and accounting-grade reward redemption.
8. ~~Internal ledger schema, math and guarded posting foundation~~ Added. Still required: licensed/approved payment provider, trusted webhook verification, refunds/reversals, taxes/fees, payout reconciliation, audit and independent approval.
9. Arabic/English translations and accessibility, security tests and integration/regression tests.
10. Pilot with two owned Facebook pages and genuine advertising results, then controlled release.

## Policies and safeguards
- A public post promoting Vibsies is **never required** for account verification or standard publisher access.
- Do not claim follower counts have been verified unless approved evidence supports them.
- Promotions involving incentives require applicable disclosure. Do not create automated group/profile publishing.
- Referral codes can attribute registrations, but do not constitute qualified or payable conversions.
- Do not enable either Vibsies Ads or finance posting or deploy to production until migrations, application tests, provider verification, security checks and policy/legal review pass.

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