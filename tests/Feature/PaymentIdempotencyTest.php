<?php
namespace Tests\Feature;
use App\Services\Payments\PaymentIdempotency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;
class PaymentIdempotencyTest extends TestCase {
 use RefreshDatabase;
 public function test_repeated_identical_request_is_reserved_only_once():void {
  $service=new PaymentIdempotency();
  $this->assertTrue($service->reserve('checkout','booking-test-0001','{"total_minor":11500}'));
  $this->assertFalse($service->reserve('checkout','booking-test-0001','{"total_minor":11500}'));
  $this->assertDatabaseCount('payment_idempotency_keys',1);
  $this->assertDatabaseHas('payment_idempotency_keys',['operation'=>'checkout','status'=>'reserved']);
 }
 public function test_conflicting_duplicate_is_rejected():void {
  $service=new PaymentIdempotency();
  $service->reserve('checkout','booking-test-0002','{"total_minor":11500}');
  $this->expectException(LogicException::class);
  $service->reserve('checkout','booking-test-0002','{"total_minor":30000}');
 }
 public function test_same_key_can_be_scoped_to_different_operations():void {
  $service=new PaymentIdempotency();
  $this->assertTrue($service->reserve('checkout','booking-test-0003','payload'));
  $this->assertTrue($service->reserve('refund','booking-test-0003','payload'));
  $this->assertDatabaseCount('payment_idempotency_keys',2);
 }
}
