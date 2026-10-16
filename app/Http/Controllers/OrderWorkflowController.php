<?php
namespace App\Http\Controllers;
use App\Models\Agreement;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingUpdate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
class OrderWorkflowController extends Controller {
 private function participant(Request $request,Booking $booking):void{
  $booking->loadMissing('creator');
  abort_unless($booking->customer_id===$request->user()->id||$booking->creator->user_id===$request->user()->id,403);
 }
 public function decide(Request $request,Booking $booking){
  $data=$request->validate(['decision'=>'required|in:accepted,declined']);
  $this->participant($request,$booking);
  abort_unless($booking->creator->user_id===$request->user()->id,403);
  DB::transaction(function()use($booking,$data){
   $locked=Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
   abort_unless($locked->status==='requested',409,'Order already decided');
   $locked->update(['status'=>$data['decision']]);
   if($data['decision']==='accepted'){
    $terms=['version'=>1,'booking_id'=>$locked->id,'customer_id'=>$locked->customer_id,
      'creator_profile_id'=>$locked->creator_profile_id,'brief'=>$locked->brief,'price_minor'=>$locked->price_minor,
      'fee_minor'=>$locked->fee_minor,'currency'=>$locked->currency,'payment_enabled'=>false];
    $json=json_encode($terms,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    Agreement::create(['booking_id'=>$locked->id,'terms'=>$terms,'terms_sha256'=>hash('sha256',$json)]);
   }
  });
  User::findOrFail($booking->customer_id)->notify(new BookingUpdate($booking->id,$data['decision']));
  return redirect()->route('bookings.show',$booking);
 }
 public function agreement(Request $request,Booking $booking){
  $this->participant($request,$booking);
  abort_unless($booking->agreement,404);
  return view('bookings.agreement',['booking'=>$booking,'agreement'=>$booking->agreement]);
 }
 public function accept(Request $request,Booking $booking){
  $this->participant($request,$booking);
  DB::transaction(function()use($request,$booking){
   $lockedBooking=Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
   abort_unless($lockedBooking->status==='accepted',409,'Agreement cannot be accepted in this order state.');
   $agreement=Agreement::where('booking_id',$booking->id)->lockForUpdate()->firstOrFail();
   $json=json_encode($agreement->terms,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
   abort_unless(hash_equals($agreement->terms_sha256,hash('sha256',$json)),409,'Agreement integrity failure');
   $column=$lockedBooking->customer_id===$request->user()->id?'customer_accepted_at':'creator_accepted_at';
   if(!$agreement->{$column})$agreement->update([$column=>now()]);
  });
  return redirect()->route('bookings.agreement',$booking);
 }
}
