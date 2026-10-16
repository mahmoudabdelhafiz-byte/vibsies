<?php
namespace Tests\Feature;
use App\Models\CreatorProfile;
use App\Models\CreatorVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class VerificationSupersessionTest extends TestCase {
 use RefreshDatabase;
 public function test_approving_new_check_supersedes_prior_check_of_same_type_only():void {
  $admin=User::factory()->create(['is_admin'=>true]);
  $creator=CreatorProfile::create(['user_id'=>User::factory()->create()->id,
   'display_name'=>'Creator','slug'=>'creator-supersession','category'=>'UGC',
   'country_code'=>'EG','status'=>'approved']);
  $make=function(string $type,string $status)use($creator){
   return CreatorVerification::create(['creator_profile_id'=>$creator->id,'type'=>$type,
    'status'=>$status,'reference_hash'=>hash('sha256',$type.$status),
    'reviewed_at'=>$status==='verified'?now():null]);
  };
  $prior=$make('identity','verified');$other=$make('social','verified');
  $new=$make('identity','pending');
  $this->actingAs($admin)->post(route('admin.verifications.review',$new),[
   'decision'=>'verified','reason'=>'Fresh identity evidence reviewed'
  ])->assertRedirect();
  $this->assertSame('superseded',$prior->fresh()->status);
  $this->assertSame('verified',$new->fresh()->status);
  $this->assertSame('verified',$other->fresh()->status);
 }
}
