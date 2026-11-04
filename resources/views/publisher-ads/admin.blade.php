<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Vibsies Ads Admin</title><link rel="stylesheet" href="{{ asset('css/ads-mobile.css') }}"></head><body>
@include('ads.partials.locale')

<a href="{{ route('dashboard') }}">{{ __('ads.dashboard') }}</a><h1>{{ __('ads.admin') }}</h1>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
<h2>{{ __('ads.performance_evidence_review') }}</h2>
<p>{{ __('ads.performance_evidence_disclaimer') }}</p>
@forelse($performanceQueue as $report)
<article><strong>{{ $report->property->display_name }}</strong> · {{ $report->offer->campaign->title }}
<p>{{ __('ads.measurement_date') }}: {{ $report->measured_on->format('Y-m-d') }} · {{ __('ads.views') }}: {{ $report->views ?? __('ads.not_reported') }} · {{ __('ads.reach') }}: {{ $report->reach ?? __('ads.not_reported') }}</p>
@if($report->evidence_url)<a href="{{ $report->evidence_url }}" target="_blank" rel="noopener noreferrer nofollow">{{ __('ads.inspect_evidence') }}</a>@endif
<form method="post" action="{{ route('ads.admin.performance.moderate',$report) }}">@csrf
<label>{{ __('ads.evidence_source') }}<select name="source_type"><option value="reviewed_export">{{ __('ads.reviewed_export') }}</option><option value="verified_platform">{{ __('ads.verified_platform') }}</option></select></label>
<label>{{ __('ads.review_reason') }}<textarea name="note" minlength="12" maxlength="2000" required></textarea></label>
<button name="decision" value="verified">{{ __('ads.verify_evidence') }}</button><button name="decision" value="rejected">{{ __('ads.reject') }}</button>
</form></article>
@empty<p>{{ __('ads.no_pending_reports') }}</p>@endforelse
<h2>{{ __('ads.rating_appeals') }}</h2>
@forelse($ratingAppeals as $appeal)
<article>
<strong>{{ $appeal->review->property->display_name }}</strong> — {{ $appeal->review->offer->campaign->title }}
<p>{{ __('ads.appeal_reason') }}: {{ $appeal->reason }}</p>
<p>{{ __('ads.rating_overall') }}: {{ $appeal->review->overall_rating }}/5</p>
<form method="post" action="{{ route('ads.admin.review.appeal.resolve',$appeal) }}">@csrf
<label>{{ __('ads.review_reason') }}<textarea name="note" minlength="12" maxlength="2000" required></textarea></label>
<button name="decision" value="upheld">{{ __('ads.uphold_appeal') }}</button>
<button name="decision" value="dismissed">{{ __('ads.dismiss_appeal') }}</button>
</form></article>
@empty<p>{{ __('ads.no_pending_appeals') }}</p>@endforelse
<h2>{{ __('ads.pending_ratings') }}</h2>
@forelse($ratingReviews as $rating)
<article><strong>{{ $rating->property->display_name }}</strong>
<p>{{ $rating->offer->campaign->title }} · {{ __('ads.rating_overall') }} {{ $rating->overall_rating }}/5</p>
<p>{{ $rating->comment }}</p>
<form method="post" action="{{ route('ads.admin.review.moderate',$rating) }}">@csrf
<label>{{ __('ads.review_reason') }}<textarea name="note" minlength="12" maxlength="2000" required></textarea></label>
<button name="decision" value="approved">{{ __('ads.approve') }}</button>
<button name="decision" value="rejected">{{ __('ads.reject') }}</button>
</form></article>
@empty<p>{{ __('ads.no_pending_ratings') }}</p>@endforelse
<h2>{{ __('ads.referral_review') }}</h2>
<p>{{ __('ads.referral_notice') }}</p>
@foreach($referrals as $referral)
<article>
<strong>{{ $referral->promotion->property->display_name }}</strong> · {{ __('ads.referral') }} {{ $referral->id }}
<p>{{ __('ads.registered_at') }} {{ $referral->registered_at?->format('Y-m-d') }} · {{ $referral->review_status }}</p>
<form method="post" action="{{ route('ads.admin.referral.review',$referral) }}">@csrf
<label>{{ __('ads.review_reason') }}<textarea name="note" minlength="12" maxlength="2000" required></textarea></label>
<button name="decision" value="reviewed">{{ __('ads.manual_reviewed') }}</button>
<button name="decision" value="flagged">{{ __('ads.flag_referral') }}</button>
</form></article>
@endforeach
<h2>{{ __('ads.pending_campaigns') }}</h2>
@foreach($campaigns as $campaign)
<article>
<strong>{{ $campaign->title }}</strong> — {{ $campaign->objective }}
<p>{{ $campaign->brief }}</p>
<p>{{ __('ads.target') }}: {{ $campaign->target_country }} · {{ $campaign->target_city }} · {{ $campaign->target_category }}. {{ __('ads.budget') }}: {{ number_format($campaign->budget_minor/100,2) }} {{ $campaign->currency }}</p>
<form method="post" action="{{ route('ads.admin.campaign.review',$campaign) }}">@csrf
<button name="decision" value="approved">{{ __('ads.approve_campaign') }}</button>
<button name="decision" value="rejected">{{ __('ads.reject_campaign') }}</button></form>
</article>
@endforeach
<h2>{{ __('ads.disputed_publications') }}</h2>
@forelse($disputedOffers as $offer)
<article>
<strong>{{ $offer->campaign->title }}</strong> · {{ $offer->package->property->display_name }}
@if($offer->published_url)<p><a href="{{ $offer->published_url }}" target="_blank" rel="noopener noreferrer">Inspect publication</a></p>@endif
<p>{{ __('ads.advertiser_note') }}: {{ $offer->review_note }}</p>
<form method="post" action="{{ route('ads.admin.offer.resolve',$offer) }}">@csrf
<label>{{ __('ads.admin_review_explanation') }}<textarea name="note" aria-label="{{ __('ads.admin_review_explanation') }}" minlength="10" maxlength="2000" required></textarea></label>
<button name="decision" value="resolved_confirmed">{{ __('ads.confirm_delivery') }}</button>
<button name="decision" value="resolved_rejected">{{ __('ads.reject_delivery') }}</button>
</form></article>
@empty<p>{{ __('ads.no_disputes') }}</p>@endforelse
<h2>{{ __('ads.expired_offers') }}</h2>
@foreach($expiredCandidates as $offer)
<article><strong>{{ $offer->campaign->title }}</strong> — {{ $offer->package->title }}
<form method="post" action="{{ route('ads.admin.offer.expire',$offer) }}">@csrf
<button type="submit">{{ __('ads.mark_expired') }}</button></form></article>
@endforeach
<h2>{{ __('ads.review_properties') }}</h2>
@foreach($properties as $property)
<article><strong>{{ $property->display_name }}</strong> — {{ $property->platform }} / {{ $property->property_type }} / {{ $property->verification_status }}
<a href="{{ $property->profile_url }}" target="_blank" rel="noopener noreferrer">{{ __('ads.review_public_url') }}</a>
<p>{{ __('ads.challenge_code') }}: {{ $property->verification_token }} · {{ __('ads.challenge_expires') }} {{ $property->verification_expires_at?->format("Y-m-d H:i") ?? "Not issued" }} UTC · {{ __('ads.evidence_submitted') }}: {{ $property->challenge_submitted_at?->format("Y-m-d H:i") ?? "No" }}. {{ __('ads.ownership_evidence') }}: {{ $property->ownership_evidence_note ?: "Not provided" }} @if($property->ownership_evidence_url)<a target="_blank" rel="noopener noreferrer" href="{{ $property->ownership_evidence_url }}">{{ __('ads.inspect_evidence') }}</a>@endif</p><p>{{ __('ads.self_reported_audience') }}: {{ $property->reported_followers ?? 'Unknown' }} · {{ $property->category }} · {{ $property->city }}</p>
<form method="post" action="{{ route('ads.admin.verify', $property) }}">@csrf
<textarea name="note" aria-label="{{ __('ads.review_reason') }}" required minlength="12" placeholder="Document code review or rejection" maxlength="2000"></textarea><label><input type="checkbox" name="challenge_checked" value="1"> {{ __('ads.checked_challenge') }}</label>
<button name="decision" value="verified">{{ __('ads.verify_owner') }}</button><button name="decision" value="rejected">{{ __('ads.reject') }}</button></form></article>
@endforeach
<h2>{{ __('ads.review_promotions') }}</h2>
@foreach($promotions as $promotion)
<article><strong>{{ $promotion->property->display_name }}</strong> — {{ $promotion->status }} · {{ $promotion->post_format }}
<a href="{{ $promotion->post_url }}" target="_blank" rel="noopener noreferrer">{{ __('ads.inspect_post') }}</a>
<form method="post" action="{{ route('ads.admin.promotion', $promotion) }}">@csrf
<textarea name="note" aria-label="{{ __('ads.admin_promotional_notes') }}" placeholder="Disclosure and post review notes" maxlength="2000"></textarea>
<button name="decision" value="approved">{{ __('ads.approve') }}</button><button name="decision" value="rejected">{{ __('ads.reject') }}</button></form>
</article>@endforeach
<h2>{{ __('ads.review_packages') }}</h2>
@foreach($packages as $package)
<article>
<strong>{{ $package->property->display_name }}</strong> — {{ $package->title }} / {{ $package->placement_type }} / {{ $package->moderation_status }}
<p>{{ number_format($package->price_minor / 100,2) }} {{ $package->currency }} · {{ $package->delivery_days }} days to deliver</p>
<form method="post" action="{{ route('ads.admin.packages.review', $package) }}">@csrf
<button name="decision" value="approved">{{ __('ads.approve') }}</button><button name="decision" value="rejected">{{ __('ads.reject') }}</button></form>
</article>@endforeach
<h2>{{ __('ads.review_metrics') }}</h2>
@foreach($metrics as $snapshot)
<article>
<strong>{{ $snapshot->property->display_name }}</strong> — {{ $snapshot->observed_on->format('Y-m-d') }} / {{ $snapshot->verification_status }}
<p>{{ __('ads.category_short') }}: {{ $snapshot->content_category ?? 'General' }} · {{ __('ads.views') }}: {{ $snapshot->views ?? 'Unknown' }} · {{ __('ads.reach') }}: {{ $snapshot->reach ?? 'Unknown' }}. Source: {{ $snapshot->source_type }}</p>
@if($snapshot->evidence_url)<a href="{{ $snapshot->evidence_url }}" target="_blank" rel="noopener noreferrer">{{ __('ads.evidence_link') }}</a>@endif
<form method="post" action="{{ route('ads.admin.metrics.review', $snapshot) }}">@csrf
<button name="decision" value="verified">Verify evidence</button><button name="decision" value="rejected">{{ __('ads.reject') }}</button></form>
</article>@endforeach
<p>{{ __('ads.no_admin_rewards') }}</p>
</body></html>