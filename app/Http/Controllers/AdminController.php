<?php
namespace App\Http\Controllers;
use App\Models\CreatorPackage;
use App\Services\Agreements\AgreementIntegrity;
use App\Models\CreatorPortfolioSample;
use App\Models\CreatorProfile;
use App\Models\Dispute;
use App\Models\CreatorVerification;
use App\Models\Booking;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class AdminController extends Controller {
 private function guard(Request $r):void {abort_unless($r->user()?->is_admin===true,403);}
 public function index(Request $r){
  $this->guard($r);
  $metrics=[
   'users'=>User::count(),
   'approved_creators'=>CreatorProfile::where('status','approved')->count(),
   'active_packages'=>CreatorPackage::where('is_active',true)->where('moderation_status','approved')->count(),
   'pending_creators'=>CreatorProfile::where('status','pending')->count(),
   'pending_packages'=>CreatorPackage::where('moderation_status','pending')->count(),
   'pending_samples'=>CreatorPortfolioSample::where('moderation_status','pending')->count(),
   'bookings_total'=>Booking::count(),
   'bookings_requested'=>Booking::where('status','requested')->count(),
   'bookings_in_review'=>Booking::where('status','in_review')->count(),
   'deliveries_total'=>Delivery::count(),
   'disputes_open'=>Dispute::whereIn('status',['open','escalated'])->count()
  ];
  return view('admin.index',[
   'metrics'=>$metrics,
   'creators'=>CreatorProfile::where('status','pending')->limit(30)->get(),
   'packages'=>CreatorPackage::where('moderation_status','pending')->limit(30)->get(),
   'samples'=>CreatorPortfolioSample::with('creator')->where('moderation_status','pending')->limit(30)->get(),
   'disputes'=>Dispute::whereIn('status',['open','escalated'])->limit(30)->get(),
   'verifications'=>CreatorVerification::with('creator')->where('status','pending')->limit(30)->get()
  ]);
 }
 public function creator(Request $r,CreatorProfile $creator){
  $this->guard($r);$d=$r->validate(['decision'=>'required|in:approved,rejected','reason'=>'nullable|string|max:1000']);
  DB::transaction(function()use($r,$creator,$d){
   $creator=CreatorProfile::whereKey($creator->id)->lockForUpdate()->firstOrFail();
   abort_unless($creator->status==='pending',409);
   $creator->update(['status'=>$d['decision']]);
   // Approval of a creator does not automatically reactivate packages; each requires its own moderation.
   if($d['decision']==='rejected'){
    CreatorPackage::where('creator_profile_id',$creator->id)->where('is_active',true)->update(['is_active'=>false]);
    CreatorVerification::where('creator_profile_id',$creator->id)->whereIn('status',['verified','pending'])->update(['status'=>'revoked']);
    CreatorPortfolioSample::where('creator_profile_id',$creator->id)->where('moderation_status','approved')->update(['moderation_status'=>'pending']);
   }
   DB::table('moderation_actions')->insert(['id'=>Str::uuid(),'actor_id'=>$r->user()->id,'target_type'=>'creator','target_id'=>$creator->id,'action'=>$d['decision'],'reason'=>$d['reason']??null,'created_at'=>now(),'updated_at'=>now()]);
  });
  return redirect()->route('admin.index');
 }
 public function package(Request $r,CreatorPackage $package){
  $this->guard($r);$d=$r->validate(['decision'=>'required|in:approved,rejected','reason'=>'nullable|string|max:1000']);
  DB::transaction(function()use($r,$package,$d){
   $p=CreatorPackage::whereKey($package->id)->lockForUpdate()->firstOrFail();
   abort_unless($p->moderation_status==='pending',409);
   abort_unless($d['decision']!=='approved' || $p->creator->status==='approved',409);
   $p->update(['is_active'=>$d['decision']==='approved','moderation_status'=>$d['decision']]);
   DB::table('moderation_actions')->insert(['id'=>Str::uuid(),'actor_id'=>$r->user()->id,'target_type'=>'package','target_id'=>$p->id,'action'=>$d['decision'],'reason'=>$d['reason']??null,'created_at'=>now(),'updated_at'=>now()]);
  });
  return redirect()->route('admin.index');
 }
 public function sample(Request $r,CreatorPortfolioSample $sample){
  $this->guard($r);
  $d=$r->validate(['decision'=>'required|in:approved,rejected','reason'=>'nullable|string|max:1000']);
  DB::transaction(function()use($r,$sample,$d){
   $locked=CreatorPortfolioSample::whereKey($sample->id)->lockForUpdate()->firstOrFail();
   abort_unless($locked->moderation_status==='pending',409);
   abort_unless($d['decision']!=='approved' || $locked->creator->status==='approved',409);
   $locked->update(['moderation_status'=>$d['decision']]);
   DB::table('moderation_actions')->insert(['id'=>Str::uuid(),'actor_id'=>$r->user()->id,
    'target_type'=>'portfolio_sample','target_id'=>$locked->id,'action'=>$d['decision'],
    'reason'=>$d['reason']??null,'created_at'=>now(),'updated_at'=>now()]);
  });
  return redirect()->route('admin.index');
 }
 public function dispute(Request $r,Dispute $dispute){
  $this->guard($r);$d=$r->validate(['decision'=>'required|in:resolved,escalated','reason'=>'required|string|min:10|max:2000']);
  DB::transaction(function()use($r,$dispute,$d){
   // Lock the parent booking before its dispute, matching the customer dispute path.
   $booking=Booking::whereKey($dispute->booking_id)->lockForUpdate()->firstOrFail();
   $dispute=Dispute::whereKey($dispute->id)->lockForUpdate()->firstOrFail();
   abort_unless($dispute->booking_id===$booking->id && in_array($dispute->status,['open','escalated'],true),409);
   abort_unless($dispute->status==='open' || $d['decision']==='resolved',409,'Escalated disputes may only be resolved.');
   abort_unless($booking->status==='disputed',409,'Booking is not in a disputed state.');
   $dispute->update(['status'=>$d['decision']]);
   if($d['decision']==='resolved'){
    // Restore an actionable state based on the most recent recorded delivery.
    $last=Delivery::where('booking_id',$booking->id)->orderByDesc('version')->first();
    $resume=match($last?->status){
     'submitted'=>'in_review',
     'revisions_requested'=>'revisions_requested',
     'accepted'=>'delivery_approved',
     default=>'accepted'
    };
    // Never re-enable delivery or consent from an unverified or incomplete agreement.
    $agreement=$booking->agreement;
    abort_unless($agreement && AgreementIntegrity::verify($agreement->terms,$agreement->terms_sha256),
     409,'Agreement missing or modified; manual remediation required.');
    if(in_array($resume,['in_review','revisions_requested','delivery_approved'],true))
     abort_unless($agreement->customer_accepted_at && $agreement->creator_accepted_at,
      409,'Both parties must accept before resuming delivered work.');
    $booking->update(['status'=>$resume]);
   }
   DB::table('moderation_actions')->insert(['id'=>Str::uuid(),'actor_id'=>$r->user()->id,'target_type'=>'dispute','target_id'=>$dispute->id,'action'=>$d['decision'],'reason'=>$d['reason'],'created_at'=>now(),'updated_at'=>now()]);
  });
  return redirect()->route('admin.index');
 }
}