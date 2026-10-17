<?php
namespace Tests\Feature;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CreatorRejectionSafetyTest extends TestCase {
 use RefreshDatabase;
 public function test_rejecting_creator_deactivates_active_packages():void {
  $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
  $owner=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Review Creator','slug'=>'review-creator-test','category'=>'UGC','status'=>'pending']);
  $package=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Previously approved video','price_minor'=>1000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $this->actingAs($admin)->post(route('admin.creators.decide',$creator),['decision'=>'rejected','reason'=>'Review failed'])->assertRedirect(route('admin.index'));
  $this->assertSame('rejected',$creator->fresh()->status);
  $this->assertFalse($package->fresh()->is_active);
  $this->assertDatabaseHas('creator_packages',['id'=>$package->id,'moderation_status'=>'approved']);
 }
}
