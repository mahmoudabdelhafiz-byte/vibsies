<?php

namespace App\Http\Controllers;

use App\Models\AdCampaignOffer;
use App\Models\PublisherCampaignReview;
use App\Models\PublisherReviewAppeal;
use App\Models\PublisherCampaignPerformanceReport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PublisherReputationController extends Controller
{
    private function enabled(): void {abort_unless(config('vibsies_ads.enabled'),404);}

    public function review(Request $request,AdCampaignOffer $offer)
    {
        $this->enabled();
        abort_unless($offer->campaign->user_id === $request->user()->id,403);
        $data=$request->validate([
            'overall_rating'=>'required|integer|between:1,5',
            'communication_rating'=>'required|integer|between:1,5',
            'timeliness_rating'=>'required|integer|between:1,5',
            'quality_rating'=>'required|integer|between:1,5',
            'comment'=>'nullable|string|max:2000',
        ]);
        DB::transaction(function()use($offer,$data,$request){
            $locked=AdCampaignOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status,['confirmed','resolved_confirmed'],true),409,
                'Only confirmed deliveries can receive a review.');
            abort_unless($locked->publisher_user_id !== $request->user()->id,403,'Self-reviews prohibited.');
            abort_unless(!PublisherCampaignReview::where('ad_campaign_offer_id',$locked->id)->exists(),409,
                'The advertiser has already reviewed this placement.');
            PublisherCampaignReview::create($data+[
                'ad_campaign_offer_id'=>$locked->id,
                'publisher_property_id'=>$locked->package->publisher_property_id,
                'advertiser_user_id'=>$request->user()->id,
            ]);
        });
        return back()->with('notice','Feedback submitted for moderation; it is not a public verified rating.');
    }

    public function performance(Request $request,AdCampaignOffer $offer)
    {
        $this->enabled();
        abort_unless($offer->publisher_user_id === $request->user()->id,403);
        $data=$request->validate([
            'views'=>'nullable|integer|min:0|max:2000000000',
            'reach'=>'nullable|integer|min:0|max:2000000000',
            'clicks'=>'nullable|integer|min:0|max:2000000000',
            'interactions'=>'nullable|integer|min:0|max:2000000000',
            'measured_on'=>'required|date|before_or_equal:today',
            'evidence_url'=>'nullable|url|max:2048',
        ]);
        abort_unless(collect(['views','reach','clicks','interactions'])->contains(fn($field)=>array_key_exists($field,$data) && $data[$field] !== null),422,
            'Provide at least one performance measurement.');
        DB::transaction(function()use($offer,$data){
            $locked=AdCampaignOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status,['submitted','confirmed','resolved_confirmed'],true),409);
            abort_unless(!PublisherCampaignPerformanceReport::where('ad_campaign_offer_id',$locked->id)->exists(),409,
                'Performance report already submitted. Contact moderation to amend.');
            PublisherCampaignPerformanceReport::create($data+[
                'ad_campaign_offer_id'=>$locked->id,
                'publisher_property_id'=>$locked->package->publisher_property_id,
                'evidence_source'=>'self_reported',
            ]);
        });
        return back()->with('notice','Self-reported metrics saved; no verification or public performance claims have been granted.');
    }

    public function moderate(Request $request,PublisherCampaignReview $review)
    {
        $this->enabled();
        abort_unless((bool)$request->user()->is_admin,403);
        $data=$request->validate([
            'decision'=>['required',Rule::in(['approved','rejected'])],
            'note'=>'required|string|min:12|max:2000',
        ]);
        DB::transaction(function()use($review,$request,$data){
            $locked=PublisherCampaignReview::whereKey($review->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->moderation_status==='pending',409,'Review already moderated.');
            abort_unless(in_array($locked->offer->status,['confirmed','resolved_confirmed'],true),409);
            $locked->forceFill([
                'moderation_status'=>$data['decision'],'moderation_note'=>$data['note'],
                'reviewed_at'=>now(),'moderated_by_user_id'=>$request->user()->id,
            ])->save();
        });
        return back()->with('notice','Advertiser feedback moderation saved.');
    }
    public function moderatePerformance(Request $request,PublisherCampaignPerformanceReport $report)
    {
        $this->enabled();
        abort_unless((bool)$request->user()->is_admin,403);
        $data=$request->validate([
            'decision'=>['required',Rule::in(['verified','rejected'])],
            'source_type'=>['required_if:decision,verified',Rule::in(['reviewed_export','verified_platform'])],
            'note'=>'required|string|min:12|max:2000',
        ]);
        DB::transaction(function() use($report,$data,$request) {
            $locked=PublisherCampaignPerformanceReport::with('offer')->whereKey($report->id)
                ->lockForUpdate()->firstOrFail();
            abort_unless($locked->verification_status==='unverified',409,'Report already reviewed.');
            abort_unless(in_array($locked->offer->status,['confirmed','resolved_confirmed'],true),409,
                'Performance evidence cannot be certified before accepted delivery.');
            if($data['decision']==='verified') {
                abort_unless($locked->evidence_url,409,
                    'Independent performance evidence URL is required before verification.');
            }
            $locked->forceFill([
                'verification_status'=>$data['decision'],
                'evidence_source'=>$data['decision']==='verified'?$data['source_type']:'self_reported',
                'review_note'=>$data['note'],
                'verified_at'=>$data['decision']==='verified'?now():null,
                'verified_by_user_id'=>$request->user()->id,
            ])->save();
        });
        return back()->with('notice','Performance evidence decision saved; no financial reward issued.');
    }

    public function publisherReply(Request $request,PublisherCampaignReview $review)
    {
        $this->enabled();
        abort_unless($review->property->user_id===$request->user()->id,403);
        $data=$request->validate(['response'=>'required|string|min:10|max:2000']);
        DB::transaction(function()use($review,$data){
            $locked=PublisherCampaignReview::whereKey($review->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->moderation_status==='approved',409,'Only approved feedback may receive a response.');
            abort_unless(!$locked->publisher_responded_at,409,'Already responded.');
            $locked->forceFill(['publisher_response'=>$data['response'],'publisher_responded_at'=>now()])->save();
        });
        return back()->with('notice','Publisher reply saved separately from the advertiser rating.');
    }

    public function appeal(Request $request,PublisherCampaignReview $review)
    {
        $this->enabled();
        abort_unless($review->property->user_id===$request->user()->id,403);
        $data=$request->validate(['reason'=>'required|string|min:20|max:2000']);
        DB::transaction(function()use($review,$data,$request){
            $locked=PublisherCampaignReview::whereKey($review->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->moderation_status==='approved',409,'Only approved feedback can be appealed.');
            abort_unless(!PublisherReviewAppeal::where('publisher_campaign_review_id',$locked->id)->exists(),409,'Review already appealed.');
            PublisherReviewAppeal::create([
                'publisher_campaign_review_id'=>$locked->id,
                'publisher_user_id'=>$request->user()->id,
                'reason'=>$data['reason'],
            ]);
        });
        return back()->with('notice','Rating appeal submitted for moderator review.');
    }

    public function resolveAppeal(Request $request,PublisherReviewAppeal $appeal)
    {
        $this->enabled();
        abort_unless((bool)$request->user()->is_admin,403);
        $data=$request->validate([
            'decision'=>['required',Rule::in(['upheld','dismissed'])],
            'note'=>'required|string|min:12|max:2000',
        ]);
        DB::transaction(function()use($appeal,$data,$request){
            $locked=PublisherReviewAppeal::with('review')->whereKey($appeal->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status==='pending',409,'Appeal already resolved.');
            $locked->forceFill([
                'status'=>$data['decision'],
                'resolution_note'=>$data['note'],
                'resolved_by_user_id'=>$request->user()->id,
                'resolved_at'=>now(),
            ])->save();
        });
        return back()->with('notice','Appeal decision recorded without rewriting original feedback.');
    }

}
