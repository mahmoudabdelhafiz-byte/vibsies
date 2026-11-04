<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vibsies Ads Publisher Offers</title></head>
<body style="max-width:900px;margin:2rem auto;padding:0 1rem;font:16px system-ui">
<a href="{{ route('ads.publisher.index') }}">Publisher dashboard</a><h1>Advertising offers</h1>
<p>Offers are preliminary and do not indicate payment collection or guaranteed publisher earnings.</p>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
@forelse($offers as $offer)
<section style="border:1px solid #ddd;border-radius:12px;padding:1rem;margin:1rem 0">
<h2>{{ $offer->campaign->title }}</h2>
<p>{{ $offer->package->property->display_name }} · {{ $offer->package->title }}</p>
<p>Advertiser brief: {{ $offer->campaign->brief }}</p>
<p>Offered package price: {{ number_format($offer->agreed_price_minor / 100,2) }} {{ $offer->currency }} · Status: {{ $offer->status }}</p>
@if($offer->expires_at && $offer->status === 'offered')<p>Offer valid until {{ $offer->expires_at->format('Y-m-d H:i') }} UTC (expired offers cannot be accepted).</p>@endif
@if($offer->status === 'offered' || $offer->status === 'accepted')
<form method="post" action="{{ route('ads.offer.cancel',$offer) }}">@csrf
<label>Cancellation reason<textarea required minlength="8" maxlength="1000" name="reason"></textarea></label>
<button type="submit">Cancel preliminary offer</button></form>
@endif
@if($offer->status === 'offered')
<form method="post" action="{{ route('ads.publisher.offer.decide',$offer) }}">@csrf
<button name="decision" value="accepted">Accept preliminary offer</button>
<button name="decision" value="declined">Decline</button></form>
@elseif($offer->status === 'accepted')
<form method="post" action="{{ route('ads.publisher.offer.publish',$offer) }}">@csrf
<label>Public sponsored post URL<input type="url" name="published_url" required style="width:100%;padding:.5rem" placeholder="https://www.facebook.com/..."></label>
<label>Publication notes<textarea name="publisher_note" maxlength="2000" style="display:block;width:100%"></textarea></label>
<label><input type="checkbox" name="disclosure_confirmed" value="1" required> I disclosed the sponsored relationship as required by the platform and applicable law.</label>
<button type="submit">Submit publication for review</button></form>
@elseif($offer->published_url)
<a href="{{ $offer->published_url }}" rel="noopener noreferrer" target="_blank">View published content</a>
@endif
@if($offer->review_note)<p>Review note: {{ $offer->review_note }}</p>@endif
</section>
@empty<p>No offers received.</p>@endforelse
{{ $offers->links() }}
</body></html>