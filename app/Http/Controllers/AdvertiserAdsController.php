<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Models\Organization;
use App\Services\Ads\PublisherMatcher;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdvertiserAdsController extends Controller {
    private function enabled(): void { abort_unless(config('vibsies_ads.enabled'),404); }

    public function index(Request $request) {
        $this->enabled();
        return view('advertiser-ads.index', [
            'campaigns'=>AdCampaign::where('user_id',$request->user()->id)->latest()->get(),
            'organizations'=>Organization::where('owner_id',$request->user()->id)->get(),
        ]);
    }

    public function store(Request $request) {
        $this->enabled();
        $d=$request->validate([
            'organization_id'=>'nullable|uuid',
            'title'=>'required|string|max:160',
            'brief'=>'required|string|min:15|max:5000',
            'objective'=>['required',Rule::in(['awareness','website_visits','leads'])],
            'target_category'=>'required|string|max:120',
            'target_country'=>['required',Rule::in(['EG','SA'])],
            'target_city'=>'nullable|string|max:120',
            'placement_type'=>['nullable',Rule::in(['feed_post','story','reel','video','group_post','pinned_post'])],
            'budget'=>'required|numeric|min:1|max:1000000|decimal:0,2',
            'currency'=>['required',Rule::in(['EGP','SAR'])],
        ]);
        if(isset($d['organization_id'])) {
            $organization=Organization::where('owner_id',$request->user()->id)->findOrFail($d['organization_id']);
            if($organization->country_code!==$d['target_country']) {
                throw ValidationException::withMessages(['target_country'=>'For this pilot the business and campaign countries must match.']);
            }
        }
        $expected=$d['target_country']==='EG'?'EGP':'SAR';
        if($d['currency']!==$expected) throw ValidationException::withMessages(['currency'=>'Currency must match campaign country.']);
        $campaign=AdCampaign::create([
            'user_id'=>$request->user()->id,
            'organization_id'=>$d['organization_id']??null,
            'title'=>$d['title'],'brief'=>$d['brief'],'objective'=>$d['objective'],
            'target_category'=>$d['target_category'],'target_country'=>$d['target_country'],
            'target_city'=>$d['target_city']??null,'placement_type'=>$d['placement_type']??null,
            'budget_minor'=>(int)round(((float)$d['budget'])*100),'currency'=>$d['currency'],
        ]);
        return redirect()->route('ads.advertiser.show',$campaign)->with('notice','Draft campaign saved. No budget has been charged or reserved.');
    }

    public function show(Request $request, AdCampaign $campaign, PublisherMatcher $matcher) {
        $this->enabled();
        abort_unless($campaign->user_id===$request->user()->id,403);
        return view('advertiser-ads.show', [
            'campaign'=>$campaign->load(['offers.package.property','offers.campaignReview.appeal','offers.performanceReport']),
            'recommendations'=>$matcher->recommend($campaign),
        ]);
    }
}