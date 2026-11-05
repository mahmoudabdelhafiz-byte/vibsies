<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vibsies Ads — Advertiser</title><link rel="stylesheet" href="{{ asset('css/ads-mobile.css') }}"></head><body>
@include('ads.partials.locale')

<a href="{{ route('dashboard') }}">{{ __('ads.dashboard') }}</a> · <a href="{{ route('ads.publisher.index') }}">{{ __('ads.publisher') }}</a>
<h1>{{ __('ads.advertise') }}</h1><p>{{ __('ads.draft_notice') }}</p>
@if(session('notice'))<p>{{ session('notice') }}</p>@endif
@if($errors->any())<section role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></section>@endif
<section><h2>{{ __('ads.new_campaign') }}</h2><form action="{{ route('ads.advertiser.store') }}" method="post">@csrf
<label>{{ __('ads.business') }}<select name="organization_id"><option value="">{{ __('ads.personal_advertiser') }}</option>@foreach($organizations as $org)<option value="{{ $org->id }}">{{ $org->name }}</option>@endforeach</select></label>
<label>{{ __('ads.campaign_title') }}<input name="title" required maxlength="160" value="{{ old('title') }}"></label>
<label>{{ __('ads.campaign_brief') }}<textarea name="brief" required minlength="15" maxlength="5000">{{ old('brief') }}</textarea></label>
<label>{{ __('ads.objective') }}<select name="objective"><option value="awareness">{{ __('ads.awareness') }}</option><option value="website_visits">{{ __('ads.website_visits') }}</option><option value="leads">{{ __('ads.leads') }}</option></select></label>
<label>{{ __('ads.category') }}<input name="target_category" maxlength="120" required placeholder="Local community" value="{{ old('target_category') }}"></label>
<label>{{ __('ads.country') }}<select name="target_country"><option value="EG">{{ __('ads.egypt') }}</option><option value="SA">{{ __('ads.saudi') }}</option></select></label>
<label>{{ __('ads.city') }}<input name="target_city" maxlength="120" placeholder="New Cairo" value="{{ old('target_city') }}"></label>
<label>{{ __('ads.placement') }}<select name="placement_type"><option value="">{{ __('ads.any_placement') }}</option><option value="feed_post">{{ __('ads.feed_post') }}</option><option value="story">{{ __('ads.story') }}</option><option value="reel">{{ __('ads.reel') }}</option><option value="video">{{ __('ads.video') }}</option><option value="group_post">{{ __('ads.group_post') }}</option><option value="pinned_post">{{ __('ads.pinned_group_post') }}</option></select></label>
<label>{{ __('ads.budget') }}<input name="budget" type="number" min="1" max="1000000" step="0.01" required value="{{ old('budget') }}"></label>
<label>{{ __('ads.currency') }}<select name="currency"><option value="EGP">EGP</option><option value="SAR">SAR</option></select></label>
<button type="submit">{{ __('ads.save_draft') }}</button></form></section>
<h2>{{ __('ads.my_campaigns') }}</h2>
@forelse($campaigns as $campaign)<section><a href="{{ route('ads.advertiser.show',$campaign) }}">{{ $campaign->title }}</a> · {{ $campaign->status }} · {{ number_format($campaign->budget_minor / 100,2) }} {{ $campaign->currency }}</section>@empty<p>{{ __('ads.no_campaigns') }}</p>@endforelse
</body></html>