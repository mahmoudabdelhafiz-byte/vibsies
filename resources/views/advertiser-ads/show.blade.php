<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vibsies Ads — Recommendations</title><link rel="stylesheet" href="{{ asset('css/ads-mobile.css') }}"></head><body>
@include('ads.partials.locale')

<a href="{{ route('ads.advertiser.index') }}">{{ __('ads.all_campaigns') }}</a>
<h1>{{ $campaign->title }}</h1><p>{{ $campaign->brief }}</p><p>Budget: {{ number_format($campaign->budget_minor / 100,2) }} {{ $campaign->currency }} · {{ $campaign->target_country }} · {{ $campaign->target_city ?: 'Any city' }} · {{ $campaign->target_category }}</p>
<p><strong>Status:</strong> {{ $campaign->status }} — no bookings, payments or guaranteed impressions are available at this stage.</p>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
@if($campaign->status === 'draft')
<form action="{{ route('ads.campaign.submit',$campaign) }}" method="post">@csrf
<button type="submit">Submit campaign for admin review</button></form>
@elseif($campaign->status === 'pending_review')
<p>{{ __('ads.waiting_review') }}</p>
@endif
<h2>{{ __('ads.sent_offers') }}</h2>
@forelse($campaign->offers as $offer)
<article><strong>{{ $offer->package->property->display_name }}</strong> · {{ $offer->status }}
· {{ number_format($offer->agreed_price_minor/100,2) }} {{ $offer->currency }}
@if($offer->status === 'offered')
<form method="post" action="{{ route('ads.offer.cancel',$offer) }}">@csrf
<label>{{ __('ads.withdrawal_reason') }}<textarea name="reason" minlength="8" maxlength="1000" required></textarea></label>
<button type="submit">{{ __('ads.withdraw_offer') }}</button></form>
@endif
@if($offer->published_url)
<p><a href="{{ $offer->published_url }}" target="_blank" rel="noopener noreferrer">{{ __('ads.review_publication') }}</a></p>
@endif
@if($offer->status === 'submitted')
<form method="post" action="{{ route('ads.offer.review',$offer) }}">@csrf
<label>{{ __('ads.review_notes') }}<textarea name="review_note" maxlength="2000"></textarea></label>
<button name="decision" value="confirmed">{{ __('ads.confirm_evidence') }}</button>
<button name="decision" value="disputed">{{ __('ads.dispute') }}</button></form>
@endif
</article>
@empty<p>{{ __('ads.no_offers_sent') }}</p>@endforelse
<h2>{{ __('ads.recommendations') }}</h2>
<p>Rankings consider category, exact city, verified recent evidence and price. They do not predict actual reach, clicks or sales. Missing evidence reduces confidence.</p>
@forelse($recommendations as $result)
<article><h3>{{ $result['package']->property->display_name }}</h3>
<p>{{ $result['package']->title }} · {{ str_replace('_',' ',$result['package']->placement_type) }} · {{ number_format($result['package']->price_minor / 100,2) }} {{ $result['package']->currency }}</p>
<p>Country: {{ $result['package']->property->country_code }} · City: {{ $result['package']->property->city ?: 'Unspecified' }} · Category: {{ $result['package']->property->category }}</p>
<p>Relevance points: {{ $result['score'] }} / 100 (not a performance estimate)</p>
<ul>@foreach($result['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
<a href="{{ $result['package']->property->profile_url }}" target="_blank" rel="noopener noreferrer">Inspect social property</a>
@if($campaign->status === 'approved')
<form action="{{ route('ads.campaign.offer',$campaign) }}" method="post">@csrf
<input type="hidden" name="package_id" value="{{ $result['package']->id }}">
<button type="submit">{{ __('ads.send_offer') }}</button></form>
@endif
</article>
@empty<p>No verified and approved publisher packages currently match the country, price and placement requirements. Try a different budget or target.</p>@endforelse
</body></html>