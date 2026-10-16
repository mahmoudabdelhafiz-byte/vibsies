<?php
namespace App\Http\Controllers;
use App\Models\CreatorPackage;
use App\Models\CreatorProfile;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class AdminController extends Controller {
 private function guard(Request $r):void {abort_unless($r->user()?->is_admin===true,403);}
 public function index(Request $r){
  $this->guard($r);
  return view('admin.index',[
   'creators'=>CreatorProfile::where('status','pending')->limit(30)->get(),
   'packages'=>CreatorPackage::where('is_active',false)->limit(30)->get(),
   'disputes'=>Dispute::where('status','open')->limit(30)->get()
  ]);
 }
 public function creator(Request $r,CreatorProfile $creator){
  $this->guard($r);$d=$r->validate(['decision'=>'required|in:approved,rejected','reason'=>'nullable|string|max:1000']);
  DB::transaction(function()use($r,$creator,$d){
   $creator=CreatorProfile::whereKey($creator->id)->lockForUpdate()->firstOrFail();
   abort_unless($creator->status==='pending',409);
   $creator->update(['status'=>$d['decision']]);
   DB::table('moderation_actions')->insert(['id'=>Str::uuid(),'actor_id'=>$r->user()->id,'target_type'=>'creator','target_id'=>$creator->id,'action'=>$d['decision'],'reason'=>$d['reason']??null,'created_at'=>now(),'updated_at'=>now()]);
  });
  return redirect()->route('admin.index');
 }
 public function package(Request $r,CreatorPackage $package){
  $this->guard($r);$d=$r->validate(['decision'=>'required|in:approved,rejected','reason'=>'nullable|string|max:1000']);
  DB::transaction(function()use($r,$package,$d){
   $p=CreatorPackage::whereKey($package->id)->lockForUpdate()->firstOrFail();
   abort_unless($p->is_active===false,409);
   abort_unless($d['decision']!=='approved' || $p->creator->status==='approved',409);
   $p->update(['is_active'=>$d['decision']==='approved']);
   DB::table('moderation_actions')->insert(['id'=>Str::uuid(),'actor_id'=>$r->user()->id,'target_type'=>'package','target_id'=>$p->id,'action'=>$d['decision'],'reason'=>$d['reason']??null,'created_at'=>now(),'updated_at'=>now()]);
  });
  return redirect()->route('admin.index');
 }
 public function dispute(Request $r,Dispute $dispute){
  $this->guard($r);$d=$r->validate(['decision'=>'required|in:resolved,escalated','reason'=>'required|string|min:10|max:2000']);
  DB::transaction(function()use($r,$dispute,$d){
   $dispute=Dispute::whereKey($dispute->id)->lockForUpdate()->firstOrFail();
   abort_unless($dispute->status==='open',409);
   $dispute->update(['status'=>$d['decision']]);
   DB::table('moderation_actions')->insert(['id'=>Str::uuid(),'actor_id'=>$r->user()->id,'target_type'=>'dispute','target_id'=>$dispute->id,'action'=>$d['decision'],'reason'=>$d['reason'],'created_at'=>now(),'updated_at'=>now()]);
  });
  return redirect()->route('admin.index');
 }
}