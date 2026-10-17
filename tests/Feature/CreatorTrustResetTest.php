<?php
namespace Tests\Feature;
use App\Models\CreatorProfile;
use App\Models\CreatorVerification;
use App\Models\CreatorPortfolioSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CreatorTrustResetTest extends TestCase {
 use RefreshDatabase;
 public function test_editing_approved_creator_revokes_verification_and_resets_public_samples():void {
  $user=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$user->id,'display_name'=>'Prior Creator',
   'slug'=>'prior-creator','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  $verification=CreatorVerification::create(['creator_profile_id'=>$creator->id,'type'=>'identity',
   'status'=>'verified','reference_hash'=>hash('sha256','example'),'reviewed_at'=>now()]);
  $sample=CreatorPortfolioSample::create(['creator_profile_id'=>$creator->id,
   'title'=>'Reel','video_url'=>'https://youtu.be/AbCdEf123_-','moderation_status'=>'approved']);
  $this->actingAs($user)->post(route('creator.store'),[
   'display_name'=>'Changed Creator','category'=>'UGC','country_code'=>'EG','bio'=>'Updated biography'
  ])->assertRedirect();
  $this->assertSame('pending',$creator->fresh()->status);
  $this->assertSame('revoked',$verification->fresh()->status);
  $this->assertSame('pending',$sample->fresh()->moderation_status);
 }
}
