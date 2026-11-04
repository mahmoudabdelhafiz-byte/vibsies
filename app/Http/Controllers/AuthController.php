<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
class AuthController extends Controller {
 public function registerForm(){return view('auth.register');}
 public function loginForm(){return view('auth.login');}
 public function register(Request $request){
  // Normalize before validating uniqueness, particularly on case-sensitive PostgreSQL indexes.
  if(is_string($request->input('email'))) {
   $request->merge(['email'=>mb_strtolower(trim($request->input('email')))]);
  }
  $data=$request->validate(['name'=>'required|string|max:160','email'=>'required|email|max:254|unique:users,email','password'=>['required','confirmed',Password::min(10)->mixedCase()->numbers()]]);
  $user=\Illuminate\Support\Facades\DB::transaction(function()use($request,$data) {
   $user=User::create(['name'=>$data['name'],'email'=>strtolower($data['email']),'password'=>$data['password']]);
   if(config('vibsies_ads.enabled')) {
    $ref=$request->session()->get('vibsies_referral');
    if(is_array($ref) && isset($ref['promotion_id'],$ref['captured_at'])
       && is_numeric($ref['captured_at']) && (int)$ref['captured_at'] <= now()->timestamp
       && (int)$ref['captured_at'] >= now()->subDays(7)->timestamp) {
     $promotion=\App\Models\PublisherPromotion::with('property')->find($ref['promotion_id']);
     if($promotion && in_array($promotion->status,['draft','pending','approved'],true)
        && $promotion->property->verification_status==='verified'
        && $promotion->property->user_id !== $user->id
        && mb_strtolower($promotion->property->user->email) !== $user->email) {
       \App\Models\PublisherReferral::create([
        'publisher_promotion_id'=>$promotion->id,
        'referred_user_id'=>$user->id,
        'status'=>'registered','registered_at'=>now(),
       ]);
     }
    }
   }
   return $user;
  });
  $request->session()->forget('vibsies_referral');
  Auth::login($user);$request->session()->regenerate();
  return redirect()->route('dashboard');
 }
 public function login(Request $request){
  $data=$request->validate(['email'=>'required|email','password'=>'required|string']);
  if(!Auth::attempt(['email'=>strtolower($data['email']),'password'=>$data['password']]))return back()->withErrors(['email'=>'Invalid credentials'])->onlyInput('email');
  $request->session()->regenerate();return redirect()->intended(route('dashboard'));
 }
 public function logout(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('home');}
}
