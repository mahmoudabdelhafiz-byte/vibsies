<?php
namespace App\Http\Controllers;
use App\Models\CreatorVerification;
use App\Models\CreatorProfile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class VerificationController extends Controller {
 public function submit(Request $request) {
  $data=$request->validate(['type'=>'required|in:identity,social,advertising_license','reference'=>'required|string|min:4|max:200']);
  $creator=$request->user()->creatorProfile;
  abort_unless($creator,403,'Create a creator profile first.');
  DB::transaction(function()use($data,$creator){
   // Lock an existing parent row: locking the pending query cannot prevent two inserts.
   CreatorProfile::whereKey($creator->id)->lockForUpdate()->firstOrFail();
   abort_if(CreatorVerification::where('creator_profile_id',$creator->id)->where('type',$data['type'])->where('status','pending')->lockForUpdate()->exists(),409,'Request already pending.');
   CreatorVerification::create(['creator_profile_id'=>$creator->id,'type'=>$data['type'],'status'=>'pending','reference_hash'=>hash('sha256',$data['reference'])]);
  });
  return redirect()->route('creator.setup')->with('notice','Verification request submitted for review.');
 }
 public function review(Request $request,CreatorVerification $verification) {
  abort_unless($request->user()?->is_admin===true,403);
  $data=$request->validate(['decision'=>'required|in:verified,rejected','reason'=>'required|string|min:5|max:1000','expires_at'=>'nullable|date|after:today']);
  DB::transaction(function()use($request,$verification,$data){
   // Keep lock ordering consistent with creator profile edits and admin decisions.
   $creator=CreatorProfile::whereKey($verification->creator_profile_id)->lockForUpdate()->firstOrFail();
   $v=CreatorVerification::whereKey($verification->id)->lockForUpdate()->firstOrFail();
   abort_unless($v->status==='pending',409,'Already reviewed.');
   abort_unless($data['decision']!=='verified' || $creator->status==='approved',409,'Creator profile must be approved before verification.');
   if($data['decision']==='verified'){
    // Only the latest accepted attestation for a type remains current.
    CreatorVerification::where('creator_profile_id',$creator->id)->where('type',$v->type)
     ->where('status','verified')->whereKeyNot($v->id)->update(['status'=>'superseded']);
   }
   $v->update(['status'=>$data['decision'],'reviewed_by'=>$request->user()->id,'reviewed_at'=>now(),'expires_at'=>$data['decision']==='verified'?($data['expires_at']??null):null]);
   DB::table('moderation_actions')->insert(['id'=>(string)Str::uuid(),'actor_id'=>$request->user()->id,'target_type'=>'creator_verification','target_id'=>$v->id,'action'=>$data['decision'],'reason'=>$data['reason'],'created_at'=>now(),'updated_at'=>now()]);
  });
  return redirect()->route('admin.index')->with('notice','Verification decision saved.');
 }
}
