<?php
namespace App\Http\Controllers;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DashboardController extends Controller {
 public function index(Request $request) {
  $user = $request->user();
  $customerBookings = Booking::where('customer_id', $user->id)->latest()->paginate(10);
  $creator = $user->creatorProfile;
  $incomingBookings = $creator
   ? Booking::where('creator_profile_id', $creator->id)->latest()->limit(20)->get()
   : collect();
  return view('dashboard.index', compact('customerBookings','creator','incomingBookings'));
 }
}
