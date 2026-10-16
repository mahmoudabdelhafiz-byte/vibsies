<?php
namespace Tests\Feature;
use App\Services\Payments\PaymentEventInbox;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;
class PaymentEventInboxTest extends TestCase {
 use RefreshDatabase;
 public function test_duplicate_event_is_ignored_without_duplicate_record():void {
  $inbox=new PaymentEventInbox();
  $this->assertTrue($inbox->record('demo','evt-01','{"status":"test"}'));
  $this->assertFalse($inbox->record('demo','evt-01','{"status":"test"}'));
  $this->assertDatabaseCount('payment_webhook_events',1);
 }
 public function test_same_id_with_different_payload_is_rejected():void {
  $inbox=new PaymentEventInbox();
  $inbox->record('demo','evt-02','original');
  $this->expectException(LogicException::class);
  $inbox->record('demo','evt-02','altered');
 }
}
