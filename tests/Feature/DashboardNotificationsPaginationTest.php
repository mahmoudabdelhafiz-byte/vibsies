<?php
namespace Tests\Feature;
use App\Models\User;
use App\Notifications\BookingUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class DashboardNotificationsPaginationTest extends TestCase {
 use RefreshDatabase;
 public function test_notifications_have_separate_pagination_and_unread_count():void {
  $user=User::factory()->create();
  for($i=1;$i<=17;$i++)$user->notify(new BookingUpdate('test-booking-'.$i,'requested'));
  $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('17')->assertSee('notifications_page=2');
  $this->actingAs($user)->get(route('dashboard',['notifications_page'=>2]))->assertOk();
  $this->assertSame(17,$user->unreadNotifications()->count());
 }
}
