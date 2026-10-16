<?php
namespace Tests\Feature;
use App\Models\CreatorPortfolioSample;
use App\Models\CreatorProfile;
use App\Models\CreatorPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CreatorPortfolioSamplesTest extends TestCase {
 use RefreshDatabase;
 public function test_creator_sample_stays_pending_until_review():void {
  $owner=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$owner->id,'display_name'=>'Sample Creator','slug'=>'sample-creator','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Promo','price_minor'=>10000,'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved']);
  $this->actingAs($owner)->post(route('creator.samples.store'),['title'=>'Product reel','video_url'=>'https://www.youtube.com/watch?v=abc123'])->assertRedirect();
  $sample=CreatorPortfolioSample::firstOrFail();
  $this->assertSame('pending',$sample->moderation_status);
  $this->get(route('creators.show',$creator->slug))->assertDontSee('Product reel');
 }
 public function test_non_creator_cannot_submit_portfolio_sample():void {
  $this->actingAs(User::factory()->create())->post(route('creator.samples.store'),[
   'title'=>'Unauthorized','video_url'=>'https://youtube.com/watch?v=test'
  ])->assertForbidden();
 }
}
