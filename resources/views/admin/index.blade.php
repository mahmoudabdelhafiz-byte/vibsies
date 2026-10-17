@extends('layouts.public')
@section('title',__('Administration'))
@section('content')
<h1>{{ __('Administration') }}</h1>
<p>{{ __('Moderation decisions are audited. Financial actions are unavailable.') }}</p>
<h2>{{ __('Marketplace overview') }}</h2>
<section class="grid">
@foreach([
 'users'=>__('Registered users'),
 'approved_creators'=>__('Approved creators'),
 'active_packages'=>__('Active approved packages'),
 'pending_creators'=>__('Creators pending review'),
 'pending_packages'=>__('Packages awaiting approval'),
 'bookings_total'=>__('Total bookings'),
 'bookings_requested'=>__('Bookings awaiting response'),
 'bookings_in_review'=>__('Videos awaiting customer review'),
 'deliveries_total'=>__('Total video deliveries'),
 'disputes_open'=>__('Unresolved disputes')
] as $key=>$label)
<article class="card"><p>{{ $label }}</p><strong style="font-size:1.8rem">{{ number_format($metrics[$key]) }}</strong></article>
@endforeach
</section>
<p>{{ __('Operational counts only. No payment or revenue figures are shown.') }}</p>
<h2>{{ __('Creators pending review') }}</h2>
@forelse($creators as $creator)
<article class="card"><strong>{{ $creator->display_name }}</strong> — {{ $creator->category }}
<form method="post" action="{{ route('admin.creators.decide',$creator) }}">@csrf
<label>{{ __('Reason') }}<input name="reason" maxlength="1000"></label>
<button name="decision" value="approved">{{ __('Approve') }}</button>
<button name="decision" value="rejected">{{ __('Reject') }}</button></form></article>
@empty<p>{{ __('No pending creators') }}</p>@endforelse
<h2>{{ __('Packages awaiting approval') }}</h2>
@forelse($packages as $package)
<article class="card"><strong>{{ $package->title }}</strong> — {{ $package->price_minor }} {{ __('minor units') }}
<form method="post" action="{{ route('admin.packages.decide',$package) }}">@csrf
<label>{{ __('Reason') }}<input name="reason" maxlength="1000"></label>
<button name="decision" value="approved">{{ __('Approve') }}</button>
<button name="decision" value="rejected">{{ __('Reject') }}</button></form></article>
@empty<p>{{ __('No pending packages') }}</p>@endforelse
<h2>{{ __('Portfolio samples awaiting review') }}</h2>
@forelse($samples as $sample)
<article class="card"><strong>{{ $sample->creator->display_name }} — {{ $sample->title }}</strong>
<p><a href="{{ $sample->video_url }}" target="_blank" rel="noopener noreferrer nofollow">{{ __('Review external video') }}</a></p>
<form method="post" action="{{ route('admin.samples.decide',$sample) }}">@csrf
<label>{{ __('Reason') }}<input name="reason" maxlength="1000"></label>
<button name="decision" value="approved">{{ __('Approve') }}</button>
<button name="decision" value="rejected">{{ __('Reject') }}</button></form></article>
@empty<p>{{ __('No portfolio samples awaiting review') }}</p>@endforelse
<h2>{{ __('Open and escalated disputes') }}</h2>
@forelse($disputes as $dispute)
<article class="card"><p>{{ __('Status') }}: {{ $dispute->status }}</p><p>{{ $dispute->reason }}</p>
<form method="post" action="{{ route('admin.disputes.decide',$dispute) }}">@csrf
<label>{{ __('Document review and outcome') }}<textarea name="reason" minlength="10" maxlength="2000" required></textarea></label>
<button name="decision" value="resolved">{{ __('Resolve') }}</button>
@if($dispute->status==='open')<button name="decision" value="escalated">{{ __('Escalate') }}</button>@endif</form></article>
@empty<p>{{ __('No open disputes') }}</p>@endforelse
<h2>{{ __('Pending verification requests') }}</h2>
@forelse($verifications as $verification)
<article class="card"><strong>{{ $verification->creator->display_name }}</strong> — {{ $verification->type }}
<p>{{ __('Reference hash') }}: <code>{{ $verification->reference_hash }}</code></p>
<form method="post" action="{{ route('admin.verifications.review',$verification) }}">@csrf
<label>{{ __('Decision rationale') }}<textarea name="reason" minlength="5" maxlength="1000" required></textarea></label>
<label>{{ __('Expiry, where applicable') }}<input type="date" name="expires_at"></label>
<button name="decision" value="verified">{{ __('Verify') }}</button>
<button name="decision" value="rejected">{{ __('Reject') }}</button></form></article>
@empty<p>{{ __('No pending verifications') }}</p>@endforelse
@endsection