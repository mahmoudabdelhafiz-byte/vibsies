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
<p>Advertiser brief: {{ $offer->campaign->brief }}</p>
<p>Offered package price: {{ number_format($offer->agreed_price_minor / 100,2) }} {{ $offer->currency }} · Status: {{ $offer->status }}</p>
@if($offer->expires_at && $offer->status === 'offered')<p>Offer valid until {{ $offer->expires_at->format('Y-m-d H:i') }} UTC (expired offers cannot be accepted).</p>@endif
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
<label>Public sponsored post URL<input type="url" name="published_url" required placeholder="https://www.facebook.com/..."></label>
<label>{{ __('ads.publication_notes') }}<textarea name="publisher_note" maxlength="2000"></textarea></label>
<label><input type="checkbox" name="disclosure_confirmed" value="1" required> I disclosed the sponsored relationship as required by the platform and applicable law.</label>
<button type="submit">{{ __('ads.submit_publication') }}</button></form>
@elseif($offer->published_url)
<a href="{{ $offer->published_url }}" rel="noopener noreferrer" target="_blank">{{ __('ads.published_content') }}</a>
@endif
@if($offer->review_note)<p>Review note: {{ $offer->review_note }}</p>@endif
</section>
@empty<p>{{ __('ads.no_offers_received') }}</p>@endforelse
{{ $offers->links() }}
</body></html>