<?php
namespace Tests\Feature;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CreatorPackageResubmissionTest extends TestCase {
 use RefreshDatabase;
 private function rejectedPackage(User $owner):CreatorPackage {
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Creator','slug'=>'resubmit-test','category'=>'UGC','status'=>'approved']);
  return CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Old title','price_minor'=>1000,'currency'=>'EGP','is_active'=>false,'moderation_status'=>'rejected']);
 }
 public function test_owner_can_update_and_resubmit_rejected_package():void {
  $owner=User::factory()->create();$package=$this->rejectedPackage($owner);
  $this->actingAs($owner)->post(route('creator.packages.resubmit',$package),[
   'title'=>'Updated video','description'=>'Better brief','price'=>'250.00','turnaround_days'=>5,'revision_limit'=>2
  ])->assertRedirect(route('creator.setup'));
  $this->assertDatabaseHas('creator_packages',['id'=>$package->id,'moderation_status'=>'pending','price_minor'=>25000,'is_active'=>false]);
 }
 public function test_other_user_cannot_resubmit():void {
  $owner=User::factory()->create();$package=$this->rejectedPackage($owner);
  $this->actingAs(User::factory()->create())->post(route('creator.packages.resubmit',$package),[
   'title'=>'Hijack','price'=>99,'turnaround_days'=>5,'revision_limit'=>1
  ])->assertForbidden();
 }
 public function test_approved_package_cannot_be_resubmitted():void {
  $owner=User::factory()->create();$package=$this->rejectedPackage($owner);
  $package->update(['moderation_status'=>'approved','is_active'=>true]);
  $this->actingAs($owner)->post(route('creator.packages.resubmit',$package),[
   'title'=>'Updated','price'=>99,'turnaround_days'=>5,'revision_limit'=>1
  ])->assertStatus(409);
 }
}
