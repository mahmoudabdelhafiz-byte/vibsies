<?php
namespace App\Http\Controllers;
use App\Models\CreatorPackage;
use App\Services\Payments\BookingQuote;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class MarketplaceController extends Controller {
 public function index(Request $request){
  $query=CreatorPackage::query()->where('is_active',true)->where('moderation_status','approved')->whereHas('creator',fn($q)=>$q->where('status','approved'))->with('creator');
  if($request->filled('q')){
   $term=substr($request->string('q')->toString(),0,100);
   $query->where(fn($q)=>$q->where('title','ilike',"%{$term}%")->orWhereHas('creator',fn($c)=>$c->where('display_name','ilike',"%{$term}%")));
  }
  $packages=$query->latest()->paginate(12)->withQueryString();
  $quotes=[];
  $calculator=app(BookingQuote::class);
  foreach($packages as $package)$quotes[$package->id]=$calculator->calculate($package->price_minor,$package->currency);
  return view('marketplace.index',compact('packages','quotes'));
 }
}
