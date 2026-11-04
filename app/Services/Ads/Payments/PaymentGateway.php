<?php

namespace App\Services\Ads\Payments;

use App\Models\AdCampaignOffer;

/**
 * Provider integration boundary. Implement only after provider selection,
 * signed webhook specification, settlement contract, and security review.
 */
interface PaymentGateway {
    public function createCheckout(AdCampaignOffer $offer): array;
    public function verifyWebhook(string $rawBody, array $headers): VerifiedPaymentEvent;
}