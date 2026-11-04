<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
class LocaleController extends Controller {
 public function switch(Request $request){
  $data=$request->validate(['locale'=>'required|in:en,ar']);
  $request->session()->put('locale',$data['locale']);
  $previous=$request->headers->get('referer');
  if(is_string($previous) && parse_url($previous,PHP_URL_HOST)===$request->getHost()
     && in_array(strtolower(parse_url($previous,PHP_URL_SCHEME)??''),['http','https'],true)) {
   return redirect()->to($previous);
  }
  return redirect()->to(url('/'));
 }
}