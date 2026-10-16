<?php
namespace Tests\Feature;
use App\Models\CreatorProfile;
use App\Models\CreatorVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CreatorVerificationRenewalReminderTest extends TestCase {
 use RefreshDatabase;
 public function test_renewal_notifications_are_sent_once_per_milestone():void {
  $user=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$user->id,'display_name'=>'Renewal Creator',
   'slug'=>'renewal-creator','category'=>'UGC','country_code'=>'EG','status'=>'approved']);
  CreatorVerification::create(['creator_profile_id'=>$creator->id,'type'=>'identity','status'=>'verified',
   'reference_hash'=>hash('sha256','evidence'),'reviewed_at'=>now()->subWeek(),
   'expires_at'=>now()->addDays(7)->startOfDay()]);
  $this->artisan('vibsies:remind-verification-renewals --dry-run')->assertExitCode(0);
  $this->assertDatabaseCount('notifications',0);
  $this->artisan('vibsies:remind-verification-renewals')->assertExitCode(0);
  $this->artisan('vibsies:remind-verification-renewals')->assertExitCode(0);
  $this->assertDatabaseCount('notifications',1);
 }
}
