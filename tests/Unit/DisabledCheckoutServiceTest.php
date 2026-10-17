<?php
namespace Tests\Unit;
use App\Services\Payments\BookingQuote;
use App\Services\Payments\DisabledCheckoutService;
use LogicException;
use PHPUnit\Framework\TestCase;
class DisabledCheckoutServiceTest extends TestCase {
 public function test_quote_available_without_checkout():void {
  $service=new DisabledCheckoutService(new BookingQuote());
  $this->assertSame(11500,$service->quote(10000,'EGP')['total_minor']);
 }
 public function test_checkout_is_fail_closed():void {
  $service=new DisabledCheckoutService(new BookingQuote());
  $this->expectException(LogicException::class);
  $service->beginCheckout('booking-123','retry-key');
 }
 public function test_webhook_processing_is_fail_closed():void {
  $service=new DisabledCheckoutService(new BookingQuote());
  $this->expectException(LogicException::class);
  $service->handleWebhook('{}',[]);
 }
}
