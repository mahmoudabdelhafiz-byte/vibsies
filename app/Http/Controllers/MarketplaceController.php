<?php
namespace App\Http\Controllers;
use App\Models\CreatorPackage;
use App\Services\Payments\BookingQuote;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class MarketplaceController extends Controller {
 public function index(Request $request){
  $query=CreatorPackage::query()->where('is_active',true)->where('moderation_status','approved')->whereHas('creator',fn($q)=>$q->where('status','approved'))->with('creator');
  $filters=$request->validate(['q'=>'nullable|string|max:100','category'=>'nullable|string|max:120']);
  $operator=\Illuminate\Support\Facades\DB::connection()->getDriverName()==='pgsql'?'ilike':'like';
  if(!empty($filters['category']))$query->whereHas('creator',fn($q)=>$q->where('category',$filters['category']));
  if(!empty($filters['q'])){
   $term=$filters['q'];
   $query->where(fn($q)=>$q->where('title',$operator,"%{$term}%")->orWhereHas('creator',fn($c)=>$c->where('display_name',$operator,"%{$term}%")));
  }
  $packages=$query->latest()->paginate(12)->withQueryString();
  $quotes=[];
  $calculator=app(BookingQuote::class);
  foreach($packages as $package)$quotes[$package->id]=$calculator->calculate($package->price_minor,$package->currency);
  $categories=\App\Models\CreatorProfile::query()->where('status','approved')->whereHas('packages',fn($q)=>$q->where('is_active',true)->where('moderation_status','approved'))->distinct()->orderBy('category')->pluck('category');
  return view('marketplace.index',compact('packages','quotes','categories'));
 }
}
