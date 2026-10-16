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
}
