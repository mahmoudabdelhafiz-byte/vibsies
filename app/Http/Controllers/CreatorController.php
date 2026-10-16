<?php
namespace App\Http\Controllers;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
class CreatorController extends Controller {
 public function setup(Request $request){$creator=$request->user()->creatorProfile;return view('creators.setup',['creator'=>$creator,'rejectedPackages'=>$creator?CreatorPackage::where('creator_profile_id',$creator->id)->where('moderation_status','rejected')->latest()->get():collect()]);}
 public function store(Request $request){
  $d=$request->validate(['display_name'=>'required|string|max:160','category'=>'required|string|max:120','bio'=>'nullable|string|max:2000','country_code'=>'required|in:EG,SA']);
  $profile=CreatorProfile::firstOrCreate(['user_id'=>$request->user()->id],['display_name'=>$d['display_name'],'slug'=>Str::slug($d['display_name']).'-'.Str::lower(Str::random(8)),'category'=>$d['category'],'country_code'=>$d['country_code'],'bio'=>$d['bio']??null,'status'=>'pending']);
  \Illuminate\Support\Facades\DB::transaction(function()use($profile,$d){
   $locked=CreatorProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
   $changed=$locked->status==='approved' && (
    $locked->display_name!==$d['display_name'] ||
    $locked->category!==$d['category'] ||
    $locked->country_code!==$d['country_code'] ||
    ($locked->bio??'')!==($d['bio']??'')
   );
   $locked->fill($d);
   if($changed){
    $locked->status='pending';
    CreatorPackage::where('creator_profile_id',$locked->id)->update(['is_active'=>false,'moderation_status'=>'pending']);
   }
   $locked->save();
  });
  return redirect()->route('dashboard')->with('notice','Creator profile saved and pending review.');
 }
 public function package(Request $request){
  $d=$request->validate(['title'=>'required|string|max:160','description'=>'nullable|string|max:2000','price'=>'required|numeric|min:1|max:1000000','turnaround_days'=>'required|integer|min:1|max:90','revision_limit'=>'required|integer|min:0|max:10']);
  $creator=$request->user()->creatorProfile;abort_unless($creator,403);
  CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>$d['title'],'description'=>$d['description']??null,'price_minor'=>(int)round((float)$d['price']*100),'currency'=>'EGP','turnaround_days'=>$d['turnaround_days'],'revision_limit'=>$d['revision_limit'],'is_active'=>false,'moderation_status'=>'pending']);
  return redirect()->route('dashboard')->with('notice','Package created; awaiting moderation.');
 }
 public function resubmitPackage(Request $request,CreatorPackage $package){
  $creator=$request->user()->creatorProfile;
  abort_unless($creator && $package->creator_profile_id===$creator->id,403);
  $data=$request->validate([
   'title'=>'required|string|max:160','description'=>'nullable|string|max:2000',
   'price'=>'required|numeric|min:1|max:1000000',
   'turnaround_days'=>'required|integer|min:1|max:90',
   'revision_limit'=>'required|integer|min:0|max:10'
  ]);
  \Illuminate\Support\Facades\DB::transaction(function()use($package,$creator,$data){
   $locked=CreatorPackage::whereKey($package->id)->lockForUpdate()->firstOrFail();
   abort_unless($locked->creator_profile_id===$creator->id,403);
   abort_unless($locked->moderation_status==='rejected',409,'Only rejected packages can be resubmitted.');
   $locked->update([
    'title'=>$data['title'],'description'=>$data['description']??null,
    'price_minor'=>(int)round((float)$data['price']*100),
    'turnaround_days'=>$data['turnaround_days'],'revision_limit'=>$data['revision_limit'],
    'moderation_status'=>'pending','is_active'=>false
   ]);
  });
  return redirect()->route('creator.setup')->with('notice','Package resubmitted for moderation.');
 }

}
