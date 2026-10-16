<?php
namespace App\Http\Controllers;
use App\Models\CreatorProfile;
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
  return view('creators.portfolio',compact('creator','packages','quotes'));
 }
}
