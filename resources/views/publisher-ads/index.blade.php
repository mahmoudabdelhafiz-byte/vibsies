<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Vibsies Ads — Publisher</title>
<style>body{font:16px system-ui,sans-serif;max-width:850px;margin:2rem auto;padding:0 1rem;color:#172b4d}section{border:1px solid #ddd;border-radius:12px;padding:1.2rem;margin:1rem 0}input,select,textarea,button{display:block;max-width:100%;padding:.6rem;margin:.5rem 0 1rem}button{background:#116cbd;color:white;border:0;border-radius:8px;cursor:pointer}label{font-weight:600}small{color:#555}</style></head><body>
<nav><a href="{{ route('dashboard') }}">Dashboard</a> · <a href="{{ route('home') }}">Marketplace</a></nav>
<h1>Vibsies Ads — Your publisher properties</h1>
<p>Register Facebook pages, groups, professional profiles or Instagram creator accounts. Ownership review is required. Promoting Vibsies is entirely optional.</p>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
@if($errors->any())<section role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></section>@endif
<section><h2>Add a publishing property</h2>
<form method="post" action="{{ route('ads.publisher.store') }}">@csrf
<label>Platform<select name="platform" required><option value="facebook">Facebook</option><option value="instagram">Instagram</option></select></label>
<label>Account type<select name="property_type" required><option value="page">Facebook page</option><option value="group">Facebook group</option><option value="professional_profile">Facebook professional profile</option><option value="creator_account">Instagram creator account</option></select></label>
<label>Display name<input name="display_name" maxlength="160" required value="{{ old('display_name') }}"></label>
<label>Public account URL<input name="profile_url" type="url" required value="{{ old('profile_url') }}" placeholder="https://www.facebook.com/..."></label>
<label>Audience category<input name="category" required maxlength="120" value="{{ old('category') }}" placeholder="New Cairo community"></label>
<label>Country<select name="country_code"><option value="EG">Egypt</option><option value="SA">Saudi Arabia</option></select></label>
<label>City<input name="city" maxlength="120" value="{{ old('city') }}"></label>
<label>Reported followers / members<input name="reported_followers" type="number" min="0" value="{{ old('reported_followers') }}"></label>
<label>Description<textarea name="description" maxlength="2000">{{ old('description') }}</textarea></label>
<label>Ownership evidence URL (optional)<input name="ownership_evidence_url" type="url" value="{{ old('ownership_evidence_url') }}" placeholder="https://www.facebook.com/..."></label><label>Ownership evidence details (optional)<textarea name="ownership_evidence_note" maxlength="2000" placeholder="Explain how the administrator can verify account control. Do not share passwords.">{{ old('ownership_evidence_note') }}</textarea></label><small>Audience figures are self-reported until verified. Do not share account passwords.</small>
<button type="submit">Submit property for review</button>
</form></section>
<h2>My properties</h2>
@forelse($properties as $property)
<section><h3>{{ $property->display_name }}</h3><p>{{ ucfirst($property->platform) }} · {{ str_replace('_',' ', $property->property_type) }} · {{ $property->category }} · {{ $property->verification_status }}</p>
<a href="{{ $property->profile_url }}" rel="noopener noreferrer" target="_blank">View property</a>
@if($property->verification_status === 'verified')
<h4>Optional: Founding Promoter</h4>
<p>Publish a clear disclosure about your Vibsies partnership on your account, then submit the post link. Participation is not required to receive advertiser bookings. Rewards only apply once approved and enabled by the finance workflow.</p>
@if($property->promotions->whereIn('status',['pending','approved'])->isEmpty())
<form method="post" action="{{ route('ads.publisher.promotion', $property) }}">@csrf
<label>Promotional post URL<input name="post_url" type="url" required placeholder="https://www.facebook.com/..."></label>
<label>Format<select name="post_format"><option value="post">Post</option><option value="video">Video</option></select></label>
<label><input type="checkbox" name="disclosure_confirmed" value="1" required> I clearly disclosed my commercial relationship or incentive as applicable.</label>
<button type="submit">Submit promotion for review</button></form>
@endif
@endif
@if($property->verification_status === 'verified')
<h4>Offer advertising placements</h4>
<form method="post" action="{{ route('ads.publisher.packages.store', $property) }}">@csrf
<label>Package title<input name="title" maxlength="160" required placeholder="Sponsored New Cairo post"></label>
<label>Placement type<select name="placement_type"><option value="feed_post">Feed post</option><option value="story">Story</option><option value="reel">Reel</option><option value="video">Video</option><option value="group_post">Group post</option><option value="pinned_post">Pinned group post</option></select></label>
<label>Price<input name="price" type="number" step="0.01" min="1" required></label>
<label>Currency<select name="currency"><option value="EGP">EGP</option><option value="SAR">SAR</option></select></label>
<label>Days to deliver<input name="delivery_days" type="number" min="1" max="90" value="7" required></label>
<label>Optional display duration (hours)<input name="duration_hours" type="number" min="1" max="720"></label>
<label>Description<textarea name="description" maxlength="2000"></textarea></label>
<button type="submit">Submit placement package</button>
</form>
@endif
@foreach($property->adPackages as $package)
<p>Placement: {{ $package->title }} ({{ $package->placement_type }}) — {{ number_format($package->price_minor / 100, 2) }} {{ $package->currency }} · {{ $package->moderation_status }}</p>
@endforeach
<h4>Audience and content performance</h4>
<p>Provide dated measurements with available public evidence. Self-reported figures are not presented as verified reach.</p>
<form method="post" action="{{ route('ads.publisher.metrics.store', $property) }}">@csrf
<label>Measurement date<input name="observed_on" type="date" max="{{ now()->format('Y-m-d') }}" required></label>
<label>Content category<input name="content_category" maxlength="120" placeholder="Local news"></label>
<label>Followers<input name="follower_count" type="number" min="0"></label>
<label>Views<input name="views" type="number" min="0"></label>
<label>Reach<input name="reach" type="number" min="0"></label>
<label>Reactions<input name="reactions" type="number" min="0"></label>
<label>Comments<input name="comments" type="number" min="0"></label>
<label>Shares<input name="shares" type="number" min="0"></label>
<label>Public evidence URL<input name="evidence_url" type="url" placeholder="https://www.facebook.com/..."></label>
<button type="submit">Save self-reported metrics</button>
</form>
@foreach($property->metricSnapshots as $snapshot)
<p>{{ $snapshot->observed_on->format('Y-m-d') }} — {{ $snapshot->content_category ?: 'General' }} · Views: {{ $snapshot->views ?? 'Not reported' }} · {{ $snapshot->verification_status }}</p>
@endforeach
@foreach($property->promotions as $promotion)<p>Promotion: {{ $promotion->status }} · Referral code: {{ $promotion->referral_code }}</p>@endforeach
</section>
@empty<p>No properties registered yet.</p>@endforelse
</body></html>