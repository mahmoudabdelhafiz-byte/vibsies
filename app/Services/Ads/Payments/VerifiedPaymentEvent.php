<?php

namespace App\Services\Ads\Payments;

final readonly class VerifiedPaymentEvent {
    public function __construct(
        public string $provider,
        public string $eventId,
        public string $providerPaymentReference,
        public string $eventType,
        public int $amountMinor,
        public string $currency,
        public string $offerId,
    ) {}
}