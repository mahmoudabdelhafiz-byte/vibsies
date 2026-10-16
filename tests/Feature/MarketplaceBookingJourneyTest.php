<?php
namespace Tests\Feature;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MarketplaceBookingJourneyTest extends TestCase {
 use RefreshDatabase;
 public function test_customer_can_request_an_approved_package_from_marketplace():void {
  $creatorUser=User::factory()->create();
  $buyer=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$creatorUser->id,'display_name'=>'Video Creator','slug'=>'booking-journey-creator','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Social video','price_minor'=>10000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $this->actingAs($buyer)->get(route('home'))->assertOk()->assertSee('Request booking')->assertSee($package->id);
  $this->actingAs($buyer)->post(route('bookings.store'),['creator_package_id'=>$package->id,'brief'=>'Please create a 30 second vertical product video.'])->assertRedirect();
  $this->assertDatabaseHas('bookings',['customer_id'=>$buyer->id,'creator_package_id'=>$package->id,'status'=>'requested','fee_minor'=>1500]);
 }
 public function test_creator_cannot_book_own_package():void {
  $owner=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Owner','slug'=>'own-package','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Video','price_minor'=>10000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $this->actingAs($owner)->post(route('bookings.store'),['creator_package_id'=>$package->id,'brief'=>'A long enough request that is not permitted'])->assertStatus(422);
  $this->assertDatabaseCount('bookings',0);
 }
}
