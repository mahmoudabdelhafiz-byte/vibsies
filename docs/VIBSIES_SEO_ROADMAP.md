# Vibsies SEO enhancement roadmap

Status: **planned, not deployed**. This specification covers both the creator marketplace and the new publisher/advertiser marketplace. Changes require review, tests, and verified public URL/domain configuration.

## Audit baseline from repository review
- Public layout `resources/views/layouts/public.blade.php` exposes a basic title but does not currently expose dedicated meta description, canonical URL, Open Graph / Twitter metadata or JSON-LD slots.
- Public marketplace exists at `/`; approved creator profiles exist at `/creators/{slug}`.
- There were no committed `public/robots.txt` or `public/sitemap.xml` files at the checked paths. Confirm the serving environment and route responses before deciding final implementation.
- Search/filter URLs use query parameters; review canonical/noindex handling to avoid duplicate thin search results.
- Ads advertiser and publisher management routes are authenticated. They should remain excluded from indexes and all XML sitemaps. Referral redirect pages should not be indexed.
- Do not publish empty geographic/category landing pages, fabricated reviews, fake followers, or unearned Rating schema markup.

## SEO implementation order

### SEO-1 — Technical and crawlability foundations (highest priority)
1. Establish production base domain and HTTPS host policy using canonical configuration (do not assume an unconfirmed domain); enforce http→https and preferred www/non-www host through server settings.
2. Add descriptive unique title/description, canonical and Open Graph metadata slots to the shared public layout, with sensible escaped fallbacks.
3. Add `robots.txt` and a generated XML sitemap endpoint for *only* active public indexable pages; exclude auth/admin/private campaigns, unapproved creators, unpublished profiles and referral links.
4. Build sitemap index or paged chunks as traffic grows; include accurate `lastmod`, and only verified available language variants.
5. Add `noindex,follow` for thin search/filter/pagination states where appropriate; avoid blocking pages required for discovery. Use 404/410 for removed profiles; handle canonical URLs for sort/filter parameters.
6. Add automatic feature tests for sitemap exclusions, canonical URL, redirects, pagination, metadata escaping and unapproved profile protections.

### SEO-2 — Structured data and social sharing
1. `Organization` / `WebSite` with real publicly supportable names, URLs, logos and social channels; do not fabricate address, ratings or telephone.
2. `ProfilePage` and `Person` or `Organization` data for eligible, public, reviewed creator/publisher profiles, reflecting visible facts only.
3. `BreadcrumbList` for multi-level landing pages.
4. Only emit `AggregateRating` markup where genuine, displayed, review-policy-compliant ratings exist and the specific schema/search-engine eligibility rules allow it; do not promise rich results or mark up self-serving ratings.
5. Dynamic Open Graph preview image, share title and description per approved public profile.

### SEO-3 — Intent-focused landing pages in Arabic and English
- Create valuable, non-duplicative educational/marketplace pages for terms such as **Facebook group advertising in Egypt**, **advertise on community pages in Cairo**, **Instagram creators in Saudi Arabia**, **micro-influencer campaigns**, **local audience advertising**, **book UGC creators**, and **social publisher marketplace**.
- Prioritize pages with real supply and useful editorial content. Define differentiated FAQs, pricing explanations, trust criteria, regional limitations and internal linking; avoid creating thousands of thin city/category combinations.
- Provide equivalent Arabic/English pages with correct `hreflang` only when translated content and canonical links truly exist. Account for RTL, locale-specific titles and native-language copy.
- Add explanatory publisher performance / trust criteria pages, without claims that current metrics are verified.

### SEO-4 — Measurement and continuous optimization
- Connect/verify Google Search Console and Bing Webmaster Tools **only after authorized domain ownership is available**.
- Add privacy-conscious analytics for organic entrances, creator-profile views, publisher application starts, advertiser signups and qualified campaign leads; avoid exposing private deal/referral information.
- Monitor indexing status, sitemap coverage, crawled-but-not-indexed, Core Web Vitals, structured data eligibility, click-through rates and broken links.
- Review internal links, headings, image alt text, accessible navigation and page speed; optimize image size, caching and rendering.
- A/B test copy and landing-page conversion without cloaking or misleading claims.

## Acceptance criteria
- Canonical and meta tags are stable/escaped on each public indexable page.
- Public sitemap contains only approved active pages; private campaign dashboards, auth paths and referral redirects never appear.
- No fake ratings, testimonials or audience statistics in metadata or structured data.
- Arabic and English URL relationships are accurate and have real published content.
- Core web performance and indexing are measured against a recorded baseline, not a promised ranking.
- CI tests pass on PostgreSQL and SQLite; Search Console setup and production checks documented.

## Dependencies
SEO foundations can proceed now without payments. Publisher public profile pages and their reputation markup should wait for moderation/privacy and genuine performance evidence. No production release is implied by this plan.
