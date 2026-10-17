<?php
namespace Tests\Feature;
use App\Models\CreatorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CreatorPortfolioVisibilityTest extends TestCase {
 use RefreshDatabase;
 public function test_unknown_creator_portfolio_is_not_found():void {
  $this->get(route('creators.show','missing-creator'))->assertNotFound();
 }
 public function test_unapproved_creator_portfolio_is_not_public():void {
  $creator=CreatorProfile::create([
   'user_id'=>\App\Models\User::factory()->create()->id,
   'display_name'=>'Pending Creator','slug'=>'pending-creator',
   'category'=>'UGC','country_code'=>'EG','status'=>'pending'
  ]);
  $this->get(route('creators.show',$creator->slug))->assertNotFound();
 }
}
