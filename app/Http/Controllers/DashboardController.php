<?php
namespace App\Http\Controllers;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller {
 public function index(Request $request) {
  $user = $request->user();
  $customerBookings = Booking::with('creator')->where('customer_id', $user->id)->latest()->paginate(10);
  $creator = $user->creatorProfile;
  $incomingBookings = $creator
   ? Booking::with('customer')->where('creator_profile_id', $creator->id)->latest()->paginate(10, ['*'], 'incoming_page')
   : collect();
  $notifications=$user->notifications()->latest()->limit(15)->get();
  return view('dashboard.index', compact('customerBookings','creator','incomingBookings','notifications'));
 }
}
