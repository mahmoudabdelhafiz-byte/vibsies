<?php
namespace Tests\Feature;
use App\Models\CreatorProfile;
use App\Models\CreatorVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class VerificationWorkflowTest extends TestCase {
 use RefreshDatabase;
 public function test_only_creator_can_submit():void {
  $this->actingAs(User::factory()->create())->post('/verification/submit',['type'=>'identity','reference'=>'123456'])->assertForbidden();
 }
 public function test_creator_submits_pending_request_without_storing_raw_identifier():void {
  $user=User::factory()->create();
  CreatorProfile::create(['user_id'=>$user->id,'display_name'=>'Test creator','slug'=>'test-creator','category'=>'UGC','status'=>'pending']);
  $this->actingAs($user)->post('/verification/submit',['type'=>'identity','reference'=>'secret-identifier'])->assertRedirect('/creator');
  $v=CreatorVerification::firstOrFail();
  $this->assertSame('pending',$v->status);
  $this->assertSame(hash('sha256','secret-identifier'),$v->reference_hash);
 }
 public function test_non_admin_cannot_approve():void {
  $owner=User::factory()->create();$creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Creator','slug'=>'creator-verification-test','category'=>'UGC','status'=>'pending']);
  $v=CreatorVerification::create(['creator_profile_id'=>$creator->id,'type'=>'identity','status'=>'pending','reference_hash'=>hash('sha256','abcd')]);
  $this->actingAs($owner)->post('/admin/verifications/'.$v->id.'/review',['decision'=>'verified','reason'=>'Verified evidence'])->assertForbidden();
 }
}
