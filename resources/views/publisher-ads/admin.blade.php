<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Vibsies Ads Admin</title></head><body style="max-width:900px;margin:2rem auto;font:16px system-ui">
<a href="{{ route('dashboard') }}">Dashboard</a><h1>Vibsies Ads — Review Queue</h1>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
<h2>Pending advertiser campaigns</h2>
@foreach($campaigns as $campaign)
<article style="border-bottom:1px solid #ddd;padding:1rem 0">
<strong>{{ $campaign->title }}</strong> — {{ $campaign->objective }}
<p>{{ $campaign->brief }}</p>
<p>Target: {{ $campaign->target_country }} · {{ $campaign->target_city }} · {{ $campaign->target_category }}. Budget: {{ number_format($campaign->budget_minor/100,2) }} {{ $campaign->currency }}</p>
<form method="post" action="{{ route('ads.admin.campaign.review',$campaign) }}">@csrf
<button name="decision" value="approved">Approve campaign</button>
<button name="decision" value="rejected">Reject campaign</button></form>
</article>
@endforeach
<h2>Disputed advertising publications</h2>
@forelse($disputedOffers as $offer)
<article style="border-bottom:1px solid #ddd;padding:1rem 0">
<strong>{{ $offer->campaign->title }}</strong> · {{ $offer->package->property->display_name }}
@if($offer->published_url)<p><a href="{{ $offer->published_url }}" target="_blank" rel="noopener noreferrer">Inspect publication</a></p>@endif
<p>Advertiser note: {{ $offer->review_note }}</p>
<form method="post" action="{{ route('ads.admin.offer.resolve',$offer) }}">@csrf
<label>Resolution explanation<textarea name="note" minlength="10" maxlength="2000" required></textarea></label>
<button name="decision" value="resolved_confirmed">Confirm delivery</button>
<button name="decision" value="resolved_rejected">Reject delivery</button>
</form></article>
@empty<p>No disputed publications.</p>@endforelse
<h2>Expired preliminary offers</h2>
@foreach($expiredCandidates as $offer)
<article><strong>{{ $offer->campaign->title }}</strong> — {{ $offer->package->title }}
<form method="post" action="{{ route('ads.admin.offer.expire',$offer) }}">@csrf
<button type="submit">Mark expired</button></form></article>
@endforeach
<h2>Publishing properties</h2>
@foreach($properties as $property)
<article style="border-bottom:1px solid #ddd;padding:1rem 0"><strong>{{ $property->display_name }}</strong> — {{ $property->platform }} / {{ $property->property_type }} / {{ $property->verification_status }}
<a href="{{ $property->profile_url }}" target="_blank" rel="noopener noreferrer">Review public URL</a>
<p>Challenge code: {{ $property->verification_token }} · Expires {{ $property->verification_expires_at?->format("Y-m-d H:i") ?? "Not issued" }} UTC · Evidence submitted: {{ $property->challenge_submitted_at?->format("Y-m-d H:i") ?? "No" }}. Ownership evidence: {{ $property->ownership_evidence_note ?: "Not provided" }} @if($property->ownership_evidence_url)<a target="_blank" rel="noopener noreferrer" href="{{ $property->ownership_evidence_url }}">Inspect evidence</a>@endif</p><p>Self-reported audience: {{ $property->reported_followers ?? 'Unknown' }} · {{ $property->category }} · {{ $property->city }}</p>
<form method="post" action="{{ route('ads.admin.verify', $property) }}">@csrf
<textarea name="note" required minlength="12" placeholder="Document code review or rejection" maxlength="2000"></textarea><label><input type="checkbox" name="challenge_checked" value="1"> I checked the current challenge code on the linked social property</label>
<button name="decision" value="verified">Verify ownership</button><button name="decision" value="rejected">Reject</button></form></article>
@endforeach
<h2>Optional Vibsies promotions</h2>
@foreach($promotions as $promotion)
<article style="border-bottom:1px solid #ddd;padding:1rem 0"><strong>{{ $promotion->property->display_name }}</strong> — {{ $promotion->status }} · {{ $promotion->post_format }}
<a href="{{ $promotion->post_url }}" target="_blank" rel="noopener noreferrer">Inspect sponsored post</a>
<form method="post" action="{{ route('ads.admin.promotion', $promotion) }}">@csrf
<textarea name="note" placeholder="Disclosure and post review notes" maxlength="2000"></textarea>
<button name="decision" value="approved">Approve</button><button name="decision" value="rejected">Reject</button></form>
</article>@endforeach
<h2>Placement packages</h2>
@foreach($packages as $package)
<article style="border-bottom:1px solid #ddd;padding:1rem 0">
<strong>{{ $package->property->display_name }}</strong> — {{ $package->title }} / {{ $package->placement_type }} / {{ $package->moderation_status }}
<p>{{ number_format($package->price_minor / 100,2) }} {{ $package->currency }} · {{ $package->delivery_days }} days to deliver</p>
<form method="post" action="{{ route('ads.admin.packages.review', $package) }}">@csrf
<button name="decision" value="approved">Approve</button><button name="decision" value="rejected">Reject</button></form>
</article>@endforeach
<h2>Audience metric snapshots</h2>
@foreach($metrics as $snapshot)
<article style="border-bottom:1px solid #ddd;padding:1rem 0">
<strong>{{ $snapshot->property->display_name }}</strong> — {{ $snapshot->observed_on->format('Y-m-d') }} / {{ $snapshot->verification_status }}
<p>Category: {{ $snapshot->content_category ?? 'General' }} · Views: {{ $snapshot->views ?? 'Unknown' }} · Reach: {{ $snapshot->reach ?? 'Unknown' }}. Source: {{ $snapshot->source_type }}</p>
@if($snapshot->evidence_url)<a href="{{ $snapshot->evidence_url }}" target="_blank" rel="noopener noreferrer">Evidence link</a>@endif
<form method="post" action="{{ route('ads.admin.metrics.review', $snapshot) }}">@csrf
<button name="decision" value="verified">Verify evidence</button><button name="decision" value="rejected">Reject</button></form>
</article>@endforeach
<p>No rewards or payments are automatically issued from this review.</p>
</body></html>