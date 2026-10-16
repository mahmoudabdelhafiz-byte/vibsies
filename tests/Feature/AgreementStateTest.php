<?php
namespace Tests\Feature;
use App\Models\Agreement;
use App\Models\Booking;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AgreementStateTest extends TestCase {
 use RefreshDatabase;
 public function test_consent_is_forbidden_after_dispute():void {
  $customer=User::factory()->create();
  $creatorUser=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$creatorUser->id,'display_name'=>'Demo','slug'=>'agreement-test-creator','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'UGC video','price_minor'=>1000,'currency'=>'EGP','is_active'=>true]);
  $booking=Booking::create(['customer_id'=>$customer->id,'creator_profile_id'=>$creator->id,'creator_package_id'=>$package->id,'brief'=>'Please create a short marketing video','price_minor'=>1000,'fee_minor'=>150,'currency'=>'EGP','status'=>'disputed']);
  $terms=['version'=>1,'brief'=>$booking->brief];
  $agreement=Agreement::create(['booking_id'=>$booking->id,'terms'=>$terms,'terms_sha256'=>hash('sha256',json_encode($terms,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))]);
  $this->actingAs($customer)->post(route('bookings.accept-agreement',$booking))->assertStatus(409);
  $this->assertNull($agreement->fresh()->customer_accepted_at);
 }
}
