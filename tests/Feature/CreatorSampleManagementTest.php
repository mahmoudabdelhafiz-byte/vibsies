<?php
namespace Tests\Feature;
use App\Models\CreatorPortfolioSample;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CreatorSampleManagementTest extends TestCase {
 use RefreshDatabase;
 private function sample(User $owner):CreatorPortfolioSample {
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Sample Owner','slug'=>'sample-owner','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  return CreatorPortfolioSample::create(['creator_profile_id'=>$creator->id,'title'=>'Original sample',
   'video_url'=>'https://youtu.be/abc123','moderation_status'=>'approved']);
 }
 public function test_edit_forces_approved_sample_back_to_review():void {
  $owner=User::factory()->create();$sample=$this->sample($owner);
  $this->actingAs($owner)->put(route('creator.samples.update',$sample),[
   'title'=>'Updated sample','video_url'=>'https://www.youtube.com/watch?v=xyz123'
  ])->assertRedirect();
  $this->assertDatabaseHas('creator_portfolio_samples',['id'=>$sample->id,'moderation_status'=>'pending','title'=>'Updated sample']);
 }
 public function test_another_creator_cannot_edit_or_remove_sample():void {
  $owner=User::factory()->create();$sample=$this->sample($owner);
  $stranger=User::factory()->create();
  $this->actingAs($stranger)->put(route('creator.samples.update',$sample),[
   'title'=>'Hijack','video_url'=>'https://youtu.be/hijack'
  ])->assertForbidden();
  $this->actingAs($stranger)->delete(route('creator.samples.destroy',$sample))->assertForbidden();
  $this->assertDatabaseHas('creator_portfolio_samples',['id'=>$sample->id,'title'=>'Original sample']);
 }
 public function test_owner_can_remove_sample():void {
  $owner=User::factory()->create();$sample=$this->sample($owner);
  $this->actingAs($owner)->delete(route('creator.samples.destroy',$sample))->assertRedirect();
  $this->assertDatabaseMissing('creator_portfolio_samples',['id'=>$sample->id]);
 }
}
