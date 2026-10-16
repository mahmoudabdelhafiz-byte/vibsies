<?php
namespace App\Http\Controllers;
use App\Models\Booking;
use App\Models\Delivery;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
class FulfillmentController extends Controller {
 private function authorizeParty(Request $r,Booking $b):void {
  $b->loadMissing('creator');
  abort_unless($b->customer_id===$r->user()->id || $b->creator->user_id===$r->user()->id,403);
 }
 public function show(Request $r,Booking $booking) {
  $this->authorizeParty($r,$booking);
  return view('bookings.fulfillment',['booking'=>$booking,'deliveries'=>Delivery::where('booking_id',$booking->id)->orderByDesc('version')->get(),'disputes'=>Dispute::where('booking_id',$booking->id)->get()]);
 }
 public function deliver(Request $r,Booking $booking) {
  $this->authorizeParty($r,$booking);abort_unless($booking->creator->user_id===$r->user()->id,403);
  $d=$r->validate(['media_url'=>'required|url:https|max:2000','note'=>'nullable|string|max:2000']);
  DB::transaction(function()use($booking,$r,$d){
   $b=Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
   $a=$b->agreement;
   abort_unless($a && $a->customer_accepted_at && $a->creator_accepted_at,409);
   abort_unless(in_array($b->status,['accepted','revisions_requested'],true),409);
   $v=(int)Delivery::where('booking_id',$b->id)->max('version')+1;
   Delivery::create(['booking_id'=>$b->id,'creator_user_id'=>$r->user()->id,'version'=>$v,'media_url'=>$d['media_url'],'note'=>$d['note']??null]);
   $b->update(['status'=>'in_review']);
  });
  return redirect()->route('bookings.fulfillment',$booking);
 }
 public function review(Request $r,Booking $booking,Delivery $delivery) {
  $this->authorizeParty($r,$booking);
  abort_unless($booking->customer_id===$r->user()->id && $delivery->booking_id===$booking->id,403);
  $d=$r->validate(['decision'=>'required|in:accepted,revisions_requested']);
  DB::transaction(function()use($booking,$delivery,$d){
   $b=Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
   $v=Delivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
   abort_unless($b->status==='in_review' && $v->status==='submitted',409);
   $v->update(['status'=>$d['decision']]);
   $b->update(['status'=>$d['decision']==='accepted'?'delivery_approved':'revisions_requested']);
  });
  return redirect()->route('bookings.fulfillment',$booking);
 }
 public function dispute(Request $r,Booking $booking) {
  $this->authorizeParty($r,$booking);
  $d=$r->validate(['reason'=>'required|string|min:15|max:2000']);
  DB::transaction(function()use($booking,$r,$d){
   $b=Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
   abort_if(in_array($b->status,['declined','completed','disputed'],true),409);
   abort_if(Dispute::where('booking_id',$b->id)->where('status','open')->exists(),409);
   Dispute::create(['booking_id'=>$b->id,'raised_by'=>$r->user()->id,'reason'=>$d['reason']]);
   $b->update(['status'=>'disputed']);
  });
  return redirect()->route('bookings.fulfillment',$booking);
 }
}
