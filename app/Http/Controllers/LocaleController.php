<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class LocaleController extends Controller {
 public function switch(Request $request){
  $data=$request->validate(['locale'=>'required|in:en,ar']);
  $request->session()->put('locale',$data['locale']);
  return redirect()->to(url('/'));
 }
}