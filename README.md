# Vibsies — Creator Marketplace

Barmageyat's influencer and UGC booking marketplace. **Development prototype only**: do not deploy publicly or accept real payments yet.

## Run locally

Requires Node.js 22.13+ (built-in experimental SQLite).

```bash
npm start
# http://localhost:3000
npm test
```

SQLite is created under `data/` and excluded from Git. No external dependencies.

## Included
- Public creator directory and responsive landing page
- Account registration and sign-in
- Creator profiles and priced service packages
- Customer booking requests and 15% fee snapshot
- Customer and creator booking dashboards

## Not yet implemented
Business organization accounts, Arabic translation, verified identity, media uploads, legal e-signatures, payment capture, refunds, payouts, moderation and dispute resolution. No payment is processed and a booking is **not** a binding agreement.

## Branches
`develop`: implementation; `main`: release baseline.

## Security
Prototype only. Use a real security review, CSRF protections, rate limiting, verification and secure deployment configuration before exposing to the internet.