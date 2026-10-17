<?php
namespace Tests\Feature;
use App\Models\Agreement;
use App\Models\Booking;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class BookingRevisionLimitTest extends TestCase {
 use RefreshDatabase;
 public function test_zero_revision_package_rejects_change_request():void {
  $customer=User::factory()->create();$owner=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Revision Artist',
   'slug'=>'revision-artist','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Promo','price_minor'=>10000,
   'currency'=>'EGP','revision_limit'=>0,'moderation_status'=>'approved','is_active'=>true]);
  $booking=Booking::create(['customer_id'=>$customer->id,'creator_profile_id'=>$creator->id,
   'creator_package_id'=>$package->id,'brief'=>'Video for our upcoming new product promotion',
   'price_minor'=>10000,'fee_minor'=>1500,'currency'=>'EGP','status'=>'in_review']);
  $delivery=Delivery::create(['booking_id'=>$booking->id,'creator_user_id'=>$owner->id,'version'=>1,
   'media_url'=>'','status'=>'submitted']);
  $this->actingAs($customer)->post(route('deliveries.review',[$booking,$delivery]),
   ['decision'=>'revisions_requested'])->assertStatus(409);
  $this->assertSame('submitted',$delivery->fresh()->status);
  $this->assertSame('in_review',$booking->fresh()->status);
 }
 public function test_delivery_approval_requires_accepted_integrity_checked_agreement():void {
  $customer=User::factory()->create();$owner=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Approval Creator',
   'slug'=>'approval-creator','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Promo',
   'price_minor'=>10000,'currency'=>'EGP','moderation_status'=>'approved','is_active'=>true]);
  $booking=Booking::create(['customer_id'=>$customer->id,'creator_profile_id'=>$creator->id,
   'creator_package_id'=>$package->id,'brief'=>'Video content request with missing agreement',
   'price_minor'=>10000,'fee_minor'=>1500,'currency'=>'EGP','status'=>'in_review']);
  $delivery=Delivery::create(['booking_id'=>$booking->id,'creator_user_id'=>$owner->id,
   'version'=>1,'media_url'=>'','status'=>'submitted']);
  $this->actingAs($customer)->post(route('deliveries.review',[$booking,$delivery]),
   ['decision'=>'accepted'])->assertStatus(409);
  $this->assertSame('submitted',$delivery->fresh()->status);
  $this->assertSame('in_review',$booking->fresh()->status);
 }
}
