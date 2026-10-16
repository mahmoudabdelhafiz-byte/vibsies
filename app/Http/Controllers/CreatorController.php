<?php
namespace App\Http\Controllers;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
class CreatorController extends Controller {
 public function setup(Request $request){return view('creators.setup',['creator'=>$request->user()->creatorProfile]);}
 public function store(Request $request){
  $d=$request->validate(['display_name'=>'required|string|max:160','category'=>'required|string|max:120','bio'=>'nullable|string|max:2000','country_code'=>'required|in:EG,SA']);
  $profile=CreatorProfile::firstOrCreate(['user_id'=>$request->user()->id],['display_name'=>$d['display_name'],'slug'=>Str::slug($d['display_name']).'-'.Str::lower(Str::random(8)),'category'=>$d['category'],'country_code'=>$d['country_code'],'bio'=>$d['bio']??null,'status'=>'pending']);
  $profile->fill($d);$profile->status='pending';$profile->save();
  return redirect()->route('dashboard')->with('notice','Creator profile saved and pending review.');
 }
 public function package(Request $request){
  $d=$request->validate(['title'=>'required|string|max:160','description'=>'nullable|string|max:2000','price'=>'required|numeric|min:1|max:1000000','turnaround_days'=>'required|integer|min:1|max:90','revision_limit'=>'required|integer|min:0|max:10']);
  $creator=$request->user()->creatorProfile;abort_unless($creator,403);
  CreatorPackage::create(['creator_profile_id'=>$creator->id,'title'=>$d['title'],'description'=>$d['description']??null,'price_minor'=>(int)round((float)$d['price']*100),'currency'=>'EGP','turnaround_days'=>$d['turnaround_days'],'revision_limit'=>$d['revision_limit'],'is_active'=>false,'moderation_status'=>'pending']);
  return redirect()->route('dashboard')->with('notice','Package created; awaiting moderation.');
 }
}
