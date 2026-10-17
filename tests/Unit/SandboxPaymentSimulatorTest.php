<?php
namespace Tests\Unit;
use App\Services\Payments\BookingQuote;
use App\Services\Payments\SandboxPaymentSimulator;
use LogicException;
use Tests\TestCase;
class SandboxPaymentSimulatorTest extends TestCase {
 public function test_success_is_only_a_simulation():void {
  $result=(new SandboxPaymentSimulator(new BookingQuote()))->simulate(10000,'EGP');
  $this->assertSame('simulated',$result['mode']);
  $this->assertSame('authorized_simulation',$result['result']);
  $this->assertFalse($result['funds_moved']);
  $this->assertFalse($result['payment_intent_created']);
  $this->assertSame(11500,$result['quote']['total_minor']);
 }
 public function test_declines_do_not_move_money():void {
  $result=(new SandboxPaymentSimulator(new BookingQuote()))->simulate(10000,'EGP','declined');
  $this->assertSame('declined_simulation',$result['result']);
  $this->assertFalse($result['funds_moved']);
 }
 public function test_capture_is_disabled():void {
  $this->expectException(LogicException::class);
  (new SandboxPaymentSimulator(new BookingQuote()))->capture();
 }
}
