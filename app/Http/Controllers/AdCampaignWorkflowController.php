<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Models\AdCampaignOffer;
use App\Models\PublisherAdPackage;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdCampaignWorkflowController extends Controller
{
    private function enabled(): void { abort_unless(config('vibsies_ads.enabled'),404); }
    private function advertiser(Request $request, AdCampaign $campaign): void {
        $this->enabled();
        abort_unless($campaign->user_id === $request->user()->id,403);
    }
    private function publisher(Request $request, AdCampaignOffer $offer): void {
        $this->enabled();
        abort_unless($offer->publisher_user_id === $request->user()->id,403);
    }
    private function admin(Request $request): void {
        $this->enabled();
        abort_unless($request->user()->is_admin,403);
    }

    public function submitCampaign(Request $request, AdCampaign $campaign) {
        $this->advertiser($request,$campaign);
        abort_unless($campaign->status === 'draft',409,'Only drafts can be submitted.');
        $campaign->forceFill(['status'=>'pending_review'])->save();
        return back()->with('notice','Campaign submitted for moderation. No payment has been charged.');
    }

    public function reviewCampaign(Request $request, AdCampaign $campaign) {
        $this->admin($request);
        $data=$request->validate(['decision'=>['required',Rule::in(['approved','rejected'])]]);
        abort_unless($campaign->status === 'pending_review',409,'Campaign is not pending review.');
        $campaign->forceFill(['status'=>$data['decision']])->save();
        return back()->with('notice','Campaign moderation saved.');
    }

    public function offer(Request $request, AdCampaign $campaign) {
        $this->advertiser($request,$campaign);
        $data=$request->validate(['package_id'=>'required|uuid']);
        $package=PublisherAdPackage::with('property')->findOrFail($data['package_id']);
        DB::transaction(function() use ($campaign,$package) {
            $locked=AdCampaign::whereKey($campaign->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'approved',409,'Campaign approval is required.');
            $property=$package->property;
            abort_unless($property->verification_status === 'verified'
                && $package->is_active && $package->moderation_status === 'approved',409,'Publisher package is unavailable.');
            abort_unless($property->country_code === $locked->target_country && $package->currency === $locked->currency,409,'Region or currency mismatch.');
            abort_unless(!$locked->placement_type || $package->placement_type === $locked->placement_type,409,'Placement format mismatch.');
            abort_unless($property->user_id !== $locked->user_id,409,'You cannot offer a campaign to yourself in this pilot.');
            abort_unless(!$locked->offers()->where('publisher_ad_package_id',$package->id)->exists(),409,'An offer for this package already exists.');
            $spent=(int)$locked->offers()->whereNotIn('status',['declined','cancelled','expired'])->sum('agreed_price_minor');
            abort_unless($spent + $package->price_minor <= $locked->budget_minor,409,'Offers exceed campaign budget.');
            $locked->offers()->create([
                'publisher_ad_package_id'=>$package->id,
                'publisher_user_id'=>$property->user_id,
                'agreed_price_minor'=>$package->price_minor,
                'currency'=>$package->currency,
                'expires_at'=>now()->addDays(7),
            ]);
        });
        return back()->with('notice','Offer sent for publisher consideration. No payment has been collected or reserved.');
    }

    public function publisherOffers(Request $request) {
        $this->enabled();
        return view('publisher-ads.offers',[
            'offers'=>AdCampaignOffer::where('publisher_user_id',$request->user()->id)
                ->with(['campaign','package.property'])->latest()->paginate(20),
        ]);
    }

    public function decide(Request $request, AdCampaignOffer $offer) {
        $this->publisher($request,$offer);
        $data=$request->validate(['decision'=>['required',Rule::in(['accepted','declined'])]]);
        DB::transaction(function() use($offer,$data) {
            $locked=AdCampaignOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'offered',409,'This offer is no longer pending.');
            abort_if($locked->expires_at && $locked->expires_at->isPast(),409,'Offer has expired.');
            $locked->forceFill([
                'status'=>$data['decision'],
                'accepted_at'=>$data['decision']==='accepted'?now():null,
            ])->save();
        });
        return back()->with('notice','Offer response saved. Acceptance is not payment confirmation.');
    }

    public function submitPublication(Request $request, AdCampaignOffer $offer) {
        $this->publisher($request,$offer);
        $data=$request->validate([
            'published_url'=>'required|url|max:2048',
            'publisher_note'=>'nullable|string|max:2000',
            'disclosure_confirmed'=>'accepted',
        ]);
        $property=$offer->package->property;
        $scheme=strtolower(parse_url($data['published_url'],PHP_URL_SCHEME)??'');
        $host=strtolower(parse_url($data['published_url'],PHP_URL_HOST)??'');
        $allowed=$property->platform==='facebook'
            ? ['facebook.com','www.facebook.com','m.facebook.com','fb.com','www.fb.com']
            : ['instagram.com','www.instagram.com'];
        if($scheme!=='https'||!in_array($host,$allowed,true))
            throw ValidationException::withMessages(['published_url'=>'Supply a public post URL on the correct platform.']);
        DB::transaction(function() use($offer,$data) {
            $locked=AdCampaignOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status==='accepted',409,'Only accepted offers can submit publication evidence.');
            $locked->forceFill([
                'status'=>'submitted',
                'published_url'=>$data['published_url'],
                'publisher_note'=>$data['publisher_note']??null,
                'submitted_at'=>now(),
            ])->save();
        });
        return back()->with('notice','Publication evidence submitted for advertiser review.');
    }

    public function reviewPublication(Request $request, AdCampaignOffer $offer) {
        $this->enabled();
        abort_unless($offer->campaign->user_id === $request->user()->id,403);
        $data=$request->validate([
            'decision'=>['required',Rule::in(['confirmed','disputed'])],
            'review_note'=>'nullable|string|max:2000',
        ]);
        DB::transaction(function() use($offer,$data) {
            $locked=AdCampaignOffer::whereKey($offer->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status==='submitted',409,'No publication is pending review.');
            $locked->forceFill([
                'status'=>$data['decision'],
                'review_note'=>$data['review_note']??null,
                'reviewed_at'=>now(),
            ])->save();
        });
        return back()->with('notice','Publication review saved. No payment has been initiated.');
    }
}
