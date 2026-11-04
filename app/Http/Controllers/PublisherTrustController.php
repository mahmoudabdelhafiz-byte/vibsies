<?php

namespace App\Http\Controllers;

use App\Models\PublisherProperty;
use App\Models\PublisherReferral;
use App\Models\PublisherReferralReviewEvent;
use App\Models\PublisherVerificationEvent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublisherTrustController extends Controller
{
    private function enabled(): void { abort_unless(config('vibsies_ads.enabled'),404); }

    public function renewChallenge(Request $request,PublisherProperty $property)
    {
        $this->enabled();
        abort_unless($property->user_id===$request->user()->id,403);
        DB::transaction(function() use($property,$request) {
            $locked=PublisherProperty::whereKey($property->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->verification_status==='pending',409,'Only pending properties may renew challenges.');
            abort_unless($locked->verification_expires_at && $locked->verification_expires_at->isPast(),409,'Current challenge remains valid.');
            abort_unless($locked->challenge_renewal_count < 2,409,'Challenge renewal limit reached. Contact support.');
            $locked->forceFill([
                'verification_token'=>Str::upper(Str::random(16)),
                'verification_expires_at'=>now()->addDays(7),
                'challenge_submitted_at'=>null,
                'ownership_evidence_url'=>null,
                'ownership_evidence_note'=>null,
                'challenge_renewal_count'=>$locked->challenge_renewal_count+1,
                'challenge_last_renewed_at'=>now(),
            ])->save();
            PublisherVerificationEvent::create([
                'publisher_property_id'=>$locked->id,
                'actor_id'=>$request->user()->id,
                'action'=>'challenge_renewed',
                'note'=>'Expired challenge replaced; prior evidence cleared',
            ]);
        });
        return redirect()->route('ads.publisher.index')->with('notice','A new ownership code was issued. Previous evidence was cleared.');
    }

    public function reviewReferral(Request $request,PublisherReferral $referral)
    {
        $this->enabled();
        abort_unless((bool)$request->user()->is_admin,403);
        $data=$request->validate([
            'decision'=>['required',Rule::in(['reviewed','flagged'])],
            'note'=>'required|string|min:12|max:2000',
        ]);
        DB::transaction(function() use($referral,$request,$data) {
            $locked=PublisherReferral::with('promotion.property')->whereKey($referral->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->review_status==='pending',409,'Referral already reviewed.');
            if($data['decision']==='reviewed') {
                abort_unless($locked->promotion->status==='approved'
                    && $locked->promotion->property->verification_status==='verified',409,
                    'Approved promotion and verified publisher required.');
                abort_unless($locked->referred_user_id !== $locked->promotion->property->user_id,409,
                    'Self-referrals are not eligible.');
            }
            $locked->forceFill([
                'review_status'=>$data['decision'],
                'review_note'=>$data['note'],
                'reviewed_by_user_id'=>$request->user()->id,
                'reviewed_at'=>now(),
            ])->save();
            PublisherReferralReviewEvent::create([
                'publisher_referral_id'=>$locked->id,
                'actor_id'=>$request->user()->id,
                'decision'=>$data['decision'],
                'note'=>$data['note'],
            ]);
        });
        return back()->with('notice','Referral review saved. No reward or commission was issued.');
    }
}
