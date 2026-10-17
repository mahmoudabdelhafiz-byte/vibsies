<?php
namespace Tests\Feature;
use App\Models\Booking;
use App\Models\CreatorProfile;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class PrivateDeliveryTest extends TestCase {
 use RefreshDatabase;
 public function test_guests_cannot_access_deliveries():void {
  $this->get('/bookings/00000000-0000-0000-0000-000000000000/deliveries/00000000-0000-0000-0000-000000000000/download')->assertRedirect('/login');
 }
 public function test_unrelated_user_cannot_download_private_video():void {
  Storage::fake('local');
  $creatorUser=User::factory()->create();$customer=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$creatorUser->id,'slug'=>'private-creator','display_name'=>'Private Creator','category'=>'UGC','status'=>'approved']);
  $package=\App\Models\CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Video','price_minor'=>1000,'currency'=>'EGP','is_active'=>true]);
  $booking=Booking::create(['customer_id'=>$customer->id,'creator_profile_id'=>$creator->id,'creator_package_id'=>$package->id,'brief'=>'Test booking for private video','price_minor'=>1000,'fee_minor'=>150,'currency'=>'EGP','status'=>'in_review']);
  Storage::disk('local')->put('private-deliveries/'.$booking->id.'/video.mp4','fake-video');
  $delivery=Delivery::create(['booking_id'=>$booking->id,'creator_user_id'=>$creatorUser->id,'version'=>1,'media_url'=>null,'storage_disk'=>'local','storage_path'=>'private-deliveries/'.$booking->id.'/video.mp4','mime_type'=>'video/mp4','file_size'=>10,'sha256'=>str_repeat('0',64),'status'=>'submitted']);
  $this->actingAs(User::factory()->create())->get(route('deliveries.download',[$booking,$delivery]))->assertForbidden();
  $this->actingAs($customer)->get(route('deliveries.download',[$booking,$delivery]))->assertOk();
 }
}
