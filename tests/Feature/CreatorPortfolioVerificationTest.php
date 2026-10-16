<?php
namespace Tests\Feature;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\CreatorVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CreatorPortfolioVerificationTest extends TestCase {
 use RefreshDatabase;
 public function test_only_current_reviewed_checks_are_displayed():void {
  $creator=CreatorProfile::create([
   'user_id'=>User::factory()->create()->id,'display_name'=>'Reviewed Artist',
   'slug'=>'reviewed-artist','category'=>'UGC','country_code'=>'EG','status'=>'approved'
  ]);
  CreatorPackage::create([
   'creator_profile_id'=>$creator->id,'title'=>'UGC Video','price_minor'=>10000,
   'currency'=>'EGP','is_active'=>true,'moderation_status'=>'approved'
  ]);
  foreach([
   ['identity','verified',now()->subDay(),now()->addMonth()],
   ['social','pending',null,null],
   ['advertising_license','verified',now()->subMonth(),now()->subDay()]
  ] as [$type,$status,$reviewed,$expires]){
   CreatorVerification::create([
    'creator_profile_id'=>$creator->id,'type'=>$type,'status'=>$status,
    'reference_hash'=>hash('sha256',$type),'reviewed_at'=>$reviewed,'expires_at'=>$expires
   ]);
  }
  $this->get(route('creators.show',$creator->slug))
   ->assertOk()->assertSee('Identity reviewed')
   ->assertDontSee('Social profile reviewed')
   ->assertDontSee('Advertising license reviewed')
   ->assertSee('not a guarantee');
 }
}
