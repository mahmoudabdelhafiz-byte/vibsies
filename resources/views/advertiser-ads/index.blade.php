<!doctype html>
<html lang="{{ str_replace('_','-',app()->getLocale()) }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vibsies Ads — Advertiser</title><style>body{max-width:850px;margin:2rem auto;padding:0 1rem;font:16px system-ui;color:#17324a}section{border:1px solid #ddd;border-radius:12px;padding:1.2rem;margin:1rem 0}input,select,textarea,button{display:block;max-width:100%;padding:.6rem;margin:.4rem 0 1rem}button{background:#087ac1;color:#fff;border:0;border-radius:8px}label{font-weight:600}</style></head><body>
<a href="{{ route('dashboard') }}">Dashboard</a> · <a href="{{ route('ads.publisher.index') }}">Publisher dashboard</a>
<h1>Advertise with Vibsies</h1><p>Create a draft campaign and discover eligible publisher placements. No money is charged when you create a draft.</p>
@if(session('notice'))<p>{{ session('notice') }}</p>@endif
@if($errors->any())<section role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></section>@endif
<section><h2>New campaign</h2><form action="{{ route('ads.advertiser.store') }}" method="post">@csrf
<label>Business (optional)<select name="organization_id"><option value="">Personal advertiser</option>@foreach($organizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach</select></label>
<label>Campaign title<input name="title" required maxlength="160" value="{{ old('title') }}"></label>
<label>Campaign brief<textarea name="brief" required minlength="15" maxlength="5000">{{ old('brief') }}</textarea></label>
<label>Objective<select name="objective"><option value="awareness">Awareness</option><option value="website_visits">Website visits</option><option value="leads">Leads</option></select></label>
<label>Desired content category<input name="target_category" maxlength="120" required placeholder="Local community" value="{{ old('target_category') }}"></label>
<label>Country<select name="target_country"><option value="EG">Egypt</option><option value="SA">Saudi Arabia</option></select></label>
<label>Target city (optional)<input name="target_city" maxlength="120" placeholder="New Cairo" value="{{ old('target_city') }}"></label>
<label>Preferred placement<select name="placement_type"><option value="">Any suitable type</option><option value="feed_post">Feed post</option><option value="story">Story</option><option value="reel">Reel</option><option value="video">Video</option><option value="group_post">Group post</option><option value="pinned_post">Pinned group post</option></select></label>
<label>Budget<input name="budget" type="number" min="1" max="1000000" step="0.01" required value="{{ old('budget') }}"></label>
<label>Currency<select name="currency"><option value="EGP">EGP</option><option value="SAR">SAR</option></select></label>
<button type="submit">Save draft and find publishers</button></form></section>
<h2>My draft campaigns</h2>
@forelse($campaigns as $campaign)<section><a href="{{ route('ads.advertiser.show',$campaign) }}">{{ $campaign->title }}</a> · {{ $campaign->status }} · {{ number_format($campaign->budget_minor / 100,2) }} {{ $campaign->currency }}</section>@empty<p>No campaigns yet.</p>@endforelse
</body></html>