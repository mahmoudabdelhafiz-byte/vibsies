<?php
namespace Tests\Feature;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MarketplaceTest extends TestCase {
 use RefreshDatabase;
 public function test_public_homepage_loads():void{$this->get('/')->assertOk();}
 public function test_guests_cannot_create_bookings():void{$this->post('/bookings',[])->assertRedirect('/login');}
 public function test_creator_cannot_book_their_own_package():void{
  $user=User::factory()->create();
  $creator=CreatorProfile::create(['user_id'=>$user->id,'display_name'=>'Tester','slug'=>'tester','category'=>'UGC','status'=>'approved']);
  $package=CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>'Demo video','price_minor'=>50000,'currency'=>'EGP','is_active'=>true]);
  $this->actingAs($user)->post('/bookings',['creator_package_id'=>$package->id,'brief'=>'I need an authentic demonstration video.'])->assertStatus(422);
 }
}
