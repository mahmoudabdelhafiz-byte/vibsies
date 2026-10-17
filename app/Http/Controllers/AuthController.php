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
  $data=$request->validate(['name'=>'required|string|max:160','email'=>'required|email|max:254|unique:users,email','password'=>['required','confirmed',Password::min(10)->mixedCase()->numbers()]]);
  $user=User::create(['name'=>$data['name'],'email'=>strtolower($data['email']),'password'=>$data['password']]);
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
