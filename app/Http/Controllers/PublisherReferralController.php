<?php

namespace App\Http\Controllers;

use App\Models\PublisherPromotion;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PublisherReferralController extends Controller {
    public function visit(Request $request,string $code) {
        abort_unless(config('vibsies_ads.enabled'),404);
        abort_unless(preg_match('/^[A-Z0-9]{12}$/D',$code)===1,404);
        $promotion=PublisherPromotion::with('property')->where('referral_code',$code)->firstOrFail();
        abort_unless(in_array($promotion->status,['draft','pending','approved'],true) && $promotion->property->verification_status==='verified',404);
        // No raw IP, device fingerprint or third-party tracking. Session expires automatically.
        $request->session()->put('vibsies_referral',[
            'promotion_id'=>$promotion->id,
            'captured_at'=>now()->timestamp,
        ]);
        return redirect()->route('register');
    }
}