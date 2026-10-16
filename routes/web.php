<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CreatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\OrderWorkflowController;
use Illuminate\Support\Facades\Route;
Route::get('/',[MarketplaceController::class,'index'])->name('home');
Route::middleware('guest')->group(function(){
 Route::get('/register',[AuthController::class,'registerForm'])->name('register');
 Route::post('/register',[AuthController::class,'register'])->middleware('throttle:5,1')->name('register.submit');
 Route::get('/login',[AuthController::class,'loginForm'])->name('login');
 Route::post('/login',[AuthController::class,'login'])->middleware('throttle:10,1')->name('login.submit');
});
Route::middleware('auth')->group(function(){
 Route::post('/logout',[AuthController::class,'logout'])->name('logout');
 Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');
 Route::get('/creator',[CreatorController::class,'setup'])->name('creator.setup');
 Route::post('/creator',[CreatorController::class,'store'])->name('creator.store');
 Route::post('/creator/packages',[CreatorController::class,'package'])->name('creator.package');
 Route::get('/business',[BusinessController::class,'index'])->name('business.index');
 Route::post('/business',[BusinessController::class,'store'])->name('business.store');
 Route::post('/bookings/{booking}/decision',[OrderWorkflowController::class,'decide'])->name('bookings.decision');
 Route::get('/bookings/{booking}/agreement',[OrderWorkflowController::class,'agreement'])->name('bookings.agreement');
 Route::post('/bookings/{booking}/agreement',[OrderWorkflowController::class,'accept'])->name('bookings.accept-agreement');
 Route::post('/bookings',[BookingController::class,'store'])->name('bookings.store');
 Route::get('/bookings/{booking}',[BookingController::class,'show'])->name('bookings.show');
});
