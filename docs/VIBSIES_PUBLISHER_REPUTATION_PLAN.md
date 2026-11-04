# Vibsies publisher reputation and performance reporting — implementation specification

Status: **planned**, not implemented. Track in draft PR #3 roadmap; implement in separate reviewed commits before public launch.

## Product principles

Ratings describe **publisher delivery reliability**, verified placement performance, and genuine post-campaign advertiser experience. They are **not** inferred from page follower/member counts, paid promotion, or self-reported impressions. Publisher identity verification does not imply audience or performance verification. Newly verified publishers show **New / Not enough verified campaigns**, not an invented 5-star score.

## 1. Data and verified inputs

- One advertiser review per `ad_campaign_offer_id`, submitted only by the advertiser who owns that campaign, after `confirmed` or `resolved_confirmed` publication evidence. Unique constraint by completed offer; no anonymous reviews, self reviews, staff-generated ratings or purchased ratings.
- Review form: overall experience (1–5), communication (1–5), delivery timeliness (1–5), quality/brief adherence (1–5), optional text (max 2000), and an evidence/reporting issue indicator.
- Performance report per publication: expected deliverable, submitted/published URL, actual publish/approval date, reach/views/clicks/interactions **when evidence exists**; evidence source `verified_platform`, `reviewed_export` or `self_reported`, coverage date and reviewer. Do not label self-reported statistics as verified.
- Where a campaign is disputed, cancelled, refunded or chargeback-affected, define review eligibility and transparent moderation treatment before publishing a score. No automatic assignment of poor ratings merely because an advertiser disputes an offer.
- Allow a publisher response and evidence-based review challenge; moderators may hide an abusive review with an audit trail, never silently rewrite review scores.

## 2. Scoring and transparency (proposed)

Use a displayable **two-part reputation model**, rather than claiming that user stars measure actual reach.

A. **Advertiser satisfaction**: verified-completion-only rating average on a 1–5 scale; optionally Bayesian shrinkage toward a neutral prior only after sufficient sample size. Display count and date of latest completed review. Never display a five-star default.

B. **Delivery & performance confidence**: a separate 0–100 operational measure using:
- 40% on-time completion and accepted delivery;
- 25% evidence completeness and provenance confidence;
- 20% verified performance against *agreed campaign goals*, only where corresponding measurable goal and actual data exist;
- 15% communication/responsiveness from eligible advertiser reviews.

**Missing metrics are marked N/A and the component weight is omitted/renormalized**, with an explicit confidence/coverage indicator; do not treat missing reach as zero or guarantee outcomes. Use a trailing 12-month window with minimum sample thresholds; show an unrated / insufficient-data state until enough independent campaigns are completed (initial threshold proposal: 3). Show counts, data freshness and source descriptions.

Publisher discovery should weight the satisfaction and delivery scores modestly **after** geographic/category suitability, verified ownership, placement eligibility and budget filtering. Explain recommendation reasons and avoid a popularity feedback loop that buries new publishers.

## 3. Advertiser reporting workflows

- Advertiser dashboard: after confirmed delivery, prompt once to review the publisher; show a compact performance report, submitted proof, evidence provenance, and dispute/report-issue action.
- Publisher dashboard: display private feedback and performance trend; provide factual response/challenge request with moderation history.
- Admin dashboard: flag outliers, duplicate reviews, report manipulation, fake screenshots, multi-account collusion and suspicious review timing. Record reviewer identity, review timestamps, previous/new moderation states and decision reasons.
- Separate a **public trust-facing summary** (aggregated, threshold-gated) from private campaign-specific metrics and advertiser notes.
- Rate limit review creation/reporting, guard reviews with authenticated ownership, immutable eligible campaign linkage, and audit all moderation transitions.

## 4. Proposed technical sequence

1. PostgreSQL migrations/models for `publisher_campaign_reviews`, `publisher_campaign_performance_reports`, `publisher_rating_summaries` (cache/materialized derived values) and moderation events, with unique offer/reviewer constraints.
2. Authorized post-delivery review endpoints; form validation, review revision window, response and appeal.
3. Evidence ingestion + review queue, timestamped provenance and freshness windows; analytics reports.
4. Deterministic scoring service with test fixtures for N/A, small samples, disputes, date decay, manipulation and role isolation. Version the formula.
5. Publisher profile scorecards and advertiser campaign reports (Arabic/English and mobile).
6. Integrate modest reputation signal into PublisherMatcher with explainable ranking; A/B-test fairness.
7. Regression suite on SQLite and PostgreSQL; manual security/privacy review; nonfinancial pilot.

## Definition of done
- No unverified follower or engagement statistic influences a public rating.
- Cannot review an uncompleted placement, review one's own account, or submit multiple reviews for the same offer.
- Ratings have counts, freshness, confidence and moderation controls.
- Disputes and advertiser-reporting abuse can be reviewed without rewriting historical evidence.
- Normal and founding publisher compensation do not buy better ratings.
- Bilingual accessible UI and zero additional payments enabled by ratings.

## Launch dependencies

Requires actual campaign fulfillment and trustworthy metric collection. Build core review storage and workflow before launch; only display public composite ratings after enough authentic eligible outcomes exist.
