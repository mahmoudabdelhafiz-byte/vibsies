<?php
namespace Tests\Unit;
use App\Services\Payments\BookingQuote;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
class BookingQuoteTest extends TestCase {
 public function test_quote_is_deterministic_and_never_payable():void {
  $q=(new BookingQuote())->calculate(10000,'EGP');
  $this->assertSame(['price_minor'=>10000,'fee_minor'=>1500,'total_minor'=>11500,'currency'=>'EGP','payment_status'=>'disabled'],$q);
 }
 public function test_fee_rounds_half_up_in_minor_units():void {
  $this->assertSame(1,(new BookingQuote())->calculate(3,'SAR')['fee_minor']);
 }
 public function test_rejects_unsupported_amounts():void {
  $this->expectException(InvalidArgumentException::class);
  (new BookingQuote())->calculate(0,'EGP');
 }
}
