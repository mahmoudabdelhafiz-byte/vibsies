<?php
namespace Tests\Feature;
use App\Models\CreatorProfile;
use App\Models\CreatorVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class VerificationSubmissionSafetyTest extends TestCase {
 use RefreshDatabase;
 public function test_only_one_pending_request_per_type_can_be_submitted():void {
  $owner=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Artist',
    'slug'=>'submission-artist','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  $payload=['type'=>'identity','reference'=>'example-ref'];
  $this->actingAs($owner)->post(route('verification.submit'),$payload)->assertRedirect();
  $this->actingAs($owner)->post(route('verification.submit'),$payload)->assertStatus(409);
  $this->assertSame(1,CreatorVerification::where('creator_profile_id',$creator->id)
    ->where('type','identity')->where('status','pending')->count());
 }
 public function test_different_verification_types_can_be_submitted():void {
  $owner=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Artist',
    'slug'=>'submission-artist-two','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  foreach(['identity','social'] as $type)
   $this->actingAs($owner)->post(route('verification.submit'),['type'=>$type,'reference'=>'example-ref'])->assertRedirect();
  $this->assertSame(2,CreatorVerification::where('creator_profile_id',$creator->id)->count());
 }
}
