<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class NotificationController extends Controller {
 public function markRead(Request $request,string $notification){
  // Scope through the authenticated user's morph relationship; never resolve globally.
  $item=$request->user()->notifications()->whereKey($notification)->firstOrFail();
  if(!$item->read_at)$item->markAsRead();
  return redirect()->route('dashboard')->with('notice','Notification marked as read.');
 }
 public function markAllRead(Request $request){
  $request->user()->unreadNotifications()->update(['read_at'=>now()]);
  return redirect()->route('dashboard')->with('notice','Notifications marked as read.');
 }
}
