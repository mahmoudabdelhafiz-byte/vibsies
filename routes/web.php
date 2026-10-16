<?php
use App\Http\Controllers\BookingController;
use App\Http\Controllers\MarketplaceController;
use Illuminate\Support\Facades\Route;
Route::get('/',[MarketplaceController::class,'index'])->name('home');
Route::middleware('auth')->group(function(){
 Route::post('/bookings',[BookingController::class,'store'])->name('bookings.store');
 Route::get('/bookings/{booking}',[BookingController::class,'show'])->name('bookings.show');
});
