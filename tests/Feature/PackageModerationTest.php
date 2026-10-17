<?php
namespace Tests\Feature;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class PackageModerationTest extends TestCase {
 use RefreshDatabase;
 public function test_rejected_package_leaves_queue_and_cannot_be_reapproved_without_resubmission():void {
  $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
  $creatorUser=User::factory()->create();
  $profile=CreatorProfile::create(['user_id'=>$creatorUser->id,'display_name'=>'Demo','slug'=>'moderation-demo','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$profile->id,'title'=>'Sample','price_minor'=>5000,'currency'=>'EGP','is_active'=>false,'moderation_status'=>'pending']);
  $this->actingAs($admin)->post(route('admin.packages.decide',$package),['decision'=>'rejected','reason'=>'Incomplete'])->assertRedirect(route('admin.index'));
  $this->assertDatabaseHas('creator_packages',['id'=>$package->id,'moderation_status'=>'rejected','is_active'=>false]);
  $this->actingAs($admin)->post(route('admin.packages.decide',$package),['decision'=>'approved'])->assertStatus(409);
 }
}
