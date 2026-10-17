<?php
namespace Tests\Feature;
use App\Models\Booking;
use App\Models\Agreement;
use App\Services\Agreements\AgreementIntegrity;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\Dispute;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DisputeResolutionLifecycleTest extends TestCase {
 use RefreshDatabase;
 private function booking(string $status):array {
  $customer=User::factory()->create();
  $creatorUser=User::factory()->create();
  $profile=CreatorProfile::create(['user_id'=>$creatorUser->id,'display_name'=>'Dispute Creator','slug'=>'dispute-creator','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$profile->id,'title'=>'Video','price_minor'=>10000,'currency'=>'EGP','moderation_status'=>'approved','is_active'=>true]);
  $booking=Booking::create(['customer_id'=>$customer->id,'creator_profile_id'=>$profile->id,'creator_package_id'=>$package->id,'brief'=>'A sample video content request for disputes','price_minor'=>10000,'fee_minor'=>1500,'currency'=>'EGP','status'=>$status]);
  return [$booking,$customer];
 }
 public function test_undecided_booking_cannot_enter_dispute_state():void {
  [$booking,$customer]=$this->booking('requested');
  $this->actingAs($customer)->post(route('disputes.store',$booking),['reason'=>'Please investigate the issue with this order'])->assertStatus(409);
  $this->assertSame('requested',$booking->fresh()->status);
 }
 public function test_admin_resolution_returns_disputed_booking_to_accepted_without_delivery():void {
  [$booking,$customer]=$this->booking('disputed');
  $terms=['version'=>1,'booking_id'=>$booking->id,'customer_id'=>$customer->id];
  Agreement::create(['booking_id'=>$booking->id,'terms'=>$terms,'terms_sha256'=>AgreementIntegrity::digest($terms)]);
  $dispute=Dispute::create(['booking_id'=>$booking->id,'raised_by'=>$customer->id,'reason'=>'Please investigate the issue with this order','status'=>'open']);
  $admin=User::factory()->create(['is_admin'=>true]);
  $this->actingAs($admin)->post(route('admin.disputes.decide',$dispute),['decision'=>'resolved','reason'=>'Investigation completed and booking can continue'])->assertRedirect();
  $this->assertSame('resolved',$dispute->fresh()->status);
  $this->assertSame('accepted',$booking->fresh()->status);
 } public function test_dispute_resolution_does_not_resume_without_agreement():void {
  [$booking,$customer]=$this->booking('disputed');
  $dispute=Dispute::create(['booking_id'=>$booking->id,'raised_by'=>$customer->id,'reason'=>'Please investigate the issue with this order','status'=>'open']);
  $admin=User::factory()->create(['is_admin'=>true]);
  $this->actingAs($admin)->post(route('admin.disputes.decide',$dispute),[
   'decision'=>'resolved','reason'=>'The investigation has concluded'
  ])->assertStatus(409);
  $this->assertSame('open',$dispute->fresh()->status);
  $this->assertSame('disputed',$booking->fresh()->status);
 }
 public function test_escalated_dispute_can_be_resolved_but_not_escalated_twice():void {
  [$booking,$customer]=$this->booking('disputed');
  $terms=['version'=>1,'booking_id'=>$booking->id,'customer_id'=>$customer->id];
  Agreement::create(['booking_id'=>$booking->id,'terms'=>$terms,'terms_sha256'=>AgreementIntegrity::digest($terms)]);
  $dispute=Dispute::create(['booking_id'=>$booking->id,'raised_by'=>$customer->id,
   'reason'=>'Unresolved delivery disagreements','status'=>'escalated']);
  $admin=User::factory()->create(['is_admin'=>true]);
  $this->actingAs($admin)->post(route('admin.disputes.decide',$dispute),[
   'decision'=>'escalated','reason'=>'Duplicate escalation is not allowed'
  ])->assertStatus(409);
  $this->actingAs($admin)->post(route('admin.disputes.decide',$dispute),[
   'decision'=>'resolved','reason'=>'Escalated dispute reviewed and resolved'
  ])->assertRedirect();
  $this->assertSame('resolved',$dispute->fresh()->status);
  $this->assertSame('accepted',$booking->fresh()->status);
 }
 public function test_delivered_dispute_cannot_resume_without_both_signatures():void {
  [$booking,$customer]=$this->booking('disputed');
  $terms=['version'=>1,'booking_id'=>$booking->id,'customer_id'=>$customer->id];
  Agreement::create(['booking_id'=>$booking->id,'terms'=>$terms,'terms_sha256'=>AgreementIntegrity::digest($terms)]);
  Delivery::create(['booking_id'=>$booking->id,'creator_user_id'=>$booking->creator->user_id,
   'version'=>1,'media_url'=>'','status'=>'submitted']);
  $dispute=Dispute::create(['booking_id'=>$booking->id,'raised_by'=>$customer->id,
   'reason'=>'Submitted work must not bypass consent','status'=>'open']);
  $admin=User::factory()->create(['is_admin'=>true]);
  $this->actingAs($admin)->post(route('admin.disputes.decide',$dispute),[
   'decision'=>'resolved','reason'=>'Attempted resolution without signed agreement'
  ])->assertStatus(409);
  $this->assertSame('disputed',$booking->fresh()->status);
  $this->assertSame('open',$dispute->fresh()->status);
 }
}
