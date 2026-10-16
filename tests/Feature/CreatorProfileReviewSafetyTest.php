<?php
namespace Tests\Feature;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CreatorProfileReviewSafetyTest extends TestCase {
 use RefreshDatabase;
 public function test_editing_approved_profile_suspends_active_packages():void {
  $user=User::factory()->create();
  $profile=CreatorProfile::create(['user_id'=>$user->id,'display_name'=>'Original','slug'=>'review-safety','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$profile->id,'title'=>'Video','price_minor'=>1000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $this->actingAs($user)->post(route('creator.store'),['display_name'=>'Updated','category'=>'UGC','country_code'=>'EG'])->assertRedirect(route('dashboard'));
  $this->assertSame('pending',$profile->fresh()->status);
  $this->assertDatabaseHas('creator_packages',['id'=>$package->id,'is_active'=>false,'moderation_status'=>'pending']);
 }
 public function test_unchanged_approved_profile_keeps_packages_active():void {
  $user=User::factory()->create();
  $profile=CreatorProfile::create(['user_id'=>$user->id,'display_name'=>'Same','slug'=>'review-unchanged','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$profile->id,'title'=>'Video','price_minor'=>1000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $this->actingAs($user)->post(route('creator.store'),['display_name'=>'Same','category'=>'UGC','country_code'=>'EG'])->assertRedirect(route('dashboard'));
  $this->assertSame('approved',$profile->fresh()->status);
  $this->assertTrue($package->fresh()->is_active);
 }
 public function test_profile_re_review_does_not_reset_rejected_package():void {
  $user=User::factory()->create();
  $profile=CreatorProfile::create(['user_id'=>$user->id,'display_name'=>'Before','slug'=>'review-rejected','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  $approved=CreatorPackage::create(['creator_profile_id'=>$profile->id,'title'=>'Approved','price_minor'=>2000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $rejected=CreatorPackage::create(['creator_profile_id'=>$profile->id,'title'=>'Rejected','price_minor'=>1000,'currency'=>'EGP','is_active'=>false,'moderation_status'=>'rejected']);
  $this->actingAs($user)->post(route('creator.store'),['display_name'=>'After','category'=>'UGC','country_code'=>'EG'])->assertRedirect(route('dashboard'));
  $this->assertDatabaseHas('creator_packages',['id'=>$approved->id,'is_active'=>false,'moderation_status'=>'pending']);
  $this->assertDatabaseHas('creator_packages',['id'=>$rejected->id,'is_active'=>false,'moderation_status'=>'rejected']);
 }
}
