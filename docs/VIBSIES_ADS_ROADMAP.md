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

## Added in phase 6B (provider-neutral finance integration boundary)
- A typed payment gateway interface and verified-event data contract define the boundary for future provider integration.
- The default gateway categorically refuses checkout and webhook processing. No live provider, gateway keys, payment URL, payout API or public webhook endpoint has been introduced.
- Unique provider/event IDs are reserved in a migration for idempotent future webhook processing.
- A read-only reconciler checks existing held ledger batches for balanced entries, correct split, delivery status and payment-proof consistency. It DOES NOT validate against bank/provider settlement statements.
- Five safety/integrity feature tests committed but NOT EXECUTED.
- Still required before any real funds: select a supported licensed provider, independently verify signatures and payment state, process events idempotently and in order, bank/gateway statement matching, refunds/chargebacks, payout approval and reconciliation, taxes and security signoff.

## Phase 7: GitHub Actions test baseline (2026-11-04)
- Added `.github/workflows/laravel-ci.yml` for PHP 8.3, Composer validation/install, PHP syntax lint, fresh isolated SQLite migrations, and full PHPUnit execution.
- Fixed existing PHP namespace syntax in `CompareSandboxSettlement.php`, created writable Laravel directories in CI, added `Tests\\` PSR-4 autoload and missing Mockery/Faker dev dependencies.
- Corrected incorrect legacy test fixtures (15% fee rounding and YouTube watch URLs) without loosening production validation.
- **Verified green CI:** GitHub Actions run 41897344566 on feature branch; **138 tests, 373 assertions passed**, plus PHP syntax and SQLite migrations.
- PostgreSQL migration/integration, concurrent payment/settlement cases, external payment provider flows, security review, bilingual UX, and production cutover are still unverified. No merge or deployment has occurred.
- The Ads and finance posting flags remain disabled by default.

## Phase 8A: PostgreSQL and verification security (2026-11-05)
- Added PostgreSQL 16 service-based CI and consolidated it with the SQLite Laravel workflow.
- **Verified green CI:** GitHub Actions run 41914827737: **139 tests and 378 assertions PASS** against SQLite, and **139 tests and 378 assertions PASS** against PostgreSQL 16. Fresh migrations PASS on both engines.
- Publisher property administrator approval now requires a documented review explanation (minimum 12 characters), and repeated verification of an already reviewed property is rejected. A dedicated security regression test was added.
- No credentials, real data or production databases were used. PostgreSQL-specific schema compatibility is verified by CI, but production migration/rollback, permissions, workload and concurrency still require environment-specific review.
- Provider signatures, refunds, financial provider reconciliation, privacy/abuse review, bilingual UX, and final readiness signoff remain open. Both feature flags remain OFF.

## Phase 8B: authorization and release-readiness hardening (2026-11-05)
- Normalize new account email addresses before uniqueness validation on PostgreSQL; block case-variant duplicate registrations and attempted self-referral.
- Manual publisher review is transactional and rejects repeat approval under concurrent requests. Require a written administrator review note; allow rejecting spam applications without ownership proof, while approval still requires ownership evidence.
- Added four regression tests for account normalization, rejection without evidence, self-referral isolation, and foreign-property authorization.
- Added `docs/VIBSIES_ADS_RELEASE_READINESS.md` with controlled deployment, backup/restore, rollback and business/legal release gates.
- **Verified Phase 8B green CI:** GitHub Actions run 41915553129, SQLite **143 tests / 392 assertions passed**, PostgreSQL 16 **143 tests / 392 assertions passed**, migrations passed on both.
- CI passing is not production readiness approval.

## Phase 8C: publisher challenge and claim-abuse safeguards (2026-11-05)
- Each new publisher property has a randomly generated 16-character code and seven-day challenge expiry. Owners can submit a public, same-platform evidence URL; the public code should appear on the controlled property bio/post.
- Admin approval requires evidence submitted within the challenge validity window and an explicit attestation that the exact code was independently checked. A public Vibsies promotional post is never a verification requirement.
- Review decisions and evidence submissions are recorded in `publisher_verification_events` with actor, action and timestamp. These are application audit records, not yet tamper-proof external logging.
- New registrations normalize common mobile/www host variants into a unique SHA-256 social-property URL fingerprint. Existing/legacy records have nullable fingerprints; backfill and conflict review are required before any production rollout.
- Owner-only evidence submission is rate-limited and cannot modify another publisher's account. No passwords, account access tokens or automated social posting are requested.
- **Verified green CI:** run 41916454543, **149 tests / 414 assertions** passed on SQLite and **149 tests / 414 assertions** passed on PostgreSQL 16, including fresh migrations.
- Still pending: challenge renewal support, staff audit log access controls and retention, social proof authenticity beyond manual inspection, multi-account referral ring detection and privacy review.

