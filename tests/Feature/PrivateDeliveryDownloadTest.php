<?php
namespace Tests\Feature;
use App\Models\Booking;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class PrivateDeliveryDownloadTest extends TestCase {
 use RefreshDatabase;
 public function test_private_download_rejects_non_video_metadata_and_foreign_users():void {
  Storage::fake('local');
  $owner=User::factory()->create();
  $customer=User::factory()->create();
  $outsider=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Private Artist',
   'slug'=>'private-artist','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Video',
   'price_minor'=>10000,'currency'=>'EGP','moderation_status'=>'approved','is_active'=>true]);
  $booking=Booking::create(['customer_id'=>$customer->id,'creator_profile_id'=>$creator->id,
   'creator_package_id'=>$package->id,'brief'=>'A private branded media creation request',
   'price_minor'=>10000,'fee_minor'=>1500,'currency'=>'EGP','status'=>'in_review']);
  $path='private-deliveries/'.$booking->id.'/evidence.txt';
  Storage::disk('local')->put($path,'private');
  $delivery=Delivery::create(['booking_id'=>$booking->id,'creator_user_id'=>$owner->id,
   'version'=>1,'storage_disk'=>'local','storage_path'=>$path,'mime_type'=>'text/plain','status'=>'submitted']);
  $this->actingAs($customer)->get(route('deliveries.download',[$booking,$delivery]))->assertStatus(415);
  $this->actingAs($outsider)->get(route('deliveries.download',[$booking,$delivery]))->assertForbidden();
 }
}
