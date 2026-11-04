<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Vibsies Ads — Publisher</title>
<link rel="stylesheet" href="{{ asset('css/ads-mobile.css') }}"></head><body>
@include('ads.partials.locale')

<nav><a href="{{ route('dashboard') }}">{{ __('ads.dashboard') }}</a> · <a href="{{ route('home') }}">{{ __('ads.marketplace') }}</a></nav>
<h1>{{ __('ads.publisher_properties') }}</h1><p><a href="{{ route('ads.publisher.offers') }}">{{ __('ads.incoming_offers') }}</a></p>
<p>Register Facebook pages, groups, professional profiles or Instagram creator accounts. Ownership review is required. Promoting Vibsies is entirely optional.</p>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
@if($errors->any())<section role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></section>@endif
<section><h2>{{ __('ads.add_property') }}</h2>
<form method="post" action="{{ route('ads.publisher.store') }}">@csrf
<label>{{ __('ads.platform') }}<select name="platform" required><option value="facebook">Facebook</option><option value="instagram">Instagram</option></select></label>
<label>{{ __('ads.account_type') }}<select name="property_type" required><option value="page">Facebook page</option><option value="group">Facebook group</option><option value="professional_profile">Facebook professional profile</option><option value="creator_account">Instagram creator account</option></select></label>
<label>{{ __('ads.display_name') }}<input name="display_name" maxlength="160" required value="{{ old('display_name') }}"></label>
<label>{{ __('ads.public_url') }}<input name="profile_url" type="url" required value="{{ old('profile_url') }}" placeholder="https://www.facebook.com/..."></label>
<label>Audience category<input name="category" required maxlength="120" value="{{ old('category') }}" placeholder="New Cairo community"></label>
<label>{{ __('ads.country') }}<select name="country_code"><option value="EG">{{ __('ads.egypt') }}</option><option value="SA">{{ __('ads.saudi') }}</option></select></label>
<label>City<input name="city" maxlength="120" value="{{ old('city') }}"></label>
<label>{{ __('ads.followers') }}<input name="reported_followers" type="number" min="0" value="{{ old('reported_followers') }}"></label>
<label>{{ __('ads.description') }}<textarea name="description" maxlength="2000">{{ old('description') }}</textarea></label>
<label>{{ __('ads.evidence_url') }}<input name="ownership_evidence_url" type="url" value="{{ old('ownership_evidence_url') }}" placeholder="https://www.facebook.com/..."></label><label>{{ __('ads.evidence_details') }}<textarea name="ownership_evidence_note" maxlength="2000" placeholder="Explain how the administrator can verify account control. Do not share passwords.">{{ old('ownership_evidence_note') }}</textarea></label><small>Audience figures are self-reported until verified. Do not share account passwords.</small>
<button type="submit">{{ __('ads.submit_property') }}</button>
</form></section>
<h2>{{ __('ads.my_properties') }}</h2>
@forelse($properties as $property)
<section><h3>{{ $property->display_name }}</h3><p>{{ ucfirst($property->platform) }} · {{ str_replace('_',' ', $property->property_type) }} · {{ $property->category }} · {{ $property->verification_status }}</p>
<a href="{{ $property->profile_url }}" rel="noopener noreferrer" target="_blank">{{ __('ads.view_property') }}</a>
@if($property->verification_status === 'pending' && $property->verification_expires_at && $property->verification_expires_at->isPast())
<form method="post" action="{{ route('ads.publisher.challenge.renew',$property) }}">@csrf
<p>Ownership code expired. You can renew twice; old evidence will be cleared.</p>
<button type="submit">{{ __('ads.renew_code') }}</button></form>
@endif
@if($property->verification_status === 'pending' && $property->verification_expires_at && $property->verification_expires_at->isFuture())
<h4>{{ __('ads.verify_ownership') }}</h4>
@if($property->challenge_submitted_at)<form method="post" action="{{ route('ads.publisher.evidence.withdraw',$property) }}">@csrf<button type="submit">{{ __('ads.withdraw_evidence') }}</button></form>@endif
<p>Temporarily place the following code in a public bio or post on this account, then share the direct public URL for manual review. Remove the code after verification. Never share a password or private access token.</p>
<p><strong>{{ $property->verification_token }}</strong></p>
<p>Expires {{ $property->verification_expires_at->format('Y-m-d H:i') }} UTC.</p>
<form method="post" action="{{ route('ads.publisher.evidence',$property) }}">@csrf
<label>{{ __('ads.proof_url') }}<input type="url" name="ownership_evidence_url" required placeholder="https://www.facebook.com/..."></label>
<label>{{ __('ads.evidence_explanation') }}<textarea name="ownership_evidence_note" minlength="12" maxlength="2000" required placeholder="Where the administrator can see the code"></textarea></label>
<button type="submit">{{ __('ads.submit_evidence') }}</button></form>
@endif
@if($property->verification_status === 'verified')
<h4>{{ __('ads.founding_promoter') }}</h4>
<p>Use the referral link shown below in your Vibsies post or video, then submit your published URL for review. Referral registrations do not qualify for incentives before approval.</p>
<p>Publish a clear disclosure about your Vibsies partnership on your account, then submit the post link. Participation is not required to receive advertiser bookings. Rewards only apply once approved and enabled by the finance workflow.</p>
@if($property->promotions->whereIn('status',['pending','approved'])->isEmpty())
<form method="post" action="{{ route('ads.publisher.promotion', $property) }}">@csrf
<label>{{ __('ads.promo_url') }}<input name="post_url" type="url" required placeholder="https://www.facebook.com/..."></label>
<label>{{ __('ads.format') }}<select name="post_format"><option value="post">Post</option><option value="video">{{ __('ads.video') }}</option></select></label>
<label><input type="checkbox" name="disclosure_confirmed" value="1" required> I clearly disclosed my commercial relationship or incentive as applicable.</label>
<button type="submit">{{ __('ads.submit_promotion') }}</button></form>
@endif
@endif
@if($property->verification_status === 'verified')
<h4>{{ __('ads.offer_placements') }}</h4>
<form method="post" action="{{ route('ads.publisher.packages.store', $property) }}">@csrf
<label>{{ __('ads.package_title') }}<input name="title" maxlength="160" required placeholder="Sponsored New Cairo post"></label>
<label>{{ __('ads.placement_type') }}<select name="placement_type"><option value="feed_post">{{ __('ads.feed_post') }}</option><option value="story">{{ __('ads.story') }}</option><option value="reel">{{ __('ads.reel') }}</option><option value="video">{{ __('ads.video') }}</option><option value="group_post">{{ __('ads.group_post') }}</option><option value="pinned_post">Pinned group post</option></select></label>
<label>{{ __('ads.price') }}<input name="price" type="number" step="0.01" min="1" required></label>
<label>{{ __('ads.currency') }}<select name="currency"><option value="EGP">EGP</option><option value="SAR">SAR</option></select></label>
<label>{{ __('ads.delivery_days') }}<input name="delivery_days" type="number" min="1" max="90" value="7" required></label>
<label>Optional display duration (hours)<input name="duration_hours" type="number" min="1" max="720"></label>
<label>{{ __('ads.description') }}<textarea name="description" maxlength="2000"></textarea></label>
<button type="submit">{{ __('ads.submit_package') }}</button>
</form>
@endif
@foreach($property->adPackages as $package)
<p>Placement: {{ $package->title }} ({{ $package->placement_type }}) — {{ number_format($package->price_minor / 100, 2) }} {{ $package->currency }} · {{ $package->moderation_status }}</p>
@endforeach
<h4>{{ __('ads.performance') }}</h4>
<p>Provide dated measurements with available public evidence. Self-reported figures are not presented as verified reach.</p>
<form method="post" action="{{ route('ads.publisher.metrics.store', $property) }}">@csrf
<label>{{ __('ads.measurement_date') }}<input name="observed_on" type="date" max="{{ now()->format('Y-m-d') }}" required></label>
<label>{{ __('ads.category') }}<input name="content_category" maxlength="120" placeholder="Local news"></label>
<label>Followers<input name="follower_count" type="number" min="0"></label>
<label>{{ __('ads.views') }}<input name="views" type="number" min="0"></label>
<label>{{ __('ads.reach') }}<input name="reach" type="number" min="0"></label>
<label>{{ __('ads.reactions') }}<input name="reactions" type="number" min="0"></label>
<label>{{ __('ads.comments') }}<input name="comments" type="number" min="0"></label>
<label>{{ __('ads.shares') }}<input name="shares" type="number" min="0"></label>
<label>Public evidence URL<input name="evidence_url" type="url" placeholder="https://www.facebook.com/..."></label>
<button type="submit">{{ __('ads.save_metrics') }}</button>
</form>
@foreach($property->metricSnapshots as $snapshot)
<p>{{ $snapshot->observed_on->format('Y-m-d') }} — {{ $snapshot->content_category ?: 'General' }} · Views: {{ $snapshot->views ?? 'Not reported' }} · {{ $snapshot->verification_status }}</p>
@endforeach
@foreach($property->promotions as $promotion)
<p>Vibsies promotion: {{ $promotion->status }}</p>
@if(in_array($promotion->status,['draft','pending','approved'],true))
<p>Share your referral URL: <a href="{{ route('ads.referral.visit',$promotion->referral_code) }}">{{ route('ads.referral.visit',$promotion->referral_code) }}</a></p>
<p>Referral registrations: {{ $promotion->referrals()->count() }} (registration only; referrals are not qualified or payable)</p>
@endif
@endforeach
@if($property->rewardEntitlement)
<p>Founding Publisher benefit: {{ $property->rewardEntitlement->publisher_share_percent }}% publisher share on up to {{ $property->rewardEntitlement->placement_limit }} eligible completed paid placements, subject to settlement rules. Valid until {{ $property->rewardEntitlement->expires_at->format('Y-m-d') }}. No rewards are currently payable.</p>
@endif
</section>
@empty<p>No properties registered yet.</p>@endforelse
</body></html>