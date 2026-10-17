<?php
namespace Tests\Feature;
use App\Models\User;
use App\Notifications\BookingUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class NotificationReadTest extends TestCase {
 use RefreshDatabase;
 public function test_user_can_mark_only_own_notification_read():void {
  $owner=User::factory()->create();$other=User::factory()->create();
  $owner->notify(new BookingUpdate('booking-owned','accepted'));
  $other->notify(new BookingUpdate('booking-other','requested'));
  $owned=$owner->notifications()->firstOrFail();$foreign=$other->notifications()->firstOrFail();
  $this->actingAs($owner)->post(route('notifications.read',$foreign->id))->assertNotFound();
  $this->assertNull($foreign->fresh()->read_at);
  $this->actingAs($owner)->post(route('notifications.read',$owned->id))->assertRedirect(route('dashboard'));
  $this->assertNotNull($owned->fresh()->read_at);
 }
 public function test_mark_all_changes_only_authenticated_users_notifications():void {
  $user=User::factory()->create();$other=User::factory()->create();
  $user->notify(new BookingUpdate('booking-a','accepted'));
  $other->notify(new BookingUpdate('booking-b','accepted'));
  $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect(route('dashboard'));
  $this->assertSame(0,$user->unreadNotifications()->count());
  $this->assertSame(1,$other->unreadNotifications()->count());
 }
}
