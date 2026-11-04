<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vibsies Ads Publisher Offers</title><link rel="stylesheet" href="{{ asset('css/ads-mobile.css') }}"></head>
<body>
@include('ads.partials.locale')

<a href="{{ route('ads.publisher.index') }}">{{ __('ads.publisher') }}</a><h1>{{ __('ads.offers') }}</h1>
<p>{{ __('ads.preliminary_notice') }}</p>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
@forelse($offers as $offer)
<section>
<h2>{{ $offer->campaign->title }}</h2>
<p>{{ $offer->package->property->display_name }} · {{ $offer->package->title }}</p>
<p>{{ __('ads.ad_brief') }}: {{ $offer->campaign->brief }}</p>
<p>{{ __('ads.offered_price') }}: {{ number_format($offer->agreed_price_minor / 100,2) }} {{ $offer->currency }} · {{ __('ads.status') }}: {{ $offer->status }}</p>
@if($offer->expires_at && $offer->status === 'offered')<p>{{ __('ads.offer_valid_until') }} {{ $offer->expires_at->format('Y-m-d H:i') }} UTC {{ __('ads.offer_expiry_warning') }}</p>@endif
@if($offer->status === 'offered' || $offer->status === 'accepted')
<form method="post" action="{{ route('ads.offer.cancel',$offer) }}">@csrf
<label>{{ __('ads.cancellation_reason') }}<textarea required minlength="8" maxlength="1000" name="reason"></textarea></label>
<button type="submit">{{ __('ads.cancel_offer') }}</button></form>
@endif
@if($offer->status === 'offered')
<form method="post" action="{{ route('ads.publisher.offer.decide',$offer) }}">@csrf
<button name="decision" value="accepted">{{ __('ads.accept_offer') }}</button>
<button name="decision" value="declined">{{ __('ads.decline_offer') }}</button></form>
@elseif($offer->status === 'accepted')
<form method="post" action="{{ route('ads.publisher.offer.publish',$offer) }}">@csrf
<label>{{ __('ads.sponsored_post_url') }}<input type="url" name="published_url" required placeholder="https://www.facebook.com/..."></label>
<label>{{ __('ads.publication_notes') }}<textarea name="publisher_note" maxlength="2000"></textarea></label>
<label><input type="checkbox" name="disclosure_confirmed" value="1" required> {{ __('ads.publication_disclosure') }}</label>
<button type="submit">{{ __('ads.submit_publication') }}</button></form>
@elseif($offer->published_url)
<a href="{{ $offer->published_url }}" rel="noopener noreferrer" target="_blank">{{ __('ads.published_content') }}</a>
@endif
@if($offer->review_note)<p>{{ __('ads.review_note_short') }}: {{ $offer->review_note }}</p>@endif
@if(in_array($offer->status,['submitted','confirmed','resolved_confirmed'],true) && !$offer->performanceReport)
<form method="post" action="{{ route('ads.offer.performance',$offer) }}">@csrf
<h3>{{ __('ads.report_campaign_performance') }}</h3>
<p>{{ __('ads.self_reported_warning') }}</p>
<label>{{ __('ads.measurement_date') }}<input type="date" name="measured_on" max="{{ now()->format('Y-m-d') }}" required></label>
@foreach(['views','reach','clicks','interactions'] as $metric)
<label>{{ __('ads.'.$metric) }}<input type="number" name="{{ $metric }}" min="0"></label>
@endforeach
<label>{{ __('ads.public_evidence_url') }}<input type="url" name="evidence_url"></label>
<button type="submit">{{ __('ads.submit_performance') }}</button>
</form>
@endif
@if($offer->performanceReport)<p>{{ __('ads.report_pending_verification') }}</p>@endif
</section>
@empty<p>{{ __('ads.no_offers_received') }}</p>@endforelse
{{ $offers->links() }}
</body></html>