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
class PrivateVideoPreviewTest extends TestCase {
 use RefreshDatabase;
 public function test_only_booking_participants_can_preview_private_video():void {
  Storage::fake('local');
  $creatorUser=User::factory()->create();
  $customer=User::factory()->create();
  $profile=CreatorProfile::create(['user_id'=>$creatorUser->id,'display_name'=>'Private Creator','slug'=>'private-preview-creator','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$profile->id,'title'=>'Video','price_minor'=>1000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $booking=Booking::create(['customer_id'=>$customer->id,'creator_profile_id'=>$profile->id,'creator_package_id'=>$package->id,'brief'=>'Private preview demonstration request','price_minor'=>1000,'fee_minor'=>150,'currency'=>'EGP','status'=>'in_review']);
  $path='private-deliveries/'.$booking->id.'/video.mp4';
  Storage::disk('local')->put($path,'video-example-data');
  $delivery=Delivery::create(['booking_id'=>$booking->id,'creator_user_id'=>$creatorUser->id,'version'=>1,'media_url'=>null,'storage_disk'=>'local','storage_path'=>$path,'mime_type'=>'video/mp4','file_size'=>18,'sha256'=>hash('sha256','video-example-data'),'status'=>'submitted']);
  $url=route('deliveries.preview',[$booking,$delivery]);
  $this->get($url)->assertRedirect('/login');
  $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
  $this->actingAs($customer)->get($url)->assertOk()->assertHeader('Content-Type','video/mp4');
  $this->actingAs($creatorUser)->get($url)->assertOk();
 }
}
