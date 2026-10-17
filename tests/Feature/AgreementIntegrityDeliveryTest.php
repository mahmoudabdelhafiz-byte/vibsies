<?php
namespace Tests\Feature;
use App\Models\Agreement;
use App\Models\Booking;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class AgreementIntegrityDeliveryTest extends TestCase {
 use RefreshDatabase;
 public function test_tampered_agreement_cannot_proceed_to_delivery():void {
  Storage::fake('local');
  $creatorUser=User::factory()->create();
  $customer=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$creatorUser->id,'display_name'=>'Video Creator','slug'=>'hash-creator','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Video','price_minor'=>1000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $booking=Booking::create(['customer_id'=>$customer->id,'creator_profile_id'=>$creator->id,'creator_package_id'=>$package->id,'brief'=>'Please make a demonstration video.','price_minor'=>1000,'fee_minor'=>150,'currency'=>'EGP','status'=>'accepted']);
  Agreement::create(['booking_id'=>$booking->id,'terms'=>['price_minor'=>999999],'terms_sha256'=>hash('sha256','original terms'),'customer_accepted_at'=>now(),'creator_accepted_at'=>now()]);
  $this->actingAs($creatorUser)->post(route('deliveries.store',$booking),['video'=>UploadedFile::fake()->create('video.mp4',50,'video/mp4')])->assertStatus(409);
  $this->assertDatabaseCount('deliveries',0);
  $this->assertCount(0,Storage::disk('local')->allFiles('private-deliveries'));
 }
}
