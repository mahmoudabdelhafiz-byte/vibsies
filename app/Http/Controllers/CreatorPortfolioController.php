<?php
namespace App\Http\Controllers;
use App\Models\CreatorProfile;
use App\Models\CreatorVerification;
use App\Services\Payments\BookingQuote;
use Illuminate\Routing\Controller;
class CreatorPortfolioController extends Controller {
 public function show(string $slug){
  $creator=CreatorProfile::query()->where('slug',$slug)->where('status','approved')
   ->whereHas('packages',fn($q)=>$q->where('is_active',true)->where('moderation_status','approved'))
   ->firstOrFail();
  $packages=$creator->packages()->where('is_active',true)->where('moderation_status','approved')->orderBy('created_at')->get();
  $quotes=[];
  $calculator=app(BookingQuote::class);
  foreach($packages as $package)$quotes[$package->id]=$calculator->calculate($package->price_minor,$package->currency);
  $samples=\App\Models\CreatorPortfolioSample::where('creator_profile_id',$creator->id)->where('moderation_status','approved')->latest()->get();
  // Public labels reflect only administrator-verified, unexpired attestations.
  $verifiedTypes=CreatorVerification::query()->where('creator_profile_id',$creator->id)
   ->where('status','verified')->whereNotNull('reviewed_at')
   ->where(fn($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()))
   ->distinct()->pluck('type')->all();
  return view('creators.portfolio',compact('creator','packages','quotes','samples','verifiedTypes'));
 }
}
