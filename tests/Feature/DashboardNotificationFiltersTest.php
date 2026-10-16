<?php
namespace Tests\Feature;
use App\Models\User;
use App\Notifications\BookingUpdate;
use App\Notifications\CreatorVerificationRenewal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DashboardNotificationFiltersTest extends TestCase {
 use RefreshDatabase;
 public function test_filters_are_scoped_to_signed_in_user():void {
  $user=User::factory()->create();$other=User::factory()->create();
  $user->notify(new BookingUpdate('owned-booking','accepted'));
  $user->notify(new CreatorVerificationRenewal('verified-id','identity','2026-11-20'));
  $other->notify(new BookingUpdate('other-booking','requested'));
  $this->actingAs($user)->get(route('dashboard',['notification_filter'=>'booking']))
   ->assertOk()->assertSee('owned-booking')->assertDontSee('other-booking')
   ->assertDontSee('Verification renewal reminder');
  $this->actingAs($user)->get(route('dashboard',['notification_filter'=>'verification']))
   ->assertOk()->assertSee('Verification renewal reminder')->assertDontSee('owned-booking');
 }
 public function test_unread_filter_excludes_read_notifications():void {
  $user=User::factory()->create();
  $user->notify(new BookingUpdate('read-booking','accepted'));
  $user->notify(new BookingUpdate('unread-booking','accepted'));
  $user->notifications()->where('data','like','%read-booking%')->first()?->markAsRead();
  $this->actingAs($user)->get(route('dashboard',['notification_filter'=>'unread']))
   ->assertOk()->assertSee('unread-booking');
 }
}
