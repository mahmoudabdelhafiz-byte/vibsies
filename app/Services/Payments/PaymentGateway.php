<?php
namespace App\Services\Payments;
/**
 * Contract for a future verified payment-provider sandbox integration.
 * Implementations must use integer minor units and provider idempotency.
 */
interface PaymentGateway {
 public function createCheckout(string $bookingId,int $amountMinor,string $currency,string $idempotencyKey):array;
 public function verifyWebhook(string $rawBody,array $headers):array;
}
