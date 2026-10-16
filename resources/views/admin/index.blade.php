@extends('layouts.public')
@section('title',__('Administration'))
@section('content')
<h1>{{ __('Administration') }}</h1>
<p>{{ __('Moderation decisions are audited. Financial actions are unavailable.') }}</p>
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
<h2>{{ __('Open disputes') }}</h2>
@forelse($disputes as $dispute)
<article class="card"><p>{{ $dispute->reason }}</p>
<form method="post" action="{{ route('admin.disputes.decide',$dispute) }}">@csrf
<label>{{ __('Document review and outcome') }}<textarea name="reason" minlength="10" maxlength="2000" required></textarea></label>
<button name="decision" value="resolved">{{ __('Resolve') }}</button>
<button name="decision" value="escalated">{{ __('Escalate') }}</button></form></article>
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