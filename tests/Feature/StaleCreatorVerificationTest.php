<?php
namespace Tests\Feature;
use App\Models\CreatorProfile;
use App\Models\CreatorVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class StaleCreatorVerificationTest extends TestCase {
 use RefreshDatabase;
 public function test_pending_check_is_revoked_after_approved_profile_changes():void {
  $owner=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Old Name','slug'=>'old-name','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  $check=CreatorVerification::create(['creator_profile_id'=>$creator->id,'type'=>'identity','status'=>'pending','reference_hash'=>hash('sha256','evidence')]);
  $this->actingAs($owner)->post(route('creator.store'),[
   'display_name'=>'New Name','category'=>'UGC','country_code'=>'EG','bio'=>'Changed','status'=>'approved'
  ])->assertRedirect();
  $this->assertSame('revoked',$check->fresh()->status);
 }
 public function test_admin_cannot_verify_pending_check_for_unapproved_creator():void {
  $admin=User::factory()->create(['is_admin'=>true]);
  $owner=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Unapproved','slug'=>'unapproved','category'=>'UGC','country_code'=>'EG','status'=>'pending']);
  $check=CreatorVerification::create(['creator_profile_id'=>$creator->id,'type'=>'identity','status'=>'pending','reference_hash'=>hash('sha256','evidence')]);
  $this->actingAs($admin)->post(route('admin.verifications.review',$check),[
   'decision'=>'verified','reason'=>'Admin reviewed credentials'
  ])->assertStatus(409);
  $this->assertSame('pending',$check->fresh()->status);
 }
}
