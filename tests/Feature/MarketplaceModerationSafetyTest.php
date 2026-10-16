<?php
namespace Tests\Feature;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MarketplaceModerationSafetyTest extends TestCase {
 use RefreshDatabase;
 public function test_active_but_unapproved_package_is_hidden_and_cannot_be_booked():void {
  $creatorUser=User::factory()->create();
  $buyer=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$creatorUser->id,'display_name'=>'Unapproved Listing','slug'=>'unapproved-listing','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Should remain hidden','price_minor'=>8000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'pending']);
  $this->get('/')->assertOk()->assertDontSee('Should remain hidden');
  $this->actingAs($buyer)->post(route('bookings.store'),[
   'creator_package_id'=>$package->id,
   'brief'=>'Please record a product demonstration video.'
  ])->assertStatus(422);
  $this->assertDatabaseCount('bookings',0);
 }
 public function test_approved_active_package_is_visible():void {
  $creatorUser=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$creatorUser->id,'display_name'=>'Public Creator','slug'=>'public-creator','category'=>'UGC','status'=>'approved']);
  CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Approved listing','price_minor'=>8000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $this->get('/')->assertOk()->assertSee('Approved listing');
 }
}
