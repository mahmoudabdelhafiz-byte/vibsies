<?php
namespace App\Http\Controllers;
use App\Models\CreatorPortfolioSample;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class CreatorPortfolioSampleController extends Controller {
 public function store(Request $r){
  $creator=$r->user()->creatorProfile;
  abort_unless($creator,403);
  $d=$r->validate(['title'=>'required|string|max:160','description'=>'nullable|string|max:1000','video_url'=>'required|url:https|max:500']);
  $url=parse_url($d['video_url']);
  $host=strtolower($url['host']??'');
  abort_unless(in_array($host,['www.youtube.com','youtube.com','youtu.be','www.vimeo.com','vimeo.com'],true)
   && ($url['scheme']??'')==='https' && !isset($url['user']) && !isset($url['pass']),422,'Only HTTPS YouTube or Vimeo links are allowed.');
  CreatorPortfolioSample::create(['creator_profile_id'=>$creator->id,'title'=>$d['title'],
   'description'=>$d['description']??null,'video_url'=>$d['video_url'],'moderation_status'=>'pending']);
  return redirect()->route('creator.setup')->with('notice','Portfolio sample submitted for moderation.');
 }
 private function owner(Request $r,CreatorPortfolioSample $sample):void {
  abort_unless($r->user()->creatorProfile?->id===$sample->creator_profile_id,403);
 }
 public function update(Request $r,CreatorPortfolioSample $sample){
  $this->owner($r,$sample);
  $d=$r->validate(['title'=>'required|string|max:160','description'=>'nullable|string|max:1000','video_url'=>'required|url:https|max:500']);
  $url=parse_url($d['video_url']);
  abort_unless(($url['scheme']??'')==='https'
   && in_array(strtolower($url['host']??''),['www.youtube.com','youtube.com','youtu.be','www.vimeo.com','vimeo.com'],true)
   && !isset($url['user']) && !isset($url['pass']),422,'Only HTTPS YouTube or Vimeo links are allowed.');
  \Illuminate\Support\Facades\DB::transaction(function()use($sample,$d,$r){
   $locked=CreatorPortfolioSample::whereKey($sample->id)->lockForUpdate()->firstOrFail();
   $this->owner($r,$locked);
   $locked->update(['title'=>$d['title'],'description'=>$d['description']??null,
    'video_url'=>$d['video_url'],'moderation_status'=>'pending']);
  });
  return redirect()->route('creator.setup')->with('notice','Sample changes submitted for review.');
 }
 public function destroy(Request $r,CreatorPortfolioSample $sample){
  $this->owner($r,$sample);
  $sample->delete();
  return redirect()->route('creator.setup')->with('notice','Portfolio sample removed.');
 }
}
