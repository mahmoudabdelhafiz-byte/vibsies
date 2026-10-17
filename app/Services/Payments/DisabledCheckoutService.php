<?php
namespace App\Services\Payments;
use LogicException;
/**
 * Explicit fail-closed checkout boundary until a real provider sandbox adapter,
 * verified webhooks and settlement lifecycle are implemented.
 */
final class DisabledCheckoutService {
 public function __construct(private BookingQuote $quotes){}
 public function quote(int $priceMinor,string $currency):array {
  return $this->quotes->calculate($priceMinor,$currency);
 }
 public function beginCheckout(string $bookingId,string $idempotencyKey):never {
  throw new LogicException('Checkout is not available. No payment provider is configured.');
 }
 public function handleWebhook(string $rawBody,array $headers):never {
  throw new LogicException('Payment webhooks are disabled.');
 }
}