## Phase 8D: challenge renewal, referral review and evidence privacy (2026-11-05)
- Owners may renew an expired, pending ownership challenge twice. The challenge rotates to a new random code, lasts another seven days, clears stale evidence and records an audit event.
- Owners may remove pending ownership evidence; the withdrawal action is recorded without copying the underlying contents to the audit trail.
- Referral registrations remain **unqualified** and produce no earnings. An administrator may manually review or flag a registration, with a documented reason and actor/timestamp audit event. Review is one-time to reduce tampering.
- These controls do not assert that fraud has been eliminated. Further checks for collusive referral rings, automated abuse and identity verification are still pending.
- Data policy still needs legal review: retention periods, export/erasure mechanics and external backups must be defined prior to launch.
- Renewals and referrals have separate user-facing and administrator-only endpoints, protected by ownership/role checks. Both Ads and finance flags remain OFF by default.
- **Verified dual-database CI:** run 41917463296: **155 tests, 437 assertions passed** on both SQLite and PostgreSQL 16; migrations passed.

## Phase 9A: Arabic/English Ads UI and nonfinancial pilot preparation (2026-11-05)
- Added `lang/en/ads.php` and `lang/ar/ads.php` catalogs and applied translated key controls to advertiser campaign, recommendations, publisher, offers and administrator screens.
- Added shared responsive Ads styling with mobile-friendly form controls, visible focus outlines, responsive sections and RTL-aware direction.
- Language switching preserves the same-origin previous page rather than returning to the homepage; cross-domain referrers continue to fall back to the homepage.
- Added six feature tests checking Arabic RTL page rendering, translated key controls, English LTR fallback, administrator authorization and safe language return.
- Added `docs/VIBSIES_ADS_PILOT_PLAN.md` for a consent-based, nonfinancial staging pilot focused on city/category relevance rather than follower-count claims.
- Remaining UI work: translate secondary descriptions and validation notices, complete accessibility/manual screen-reader checks, locale-aware number/date formatting and Arabic proofreading. Do not claim full bilingual parity yet.
- **Verified green CI:** run 41918710093: SQLite **161 tests, 461 assertions** PASS; PostgreSQL 16 **161 tests, 461 assertions** PASS; fresh migrations PASS on both.
- Do not enable production Ads or finance posting based solely on CI. No pilot participants were contacted and no pilot executed.

## Phase 9B: translation completion and interface accessibility (2026-11-05)
- Expanded shared Arabic/English Ads catalogs to 211+ paired translation keys covering onboarding instructions, trust disclosures, advertiser/publisher review actions, statuses and key help text.
- Replaced remaining visible high-priority English-only interface labels in publisher, advertiser, offer and admin screens. Full Arabic human proofreading and production interaction testing remain outstanding.
- Added descriptive accessibility labels to admin review text areas and preserved explicit labels on user-facing forms; shared mobile and RTL stylesheet remains active.
- Added automated translation parity tests checking that every Ads translation key used in five Blade views exists in both language catalogs, plus Arabic rendering assertions.
- **Verified dual-database CI** run 41919649572: **165 tests / 1,278 assertions passed** on both SQLite and PostgreSQL 16; fresh migrations passed.
- This is not a claim of full WCAG conformance: manual screen-reader, contrast, keyboard, narrow viewport and translation review are release gates.
- Financial posting and Vibsies Ads feature flags remain OFF; no production deployment, public campaign or paid pilot.

## Newly scheduled workstreams — Publisher Ratings and SEO (2026-11-05)

### Phase 10A — Publisher ratings, advertiser reporting and trust
- [ ] Capture one authentic rating per eligible completed advertising offer, only from its advertiser; include delivery, communication, quality and overall feedback.
- [ ] Store campaign performance reports with dated, reviewed evidence/provenance (self-reported versus independently verified), no unsupported claims about real reach or sales.
- [ ] Display a separate advertiser satisfaction score and operational delivery/performance confidence; publish ratings only after minimum eligible campaigns, with sample size and evidence labels.
- [ ] Publisher responses, advertiser reporting, admin moderation, appeals, audit events, anti-fraud checks and privacy/retention rules.
- [ ] Carefully incorporate reputation into matching **after** category/city/placement/budget relevance; protect new publishers from cold-start exclusion.
- [ ] Tests on SQLite/PostgreSQL, Arabic/English/mobile UX and controlled pilot before any public score is shown.
- Detailed implementation contract: `docs/VIBSIES_PUBLISHER_REPUTATION_PLAN.md`. **Planned only, no ratings have been implemented.**

