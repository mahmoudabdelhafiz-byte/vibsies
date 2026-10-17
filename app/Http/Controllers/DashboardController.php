<?php
namespace App\Http\Controllers;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller {
 public function index(Request $request) {
  $user = $request->user();
  $customerBookings = Booking::with('creator')->where('customer_id', $user->id)->latest()->paginate(10, ['*'], 'bookings_page')->withQueryString();
  $creator = $user->creatorProfile;
  $incomingBookings = $creator
   ? Booking::with('customer')->where('creator_profile_id', $creator->id)->latest()->paginate(10, ['*'], 'incoming_page')->withQueryString()
   : collect();
  $filters=$request->validate(['notification_filter'=>'nullable|in:all,unread,booking,verification']);
  $notificationFilter=$filters['notification_filter']??'all';
  $notificationQuery=$user->notifications();
  if($notificationFilter==='unread')$notificationQuery->whereNull('read_at');
  if($notificationFilter==='booking')$notificationQuery->where('type',\App\Notifications\BookingUpdate::class);
  if($notificationFilter==='verification')$notificationQuery->where('type',\App\Notifications\CreatorVerificationRenewal::class);
  $notifications=$notificationQuery->latest()->paginate(15, ['*'], 'notifications_page')->withQueryString();
  $unreadCount=$user->unreadNotifications()->count();
  return view('dashboard.index', compact('customerBookings','creator','incomingBookings','notifications','unreadCount','notificationFilter'));
 }
}
