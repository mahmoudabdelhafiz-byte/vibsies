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
                ->with('promotions')->latest()->get(),
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
        $property = PublisherProperty::create($data + [
            'user_id' => $request->user()->id,
            'verification_token' => Str::upper(Str::random(16)),
        ]);
        return redirect()->route('ads.publisher.index')->with('notice', 'Property submitted for manual verification. No public post is required.');
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
        $property->promotions()->create([
            'post_url' => $data['post_url'],
            'post_format' => $data['post_format'],
            'disclosure_confirmed' => true,
            'referral_code' => Str::upper(Str::random(12)),
        ]);
        return redirect()->route('ads.publisher.index')->with('notice', 'Promotion submitted for review. Rewards are not active until approved.');
    }

    public function admin(Request $request)
    {
        $this->ensureEnabled();
        abort_unless($request->user()->is_admin, 403);
        return view('publisher-ads.admin', [
            'properties' => PublisherProperty::latest()->limit(100)->get(),
            'promotions' => PublisherPromotion::with('property')->latest()->limit(100)->get(),
        ]);
    }

    public function verify(Request $request, PublisherProperty $property)
    {
        $this->ensureEnabled();
        abort_unless($request->user()->is_admin, 403);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['verified', 'rejected'])],
            'note' => 'nullable|string|max:2000',
        ]);
        abort_if(empty($property->ownership_evidence_url) && empty($property->ownership_evidence_note), 409, 'Ownership evidence is required before approval.');
        $property->forceFill([
            'verification_status' => $data['decision'],
            'verification_note' => $data['note'] ?? null,
            'verified_at' => $data['decision'] === 'verified' ? now() : null,
        ])->save();
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
        $promotion->forceFill([
            'status' => $data['decision'],
            'review_note' => $data['note'] ?? null,
            'reviewed_at' => now(),
        ])->save();
        // Rewards are deliberately not credited here: financial ledger integration is pending.
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
