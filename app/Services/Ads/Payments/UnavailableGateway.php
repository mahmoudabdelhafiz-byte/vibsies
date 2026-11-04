<?php

namespace App\Services\Ads\Payments;

use App\Models\AdCampaignOffer;
use LogicException;

final class UnavailableGateway implements PaymentGateway {
    public function createCheckout(AdCampaignOffer $offer): array {
        throw new LogicException('No reviewed payment provider is configured. Checkout disabled.');
    }
    public function verifyWebhook(string $rawBody,array $headers): VerifiedPaymentEvent {
        throw new LogicException('No reviewed payment provider is configured. Webhook processing disabled.');
    }
}