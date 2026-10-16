<?php
namespace Tests\Feature;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class AdminSecurityTest extends TestCase {
 use RefreshDatabase;
 public function test_guest_is_redirected():void {$this->get('/admin')->assertRedirect('/login');}
 public function test_regular_user_is_forbidden():void {
  $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
 }
 public function test_only_admin_can_approve_creator():void {
  $creatorUser=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$creatorUser->id,'display_name'=>'Demo','slug'=>'demo','category'=>'UGC','status'=>'pending']);
  $this->actingAs(User::factory()->create())->post('/admin/creators/'.$creator->id.'/decision',['decision'=>'approved'])->assertForbidden();
  $admin=User::factory()->create();$admin->forceFill(['is_admin'=>true])->save();
  $this->actingAs($admin)->post('/admin/creators/'.$creator->id.'/decision',['decision'=>'approved'])->assertRedirect('/admin');
  $this->assertDatabaseHas('creator_profiles',['id'=>$creator->id,'status'=>'approved']);
  $this->assertDatabaseCount('moderation_actions',1);
 }
}
