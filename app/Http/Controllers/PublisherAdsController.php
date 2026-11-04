<?php

namespace App\Http\Controllers;

use App\Models\PublisherPromotion;
use App\Models\PublisherProperty;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublisherAdsController extends Controller
{
    private function ensureEnabled(): void
    {
        abort_unless(config('vibsies_ads.enabled'), 404);
    }

    public function index(Request $request)
    {
        $this->ensureEnabled();
        return view('publisher-ads.index', [
            'properties' => PublisherProperty::where('user_id', $request->user()->id)
                ->with(['promotions','rewardEntitlement','adPackages','metricSnapshots' => fn($q) => $q->latest('observed_on')->limit(10)])->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureEnabled();
        $data = $request->validate([
            'platform' => ['required', Rule::in(['facebook', 'instagram'])],
            'property_type' => ['required', Rule::in(['page', 'group', 'professional_profile', 'creator_account'])],
            'display_name' => 'required|string|max:160',
            'profile_url' => 'required|url|max:2048',
            'category' => 'required|string|max:120',
            'country_code' => ['required', Rule::in(['EG', 'SA'])],
            'city' => 'nullable|string|max:120',
            'reported_followers' => 'nullable|integer|min:0|max:2000000000',
            'description' => 'nullable|string|max:2000',
            'ownership_evidence_url' => 'nullable|url|max:2048',
            'ownership_evidence_note' => 'nullable|string|max:2000',
        ]);
        $allowed = $data['platform'] === 'facebook'
            ? ['page', 'group', 'professional_profile']
            : ['creator_account'];
        if (!in_array($data['property_type'], $allowed, true)) {
            throw ValidationException::withMessages(['property_type' => 'This account type does not match the platform.']);
        }
        $this->ensurePlatformUrl($data['profile_url'], $data['platform']);
        if (!empty($data['ownership_evidence_url'])) {
            $this->ensurePlatformUrl($data['ownership_evidence_url'], $data['platform']);
        }
        $parsed=parse_url($data['profile_url']);
        $host=strtolower($parsed['host'] ?? '');
        $host=preg_replace('/^(www\\.|m\\.)/','',$host);
        $path=strtolower(rtrim($parsed['path'] ?? '/', '/'));
        // Prevent duplicate claims using URL variants of the same social property.
        $fingerprint=hash('sha256',$data['platform'].':'.$host.$path);
        if(PublisherProperty::where('profile_url_fingerprint',$fingerprint)->exists()) {
            throw ValidationException::withMessages(['profile_url'=>'This social account has already been registered for review.']);
        }
        $property = PublisherProperty::create($data + [
            'profile_url_fingerprint' => $fingerprint,
            'user_id' => $request->user()->id,
            'verification_token' => Str::upper(Str::random(16)),
            'verification_expires_at' => now()->addDays(7),
        ]);
        return redirect()->route('ads.publisher.index')->with('notice', 'Property submitted for manual verification. No public post is required.');
    }

    public function submitOwnershipEvidence(Request $request, PublisherProperty $property)
    {
        $this->ensureEnabled();
        abort_unless($property->user_id === $request->user()->id,403);
        $data=$request->validate([
            'ownership_evidence_url'=>'required|url|max:2048',
            'ownership_evidence_note'=>'required|string|min:12|max:2000',
        ]);
        $this->ensurePlatformUrl($data['ownership_evidence_url'],$property->platform);
        \Illuminate\Support\Facades\DB::transaction(function() use($property,$data,$request) {
            $locked=PublisherProperty::whereKey($property->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->verification_status==='pending',409,'Property is no longer pending review.');
            abort_unless($locked->verification_expires_at && $locked->verification_expires_at->isFuture(),409,'Ownership challenge expired. Contact support for a fresh challenge.');
            $locked->forceFill([
                'ownership_evidence_url'=>$data['ownership_evidence_url'],
                'ownership_evidence_note'=>$data['ownership_evidence_note'],
                'challenge_submitted_at'=>now(),
            ])->save();
            \App\Models\PublisherVerificationEvent::create([
                'publisher_property_id'=>$locked->id,
                'actor_id'=>$request->user()->id,
                'action'=>'evidence_submitted',
                'note'=>'Owner submitted platform link for manual code verification',
            ]);
        });
        return redirect()->route('ads.publisher.index')->with('notice','Evidence submitted. An administrator must independently check the code at the social URL.');
    }

    public function promotion(Request $request, PublisherProperty $property)
    {
        $this->ensureEnabled();
        abort_unless($property->user_id === $request->user()->id, 403);
        abort_unless($property->verification_status === 'verified', 409, 'Verify ownership first.');
        $data = $request->validate([
            'post_url' => 'required|url|max:2048',
            'post_format' => ['required', Rule::in(['post', 'video'])],
            'disclosure_confirmed' => 'accepted',
        ]);
        $this->ensurePlatformUrl($data['post_url'], $property->platform);
        abort_if($property->promotions()->whereIn('status', ['pending', 'approved'])->exists(), 409, 'A promotion is already pending or approved.');
        \Illuminate\Support\Facades\DB::transaction(function() use($property,$data) {
            $existing=PublisherPromotion::where('publisher_property_id',$property->id)
                ->where('status','draft')->lockForUpdate()->first();
            if($existing) {
                $existing->forceFill([
                    'post_url'=>$data['post_url'],'post_format'=>$data['post_format'],
                    'disclosure_confirmed'=>true,'status'=>'pending',
                ])->save();
            } else {
                $property->promotions()->create([
                    'post_url'=>$data['post_url'],'post_format'=>$data['post_format'],
                    'disclosure_confirmed'=>true,'referral_code'=>Str::upper(Str::random(12)),
                    'status'=>'pending',
                ]);
            }
        });
        return redirect()->route('ads.publisher.index')->with('notice', 'Promotion submitted for review. Rewards are not active until approved.');
    }

    public function admin(Request $request)
    {
        $this->ensureEnabled();
        abort_unless($request->user()->is_admin, 403);
        return view('publisher-ads.admin', [
            'properties' => PublisherProperty::latest()->limit(100)->get(),
            'referrals' => \App\Models\PublisherReferral::with(['promotion.property'])->where('review_status','pending')->latest()->limit(100)->get(),
            'campaigns' => \App\Models\AdCampaign::where('status','pending_review')->latest()->limit(100)->get(),
            'disputedOffers' => \App\Models\AdCampaignOffer::with(['campaign','package.property'])->where('status','disputed')->latest()->limit(100)->get(),
            'expiredCandidates' => \App\Models\AdCampaignOffer::with(['campaign','package.property'])->where('status','offered')->where('expires_at','<=',now())->latest()->limit(100)->get(),
            'promotions' => PublisherPromotion::with('property')->where('status','!=','draft')->latest()->limit(100)->get(),
            'packages' => \App\Models\PublisherAdPackage::with('property')->latest()->limit(100)->get(),
            'metrics' => \App\Models\PublisherMetricSnapshot::with('property')->latest()->limit(100)->get(),
        ]);
    }

    public function verify(Request $request, PublisherProperty $property)
    {
        $this->ensureEnabled();
        abort_unless($request->user()->is_admin, 403);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['verified', 'rejected'])],
            'note' => 'required|string|min:12|max:2000',
        ]);
        abort_unless($property->verification_status === 'pending',409,'Only pending properties can be reviewed.');
        if($data['decision'] === 'verified') {
            abort_unless($request->boolean('challenge_checked'),422,'Administrator must attest that the challenge code was seen on the social property.');
        }
        \Illuminate\Support\Facades\DB::transaction(function() use ($property,$data,$request) {
            $locked=PublisherProperty::whereKey($property->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->verification_status === 'pending',409,'Property already reviewed.');
            if($data['decision']==='verified') {
                abort_unless($locked->verification_expires_at && $locked->verification_expires_at->isFuture(),409,'Ownership challenge expired.');
                abort_unless($locked->challenge_submitted_at && $locked->ownership_evidence_url,409,'Owner must submit evidence linking the challenge to the social property.');
            }
            $locked->forceFill([
            'verification_status' => $data['decision'],
            'verification_note' => $data['note'] ?? null,
            'verified_at' => $data['decision'] === 'verified' ? now() : null,
            'verified_by_user_id' => $request->user()->id,
            ])->save();
            \App\Models\PublisherVerificationEvent::create([
                'publisher_property_id'=>$locked->id,'actor_id'=>$request->user()->id,
                'action'=>'reviewed','decision'=>$data['decision'],'note'=>$data['note'],
            ]);
            if($data['decision']==='verified' && !$locked->promotions()->exists()) {
                $locked->promotions()->create([
                'referral_code'=>Str::upper(Str::random(12)),
                'post_format'=>'post',
                ]);
            }
        });
        return back()->with('notice', 'Publisher review saved.');
    }

    public function reviewPromotion(Request $request, PublisherPromotion $promotion)
    {
        $this->ensureEnabled();
        abort_unless($request->user()->is_admin, 403);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'note' => 'nullable|string|max:2000',
        ]);
        abort_unless($promotion->property->verification_status === 'verified', 409, 'Publisher must be verified.');
        abort_unless($promotion->post_url && $promotion->disclosure_confirmed,409,'The promotional post and disclosure must be submitted.');
        \Illuminate\Support\Facades\DB::transaction(function () use ($promotion,$data) {
            $locked=PublisherPromotion::with('property')->whereKey($promotion->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status==='pending',409,'Only pending promotions can be reviewed.');
            $locked->forceFill([
                'status'=>$data['decision'],
                'review_note'=>$data['note'] ?? null,
                'reviewed_at'=>now(),
            ])->save();
            if($data['decision']==='approved') {
                // An entitlement is nonfinancial and is not itself a commission or payout.
                \App\Models\PublisherRewardEntitlement::firstOrCreate(
                    ['publisher_property_id'=>$locked->publisher_property_id],
                    [
                        'publisher_promotion_id'=>$locked->id,
                        'publisher_share_percent'=>config('vibsies_ads.founding_share_percent',80),
                        'placement_limit'=>config('vibsies_ads.founding_placement_limit',3),
                        'placements_used'=>0,
                        'status'=>'eligible',
                        'eligible_from'=>now(),
                        'expires_at'=>now()->addDays(90),
                    ]
                );
            }
        });
        return back()->with('notice', 'Promotion review saved; no financial rewards have been credited.');
    }

    private function ensurePlatformUrl(string $url, string $platform): void
    {
        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $domains = $platform === 'facebook'
            ? ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'fb.com', 'www.fb.com']
            : ['instagram.com', 'www.instagram.com'];
        if ($scheme !== 'https' || !in_array($host, $domains, true)) {
            throw ValidationException::withMessages(['profile_url' => 'Use a public HTTPS Facebook or Instagram URL matching the selected platform.']);
        }
    }
}