### Phase 10B — Enhanced SEO for Vibsies public website (start technical foundation early)
- [ ] Shared meta title/description, canonical URL, social preview tags and appropriate structured data for public pages.
- [ ] Sitemap and robots strategy: index only active approved creator/publisher profiles and valuable public landing pages; exclude authenticated Ads management, private campaigns and referral URLs.
- [ ] Canonicalization, page titles/headings, filter/index controls, bilingual `hreflang`, performance and internal linking.
- [ ] Valuable Arabic/English pages for creator booking, Facebook community advertising, Instagram creators and Egypt/Saudi regional intent; no thin autogenerated pages.
- [ ] Search Console/Bing setup when domain access permits, indexing/CTR/Core Web Vitals monitoring, and automated SEO regression tests.
- [ ] Genuine approved ratings may eventually support profile reputation, **not invented aggregate-rating schema**.
- Details: `docs/VIBSIES_SEO_ROADMAP.md`. **Planned only, no public SEO changes have been deployed.**

**Sequence:** implement SEO technical fundamentals alongside release stabilization; implement publisher review storage/workflow next; defer public ranking influence and review markup until enough authenticated post-campaign data exists. Preserve disabled Ads/finance flags and draft PR isolation.

## Phase 10A initial implementation — advertiser feedback and performance evidence (2026-11-05)
- Added `publisher_campaign_reviews` and `publisher_campaign_performance_reports` schema, with one review/report per campaign offer, publisher linkage, advertiser identity and moderation/provenance fields.
- Only the campaign-owning advertiser can submit ratings after a `confirmed` or `resolved_confirmed` placement; duplicate reviews and publisher self-reviews are denied.
- Publisher can submit one dated performance report after posting; reach/views/clicks/interactions remain `self_reported` and `unverified` until independent review, not public claims.
- Administrators may approve/reject advertiser feedback only once, with a reason. Moderation does not issue compensation.
- Initial reputation service shows **Unrated** until at least three approved completed offer reviews from three independent advertisers in the past twelve months. No public 5-star default, score-derived matchmaking or publisher public AggregateRating schema.
- Added Arabic/English advertiser rating and publisher metrics forms plus admin review queue, with feature tests for eligibility, duplicate review and unverified reports.
- Still needed: evidence authentication, performance review/admin provenance workflow, public scorecard thresholds, appeal process, scoring weight validation and fraud controls.

## Phase 10B initial implementation — technical SEO foundation (2026-11-05)
- Added public `/sitemap.xml` generated from homepage and approved active creator profiles only, and `/robots.txt` with sitemap discovery and exclusions for private/Ads/referral paths.
- Shared public layout now has title, description, canonical, Open Graph tags and filtered-search noindex. Marketplace and eligible creator portfolio pages have distinct meta descriptions.
- Added tests for sitemap inclusion/exclusion, robots, canonical and filtered listing index rules.
- Still needed: verify production host/HTTPS canonical rules, sitemap size limits/caching, SEO locale URL/hreflang design, richer public pages, structured data when eligible, Search Console and production crawl audits.
- Neither workstream has been deployed; Ads and financial posting remain disabled by default.

## Remaining (implement sequentially)
1. Complete and stabilize Laravel baseline migration and tests (authentication, existing bookings, deliveries, contracts, financial controls).
2. Implement proper verification challenges and evidence review history, secure asset uploads and resubmission.
3. ~~Property-specific ad package types~~ Initial inventory foundation committed. Enhance descriptions, upload evidence, revision and auditing.
4. ~~Dated audience metrics~~ Initial snapshots and manual review committed. Add robust platform evidence validation and reporting.
5. ~~Advertiser campaign drafts and initial matching~~ Added. Pending: creative review, campaign approvals, purchasing and fulfillment.
6. ~~Offer, acceptance, publication evidence, cancellation, expiry and basic dispute decisions~~ Initial code added. Pending: comprehensive audit logging, scheduling, resubmission, formal agreements and financial reconciliation.
7. ~~Referral links, registration attribution and eligibility record~~ Initial code committed. Pending: creative templates, fraud/qualification checks, click reporting and accounting-grade reward redemption.
8. ~~Internal ledger, provider interface and read-only diagnostics~~ Added. Still required: reviewed provider implementation, signed webhooks, gateway/bank settlement data, refunds/reversals, taxes/fees, regulated payouts, reconciliation and audit approval.
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