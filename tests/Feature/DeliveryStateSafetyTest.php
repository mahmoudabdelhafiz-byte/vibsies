<?php
namespace Tests\Feature;
use App\Models\Booking;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class DeliveryStateSafetyTest extends TestCase {
 use RefreshDatabase;
 private function booking():array {
  $creator=User::factory()->create();
  $customer=User::factory()->create();
  $profile=CreatorProfile::create(['user_id'=>$creator->id,'slug'=>'creator-test','display_name'=>'Creator','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$profile->id,'title'=>'Demo','price_minor'=>1000,'currency'=>'EGP','is_active'=>true]);
  $booking=Booking::create(['customer_id'=>$customer->id,'creator_profile_id'=>$profile->id,'creator_package_id'=>$package->id,'brief'=>'Sample video request','price_minor'=>1000,'fee_minor'=>150,'currency'=>'EGP','status'=>'accepted']);
  return [$creator,$customer,$booking];
 }
 public function test_creator_cannot_upload_without_both_acceptances():void {
  Storage::fake('local');
  [$creator,$customer,$booking]=$this->booking();
  $this->actingAs($creator)->post(route('deliveries.store',$booking),[
   'video'=>UploadedFile::fake()->create('sample.mp4',250,'video/mp4')
  ])->assertStatus(409);
  $this->assertDatabaseCount('deliveries',0);
  $this->assertCount(0,Storage::disk('local')->allFiles('private-deliveries'));
 }
 public function test_customer_cannot_review_delivery_belonging_to_another_booking():void {
  [$creator,$customer,$booking]=$this->booking();
  $other=User::factory()->create();
  $otherProfile=CreatorProfile::create(['user_id'=>$other->id,'slug'=>'other-creator','display_name'=>'Other','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$otherProfile->id,'title'=>'Other demo','price_minor'=>1200,'currency'=>'EGP','is_active'=>true]);
  $otherBooking=Booking::create(['customer_id'=>$customer->id,'creator_profile_id'=>$otherProfile->id,'creator_package_id'=>$package->id,'brief'=>'Another video request','price_minor'=>1200,'fee_minor'=>180,'currency'=>'EGP','status'=>'in_review']);
  $delivery=Delivery::create(['booking_id'=>$otherBooking->id,'creator_user_id'=>$other->id,'version'=>1,'media_url'=>null,'status'=>'submitted']);
  $this->actingAs($customer)->post(route('deliveries.review',[$booking,$delivery]),['decision'=>'accepted'])->assertForbidden();
 }
}
