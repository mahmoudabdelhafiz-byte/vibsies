<?php
namespace Tests\Feature;
use App\Models\Booking;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class BookingPriceTransparencyTest extends TestCase {
 use RefreshDatabase;
 public function test_customer_sees_saved_booking_total_and_no_charge_notice():void {
  $creatorUser=User::factory()->create();$buyer=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$creatorUser->id,'display_name'=>'Price Creator','slug'=>'price-creator','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Video','price_minor'=>10000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $booking=Booking::create(['customer_id'=>$buyer->id,'creator_profile_id'=>$creator->id,'creator_package_id'=>$package->id,'brief'=>'A clear thirty second brand video','price_minor'=>10000,'fee_minor'=>1500,'currency'=>'EGP','status'=>'requested']);
  $this->actingAs($buyer)->get(route('bookings.show',$booking))->assertOk()
   ->assertSee('115.00')->assertSee('No payment has been collected.');
 }
}
