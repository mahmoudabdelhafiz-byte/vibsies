<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Vibsies Ads Admin</title></head><body style="max-width:900px;margin:2rem auto;font:16px system-ui">
<a href="{{ route('dashboard') }}">Dashboard</a><h1>Vibsies Ads — Review Queue</h1>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
<h2>Publishing properties</h2>
@foreach($properties as $property)
<article style="border-bottom:1px solid #ddd;padding:1rem 0"><strong>{{ $property->display_name }}</strong> — {{ $property->platform }} / {{ $property->property_type }} / {{ $property->verification_status }}
<a href="{{ $property->profile_url }}" target="_blank" rel="noopener noreferrer">Review public URL</a>
<p>Ownership evidence: {{ $property->ownership_evidence_note ?: "Not provided" }} @if($property->ownership_evidence_url)<a target="_blank" rel="noopener noreferrer" href="{{ $property->ownership_evidence_url }}">Inspect evidence</a>@endif</p><p>Self-reported audience: {{ $property->reported_followers ?? 'Unknown' }} · {{ $property->category }} · {{ $property->city }}</p>
<form method="post" action="{{ route('ads.admin.verify', $property) }}">@csrf
<textarea name="note" placeholder="Ownership review evidence / rejection reason" maxlength="2000"></textarea>
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
<p>No rewards or payments are automatically issued from this review.</p>
</body></html>