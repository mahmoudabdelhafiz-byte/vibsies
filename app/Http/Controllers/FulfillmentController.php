<?php
namespace App\Http\Controllers;
use App\Models\Booking;
use App\Models\Delivery;
use App\Models\Dispute;
use App\Models\User;
use App\Notifications\BookingUpdate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
  $this->authorizeParty($r,$booking);
  abort_unless($booking->creator->user_id===$r->user()->id,403);
  $d=$r->validate([
   'video'=>['required','file','mimetypes:video/mp4,video/quicktime,video/webm','max:102400'],
   'note'=>['nullable','string','max:2000']
  ]);
  // Reject invalid orders before writing any file to storage.
  $current=Booking::with('agreement')->findOrFail($booking->id);
  abort_unless(in_array($current->status,['accepted','revisions_requested'],true) &&
   $current->agreement?->customer_accepted_at && $current->agreement?->creator_accepted_at,409,'Agreement must be accepted before delivery.');
  $file=$r->file('video');
  $disk='local'; // Laravel local disk is not web-public.
  $stored=$file->store('private-deliveries/'.$booking->id,$disk);
  if(!$stored)abort(500,'Upload failed');
  try {
   DB::transaction(function()use($booking,$r,$d,$file,$stored,$disk){
    $b=Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
    $a=$b->agreement;
    abort_unless($a && $a->customer_accepted_at && $a->creator_accepted_at,409,'Both parties must accept the agreement.');
    $snapshot=json_encode($a->terms,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    abort_unless(hash_equals($a->terms_sha256,hash('sha256',$snapshot)),409,'Agreement snapshot integrity failure.');
    abort_unless(in_array($b->status,['accepted','revisions_requested'],true),409,'Invalid booking state.');
    abort_unless(!Dispute::where('booking_id',$b->id)->whereIn('status',['open','escalated'])->exists(),409,'Dispute requires resolution.');
    $version=((int)Delivery::where('booking_id',$b->id)->max('version'))+1;
    Delivery::create([
     'booking_id'=>$b->id,'creator_user_id'=>$r->user()->id,'version'=>$version,
     'media_url'=>null,'storage_disk'=>$disk,'storage_path'=>$stored,
     'original_filename'=>Str::limit(basename($file->getClientOriginalName()),255,''),
     'mime_type'=>$file->getMimeType(),'file_size'=>$file->getSize(),
     'sha256'=>hash_file('sha256',$file->getRealPath()),
     'note'=>$d['note']??null,'status'=>'submitted'
    ]);
    $b->update(['status'=>'in_review']);
   });
  }catch(\Throwable $e){
   Storage::disk($disk)->delete($stored);
   throw $e;
  }
  User::findOrFail($booking->customer_id)->notify(new BookingUpdate($booking->id,'video_submitted'));
  return redirect()->route('bookings.fulfillment',$booking);
 }
 public function thumbnail(Request $r,Booking $booking,Delivery $delivery) {
  $this->authorizeParty($r,$booking);
  abort_unless($delivery->booking_id===$booking->id && $delivery->processing_status==='ready' && $delivery->thumbnail_path,404);
  $disk=Storage::disk('local');
  abort_unless($disk->exists($delivery->thumbnail_path),404);
  return response()->file($disk->path($delivery->thumbnail_path),[
   'Content-Type'=>'image/jpeg','Cache-Control'=>'private, no-store, max-age=0',
   'X-Content-Type-Options'=>'nosniff','X-Frame-Options'=>'DENY'
  ]);
 }
 public function preview(Request $r,Booking $booking,Delivery $delivery) {
  $this->authorizeParty($r,$booking);
  abort_unless($delivery->booking_id===$booking->id && $delivery->storage_disk==='local' && $delivery->storage_path,404);
  abort_unless(in_array($delivery->mime_type,['video/mp4','video/quicktime','video/webm'],true),415);
  $disk=Storage::disk('local');
  abort_unless($disk->exists($delivery->storage_path),404);
  // BinaryFileResponse supports HTTP range requests used by browser video players.
  // The path is server-side private storage; no public URL or storage symlink is created.
  return response()->file($disk->path($delivery->storage_path),[
   'Content-Type'=>$delivery->mime_type,
   'Content-Disposition'=>'inline',
   'Cache-Control'=>'private, no-store, max-age=0',
   'X-Content-Type-Options'=>'nosniff',
   'X-Frame-Options'=>'DENY',
   'Referrer-Policy'=>'no-referrer'
  ]);
 }
 public function download(Request $r,Booking $booking,Delivery $delivery) {
  $this->authorizeParty($r,$booking);
  abort_unless($delivery->booking_id===$booking->id && $delivery->storage_disk==='local' && $delivery->storage_path,404);
  $disk=Storage::disk('local');
  abort_unless($disk->exists($delivery->storage_path),404);
  return $disk->download($delivery->storage_path,'vibsies-delivery-'.$delivery->version.'.'.match($delivery->mime_type){
   'video/mp4'=>'mp4','video/quicktime'=>'mov','video/webm'=>'webm',default=>'bin'
  },['Content-Type'=>$delivery->mime_type,'X-Content-Type-Options'=>'nosniff']);
 }
 public function review(Request $r,Booking $booking,Delivery $delivery) {
  $this->authorizeParty($r,$booking);
  abort_unless($booking->customer_id===$r->user()->id && $delivery->booking_id===$booking->id,403);
  $d=$r->validate(['decision'=>'required|in:accepted,revisions_requested']);
  DB::transaction(function()use($booking,$delivery,$d){
   $b=Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
   $v=Delivery::whereKey($delivery->id)->lockForUpdate()->firstOrFail();
   abort_unless($v->booking_id===$b->id && $b->status==='in_review' && $v->status==='submitted',409);
   abort_unless($v->version===(int)Delivery::where('booking_id',$b->id)->max('version'),409,'Only the latest delivery may be reviewed.');
   $v->update(['status'=>$d['decision']]);
   $b->update(['status'=>$d['decision']==='accepted'?'delivery_approved':'revisions_requested']);
  });
  $booking->load('creator');
  $booking->creator->user->notify(new BookingUpdate($booking->id,$d['decision']==='accepted'?'video_approved':'revisions_requested'));
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
  $booking->load('creator');
  $recipientId=$booking->customer_id===$r->user()->id?$booking->creator->user_id:$booking->customer_id;
  User::findOrFail($recipientId)->notify(new BookingUpdate($booking->id,'dispute_opened'));
  return redirect()->route('bookings.fulfillment',$booking);
 }
}
