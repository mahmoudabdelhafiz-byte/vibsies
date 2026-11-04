<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vibsies Ads — Recommendations</title><link rel="stylesheet" href="{{ asset('css/ads-mobile.css') }}"></head><body>
@include('ads.partials.locale')

<a href="{{ route('ads.advertiser.index') }}">{{ __('ads.all_campaigns') }}</a>
<h1>{{ $campaign->title }}</h1><p>{{ $campaign->brief }}</p><p>{{ __('ads.budget') }}: {{ number_format($campaign->budget_minor / 100,2) }} {{ $campaign->currency }} · {{ $campaign->target_country }} · {{ $campaign->target_city ?: __('ads.any_city') }} · {{ $campaign->target_category }}</p>
<p><strong>{{ __('ads.status') }}:</strong> {{ $campaign->status }} — {{ __('ads.no_finance_stage') }}</p>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
@if($errors->any())<ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
@if($campaign->status === 'draft')
<form action="{{ route('ads.campaign.submit',$campaign) }}" method="post">@csrf
<button type="submit">{{ __('ads.submit_campaign_admin') }}</button></form>
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
@if(in_array($offer->status,['confirmed','resolved_confirmed'],true))
@if($offer->campaignReview)
<p>{{ __('ads.rating_submitted') }}: {{ $offer->campaignReview->moderation_status }}</p>
@else
<form method="post" action="{{ route('ads.offer.rating',$offer) }}">@csrf
<h3>{{ __('ads.rate_publisher') }}</h3>
@foreach(['overall_rating'=>'rating_overall','communication_rating'=>'rating_communication','timeliness_rating'=>'rating_timeliness','quality_rating'=>'rating_quality'] as $field=>$label)
<label>{{ __('ads.'.$label) }}<select name="{{ $field }}" required>
<option value="">{{ __('ads.choose_rating') }}</option>
@for($i=1;$i<=5;$i++)<option value="{{ $i }}">{{ $i }} / 5</option>@endfor
</select></label>
@endforeach
<label>{{ __('ads.rating_comment') }}<textarea name="comment" maxlength="2000"></textarea></label>
<button type="submit">{{ __('ads.submit_rating') }}</button>
</form>
@endif
@endif
@if($offer->performanceReport)
<p>{{ __('ads.performance_source') }}: {{ $offer->performanceReport->verification_status }} ({{ $offer->performanceReport->evidence_source }}).
{{ __('ads.views') }}: {{ $offer->performanceReport->views ?? __('ads.not_reported') }}.
{{ __('ads.reach') }}: {{ $offer->performanceReport->reach ?? __('ads.not_reported') }}.</p>
@endif
</article>
@empty<p>{{ __('ads.no_offers_sent') }}</p>@endforelse
<h2>{{ __('ads.recommendations') }}</h2>
<p>{{ __('ads.ranking_details') }}</p>
@forelse($recommendations as $result)
<article><h3>{{ $result['package']->property->display_name }}</h3>
<p>{{ $result['package']->title }} · {{ str_replace('_',' ',$result['package']->placement_type) }} · {{ number_format($result['package']->price_minor / 100,2) }} {{ $result['package']->currency }}</p>
<p>{{ __('ads.country') }}: {{ $result['package']->property->country_code }} · {{ __('ads.city_short') }}: {{ $result['package']->property->city ?: __('ads.unspecified') }} · {{ __('ads.category_short') }}: {{ $result['package']->property->category }}</p>
<p>{{ __('ads.relevance_points') }}: {{ $result['score'] }} / 100 {{ __('ads.not_performance') }}</p>
<ul>@foreach($result['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
<a href="{{ $result['package']->property->profile_url }}" target="_blank" rel="noopener noreferrer">{{ __('ads.inspect_social') }}</a>
@if($campaign->status === 'approved')
<form action="{{ route('ads.campaign.offer',$campaign) }}" method="post">@csrf
<input type="hidden" name="package_id" value="{{ $result['package']->id }}">
<button type="submit">{{ __('ads.send_offer') }}</button></form>
@endif
</article>
@empty<p>{{ __('ads.empty_matches') }}</p>@endforelse
</body></html>