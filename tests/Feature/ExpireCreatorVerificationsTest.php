<?php
namespace Tests\Feature;
use App\Models\CreatorProfile;
use App\Models\CreatorVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ExpireCreatorVerificationsTest extends TestCase {
 use RefreshDatabase;
 public function test_expired_checks_are_updated_but_current_checks_remain_verified():void {
  $creator=CreatorProfile::create(['user_id'=>User::factory()->create()->id,'display_name'=>'Test Creator',
   'slug'=>'expiration-test','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  $expired=CreatorVerification::create(['creator_profile_id'=>$creator->id,'type'=>'identity','status'=>'verified',
   'reference_hash'=>hash('sha256','identity'),'reviewed_at'=>now()->subDays(10),'expires_at'=>now()->subDay()]);
  $current=CreatorVerification::create(['creator_profile_id'=>$creator->id,'type'=>'social','status'=>'verified',
   'reference_hash'=>hash('sha256','social'),'reviewed_at'=>now()->subDays(2),'expires_at'=>now()->addDay()]);
  $this->artisan('vibsies:expire-verifications --dry-run')->assertExitCode(0);
  $this->assertSame('verified',$expired->fresh()->status);
  $this->artisan('vibsies:expire-verifications')->assertExitCode(0);
  $this->assertSame('expired',$expired->fresh()->status);
  $this->assertSame('verified',$current->fresh()->status);
 }
}
