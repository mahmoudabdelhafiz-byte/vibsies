<?php
namespace App\Http\Controllers;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class BusinessController extends Controller {
 public function index(Request $request){return view('business.index',['businesses'=>Organization::where('owner_id',$request->user()->id)->latest()->get()]);}
 public function store(Request $request){
  $data=$request->validate(['name'=>'required|string|max:160','country_code'=>'required|in:EG,SA']);
  $organization=Organization::create($data+['owner_id'=>$request->user()->id]);
  $organization->members()->attach($request->user()->id,['role'=>'owner']);
  return redirect()->route('business.index')->with('notice','Business added.');
 }
}
