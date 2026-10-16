<?php
namespace App\Http\Controllers;
use App\Models\CreatorPackage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class MarketplaceController extends Controller {
 public function index(Request $request){
  $query=CreatorPackage::query()->where('is_active',true)->where('moderation_status','approved')->whereHas('creator',fn($q)=>$q->where('status','approved'))->with('creator');
  if($request->filled('q')){
   $term=substr($request->string('q')->toString(),0,100);
   $query->where(fn($q)=>$q->where('title','ilike',"%{$term}%")->orWhereHas('creator',fn($c)=>$c->where('display_name','ilike',"%{$term}%")));
  }
  return view('marketplace.index',['packages'=>$query->latest()->paginate(12)]);
 }
}
