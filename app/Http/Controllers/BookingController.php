<?php
namespace App\Http\Controllers;
use App\Models\Booking;
use App\Models\CreatorPackage;
use App\Notifications\BookingUpdate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
class BookingController extends Controller {
 public function store(Request $request){
  $input=$request->validate(['creator_package_id'=>['required','uuid','exists:creator_packages,id'],'brief'=>['required','string','min:20','max:5000']]);
  $booking=DB::transaction(function()use($request,$input){
   $package=CreatorPackage::query()->with('creator')->lockForUpdate()->findOrFail($input['creator_package_id']);
   abort_unless($package->is_active && $package->moderation_status==='approved' && $package->creator->status==='approved',422,'Package unavailable');
   abort_if($package->creator->user_id===$request->user()->id,422,'Cannot book your own service');
   $fee=intdiv($package->price_minor*15+50,100);
   return Booking::create(['customer_id'=>$request->user()->id,'creator_profile_id'=>$package->creator_profile_id,
    'creator_package_id'=>$package->id,'brief'=>$input['brief'],'price_minor'=>$package->price_minor,
    'fee_minor'=>$fee,'currency'=>$package->currency,'status'=>'requested']);
  });
  $booking->creator->user->notify(new BookingUpdate($booking->id,'requested'));
  return redirect()->route('bookings.show',$booking);
 }
 public function show(Request $request,Booking $booking){
  abort_unless($booking->customer_id===$request->user()->id || $booking->creator->user_id===$request->user()->id,403);
  return view('bookings.show',['booking'=>$booking]);
 }
}
