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

## Payment event inbox foundation

`payment_webhook_events` stores provider event identifiers and SHA-256 payload fingerprints, with a unique constraint to prevent duplicate identifiers per provider. `payment_idempotency_keys` reserves a separate schema for future checkout operations. These are **not active payment integrations**. There is no public webhook route, signature verification or settlement processing. The `PaymentEventInbox` service deliberately rejects production use until an authenticated provider adapter exists. Duplicate identifiers with conflicting payloads are rejected if detected before the unique insert, while the unique constraint prevents duplicate rows under concurrency.

Remaining prerequisites: provider selection, provider signature verification before calling the inbox, transactional processing and ledger postings, authenticated reconciliation, retry controls, retention and personal-data assessment, and sandbox integration tests.

## Reservation-only idempotency

`PaymentIdempotency::reserve()` creates a unique reservation for an operation and request key, based on a SHA-256 request fingerprint. Repeating the same payload returns `false`; changing the payload under the same key raises an exception. The database unique index is the concurrency guard. This service is **disabled in production** and does not conduct checkout, settlement, refund, or payout. Reservations do not expire or resolve automatically; proper lifecycle management and transactional provider integration remain pending.

## Transactional development journals

`LedgerPostingService` validates balanced debit/credit lines, confirms every account belongs to the intended currency, and records the transaction and entries in one database transaction. Duplicate external keys are rejected through application checks and the unique database constraint. It is disabled in production and **not connected** to provider webhooks or the payment simulator. Posting a development journal does not mean money was collected, held, refunded or paid out.

Before go-live: document the account chart and financial model, add settlement/reversal journal types and reconciliation controls, test concurrency and rollback in PostgreSQL, and integrate only provider-verified events.

## Read-only finance audit

Run `php artisan vibsies:audit-finance --limit=500` to inspect journal balance and count pending event records and reserved idempotency keys. No funds move, and no records are changed. This is **not** provider settlement reconciliation: the report has no bank/provider balance feeds and does not prove that payments settled. A zero-error result covers only the inspected journal sample.

## Offline sandbox settlement comparison

Run `php artisan vibsies:compare-settlement /path/to/sandbox-export.csv` to compare a manually supplied CSV against locally stored payment intent references. Required exact header: `provider_reference,currency,amount_minor,status`. The comparison is read-only, rejects duplicate references and malformed records, and limits input to 10,000 rows. It does not verify that the CSV originated from a payment provider or that funds settled. It also reports locally recorded provider references absent from the supplied CSV; ensure the CSV covers the intended full provider period to avoid misleading results. Use **test/sandbox exports only** and keep them outside the public web root.

### Reconciliation window

The offline comparator now requires `--from=YYYY-MM-DD --to=YYYY-MM-DD` (inclusive UTC dates) and restricts the **internal missing-from-CSV check** to payment intents created in that window. Example: `php artisan vibsies:compare-settlement sandbox.csv --from=2026-10-01 --to=2026-10-15`. CSV rows are not date-filtered because the current CSV schema has no transaction date, so export the corresponding provider period before comparing. Internal creation time is not the provider's settlement date; this is still an imperfect sandbox tool, not actual settlement reconciliation.
