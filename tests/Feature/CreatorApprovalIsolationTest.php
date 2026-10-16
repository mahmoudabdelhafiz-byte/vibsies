<?php
namespace Tests\Feature;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CreatorApprovalIsolationTest extends TestCase {
 use RefreshDatabase;
 public function test_creator_approval_never_automatically_activates_packages():void {
  $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
  $owner=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Review Creator','slug'=>'creator-isolation','category'=>'UGC','status'=>'pending']);
  $approved=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Previously approved','price_minor'=>1000,'currency'=>'EGP','is_active'=>false,'moderation_status'=>'approved']);
  $rejected=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Rejected','price_minor'=>2000,'currency'=>'EGP','is_active'=>false,'moderation_status'=>'rejected']);
  $this->actingAs($admin)->post(route('admin.creators.decide',$creator),['decision'=>'approved'])->assertRedirect(route('admin.index'));
  $this->assertSame('approved',$creator->fresh()->status);
  $this->assertFalse($approved->fresh()->is_active);
  $this->assertFalse($rejected->fresh()->is_active);
  $this->get('/')->assertDontSee('Rejected')->assertDontSee('Previously approved');
 }
}
