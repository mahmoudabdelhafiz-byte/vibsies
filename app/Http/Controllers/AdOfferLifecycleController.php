<?php

namespace App\Http\Controllers;

use App\Models\AdCampaignOffer;
use App\Models\AdOfferEvent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdOfferLifecycleController extends Controller {
    private function enabled():void {abort_unless(config('vibsies_ads.enabled'),404);}

    public function cancel(Request $request,AdCampaignOffer $offer) {
        $this->enabled();
        $isAdvertiser=$offer->campaign->user_id===$request->user()->id;
        $isPublisher=$offer->publisher_user_id===$request->user()->id;
        abort_unless($isAdvertiser||$isPublisher,403);
        $data=$request->validate(['reason'=>'required|string|min:8|max:1000']);
        DB::transaction(function()use($offer,$data,$request,$isAdvertiser) {
            $locked=AdCampaignOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            $allowed=$isAdvertiser?['offered']:['offered','accepted'];
            abort_unless(in_array($locked->status,$allowed,true),409,'Offer can no longer be cancelled directly. Raise a dispute for an active publication.');
            $previous=$locked->status;
            $locked->forceFill(['status'=>'cancelled','cancelled_at'=>now(),'cancellation_reason'=>$data['reason']])->save();
            AdOfferEvent::create([
                'ad_campaign_offer_id'=>$locked->id,'actor_id'=>$request->user()->id,
                'event_type'=>'cancelled','from_status'=>$previous,'to_status'=>'cancelled','note'=>$data['reason'],
            ]);
        });
        return back()->with('notice','Preliminary offer cancelled. No funds have been moved.');
    }

    public function expire(Request $request,AdCampaignOffer $offer) {
        $this->enabled();
        abort_unless($request->user()->is_admin,403);
        DB::transaction(function()use($offer,$request) {
            $locked=AdCampaignOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status==='offered' && $locked->expires_at && $locked->expires_at->isPast(),409,'Offer is not overdue.');
            $locked->forceFill(['status'=>'expired'])->save();
            AdOfferEvent::create([
                'ad_campaign_offer_id'=>$locked->id,'actor_id'=>$request->user()->id,
                'event_type'=>'expired','from_status'=>'offered','to_status'=>'expired',
            ]);
        });
        return back()->with('notice','Expired offer closed.');
    }

    public function resolve(Request $request,AdCampaignOffer $offer) {
        $this->enabled();
        abort_unless($request->user()->is_admin,403);
        $data=$request->validate([
            'decision'=>['required',Rule::in(['resolved_confirmed','resolved_rejected'])],
            'note'=>'required|string|min:10|max:2000',
        ]);
        DB::transaction(function()use($offer,$request,$data) {
            $locked=AdCampaignOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status==='disputed',409,'Only disputed publications may be resolved.');
            $locked->forceFill([
                'status'=>$data['decision'],'review_note'=>$data['note'],'reviewed_at'=>now(),'resolved_at'=>now(),
            ])->save();
            AdOfferEvent::create([
                'ad_campaign_offer_id'=>$locked->id,'actor_id'=>$request->user()->id,
                'event_type'=>'dispute_resolved','from_status'=>'disputed',
                'to_status'=>$data['decision'],'note'=>$data['note'],
            ]);
        });
        return back()->with('notice','Dispute decision saved. Financial settlement remains disabled.');
    }
}