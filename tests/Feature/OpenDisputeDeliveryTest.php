<?php
namespace Tests\Feature;
use App\Models\Agreement;
use App\Models\Booking;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\Dispute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class OpenDisputeDeliveryTest extends TestCase {
 use RefreshDatabase;
 public function test_video_cannot_be_submitted_while_dispute_is_escalated():void {
  Storage::fake('local');
  $creator=User::factory()->create();
  $customer=User::factory()->create();
  $profile=CreatorProfile::create(['user_id'=>$creator->id,'display_name'=>'Creator','slug'=>'escalation-creator','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$profile->id,'title'=>'Video','price_minor'=>1000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $booking=Booking::create(['customer_id'=>$customer->id,'creator_profile_id'=>$profile->id,'creator_package_id'=>$package->id,'brief'=>'Create a product demonstration video','price_minor'=>1000,'fee_minor'=>150,'currency'=>'EGP','status'=>'accepted']);
  $terms=['version'=>1,'price_minor'=>1000];
  Agreement::create(['booking_id'=>$booking->id,'terms'=>$terms,'terms_sha256'=>hash('sha256',json_encode($terms,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),'customer_accepted_at'=>now(),'creator_accepted_at'=>now()]);
  Dispute::create(['booking_id'=>$booking->id,'raised_by'=>$customer->id,'reason'=>'This agreement requires formal review','status'=>'escalated']);
  $this->actingAs($creator)->post(route('deliveries.store',$booking),['video'=>UploadedFile::fake()->create('video.mp4',20,'video/mp4')])->assertStatus(409);
  $this->assertDatabaseCount('deliveries',0);
  $this->assertSame([],Storage::disk('local')->allFiles('private-deliveries'));
 }
}
