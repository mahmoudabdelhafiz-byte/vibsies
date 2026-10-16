# Verification and finance foundation

New verification and financial tables are design scaffolding only. Payment processing is disabled. Do not record real transactions until provider integration, balanced postings and reconciliation are validated.

English and Arabic localization starts with translation strings; not all pages are translated.

Current functionality must undergo PHP, migration and integration testing before any public launch.

## Booking quote foundation (development only)

`App\\Services\\Payments\\BookingQuote` calculates an integer-minor-unit customer price, 15% platform fee, and displayed total. It never contacts a payment provider, creates a payment intent, captures money, or issues payouts. `DisabledPaymentGateway` continues to reject checkout/capture/refund/payout attempts. Changes to the fee model require updated agreements, commercial approval, and accounting tests.

Provider sandbox integration remains **pending**. Before implementation, choose an actual provider supporting the launch countries, obtain test credentials, define transaction idempotency and webhook authentication, build balanced ledger postings with reconciliation, and test failures/refunds/chargebacks. No live credential or production payment toggle should be introduced into `develop`.

## Isolated development simulator

`SandboxPaymentSimulator` supports deterministic `success`, `declined`, and `timeout` scenarios in non-production environments. It is not connected to routes, actual providers, payment intents, card data, external services or the ledger. Its “success” means **simulated authorization only** and never authorizes delivery or settlement. Real capture, refund and payout methods throw exceptions. Production invocation is explicitly rejected.

A provider sandbox integration (with verified webhooks, replay protection, idempotency and settlement reconciliation) must be completed separately before any checkout is offered.
