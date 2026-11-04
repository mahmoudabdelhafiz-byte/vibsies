<!doctype html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vibsies Ads — Recommendations</title><style>body{max-width:900px;margin:2rem auto;padding:0 1rem;font:16px system-ui;color:#17324a}article{border:1px solid #ddd;border-radius:12px;padding:1rem;margin:1rem 0}</style></head><body>
<a href="{{ route('ads.advertiser.index') }}">All campaigns</a>
<h1>{{ $campaign->title }}</h1><p>{{ $campaign->brief }}</p><p>Budget: {{ number_format($campaign->budget_minor / 100,2) }} {{ $campaign->currency }} · {{ $campaign->target_country }} · {{ $campaign->target_city ?: 'Any city' }} · {{ $campaign->target_category }}</p>
<p><strong>Status:</strong> {{ $campaign->status }} — no bookings, payments or guaranteed impressions are available at this stage.</p>
<h2>Publisher recommendations</h2>
<p>Rankings consider category, exact city, verified recent evidence and price. They do not predict actual reach, clicks or sales. Missing evidence reduces confidence.</p>
@forelse($recommendations as $result)
<article><h3>{{ $result['package']->property->display_name }}</h3>
<p>{{ $result['package']->title }} · {{ str_replace('_',' ',$result['package']->placement_type) }} · {{ number_format($result['package']->price_minor / 100,2) }} {{ $result['package']->currency }}</p>
<p>Country: {{ $result['package']->property->country_code }} · City: {{ $result['package']->property->city ?: 'Unspecified' }} · Category: {{ $result['package']->property->category }}</p>
<p>Relevance points: {{ $result['score'] }} / 100 (not a performance estimate)</p>
<ul>@foreach($result['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
<a href="{{ $result['package']->property->profile_url }}" target="_blank" rel="noopener noreferrer">Inspect social property</a>
</article>
@empty<p>No verified and approved publisher packages currently match the country, price and placement requirements. Try a different budget or target.</p>@endforelse
</body></html>