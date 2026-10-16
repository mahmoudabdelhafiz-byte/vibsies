@extends('layouts.public')
@section('title',__('Deliveries and disputes'))
@section('content')
<h1>{{ __('Order') }} {{ $booking->id }}</h1>
<p>{{ __('Status') }}: {{ $booking->status }}</p>
<p>{{ __('Payments are disabled') }}</p>
<h2>{{ __('Deliveries') }}</h2>
@forelse($deliveries as $delivery)
<article class="card">
<p>{{ __('Version') }} {{ $delivery->version }}: {{ $delivery->status }}</p>
<p>{{ $delivery->note }}</p>
@if($delivery->storage_path)
<div><video controls preload="metadata" playsinline style="width:100%;max-width:720px;aspect-ratio:16/9;background:#1c1530;border-radius:10px" controlsList="nodownload" aria-label="{{ __('Private video preview') }}"><source src="{{ route('deliveries.preview',[$booking,$delivery]) }}" type="{{ $delivery->mime_type }}">{{ __('Your browser cannot play this video.') }}</video></div>
<a href="{{ route('deliveries.download',[$booking,$delivery]) }}">{{ __('Download private video') }}</a>
@else
<span>{{ __('Legacy external delivery is unavailable here') }}</span>
@endif
@if($booking->customer_id===auth()->id() && $delivery->status==='submitted' && $booking->status==='in_review' && $delivery->version===$deliveries->max('version'))
<form method="post" action="{{ route('deliveries.review',[$booking,$delivery]) }}">@csrf
<button name="decision" value="accepted">{{ __('Approve') }}</button>
<button name="decision" value="revisions_requested">{{ __('Request changes') }}</button>
</form>
@endif
</article>
@empty<p>{{ __('No deliveries') }}</p>@endforelse
@if($booking->creator->user_id===auth()->id() && in_array($booking->status,['accepted','revisions_requested']))
<h2>{{ __('Upload video') }}</h2>
<form method="post" enctype="multipart/form-data" action="{{ route('deliveries.store',$booking) }}">@csrf
<label>{{ __('Video file (MP4, MOV, WebM; up to 100 MB)') }}<input name="video" type="file" accept="video/mp4,video/quicktime,video/webm" required></label>
<label>{{ __('Note') }}<textarea name="note" maxlength="2000"></textarea></label>
<button>{{ __('Submit video') }}</button></form>
@endif
<h2>{{ __('Disputes') }}</h2>
@forelse($disputes as $dispute)<article class="card">{{ $dispute->status }}: {{ $dispute->reason }}</article>@empty<p>{{ __('No disputes') }}</p>@endforelse
@if(!in_array($booking->status,['completed','declined','disputed']))
<form method="post" action="{{ route('disputes.store',$booking) }}">@csrf
<label>{{ __('Dispute reason') }}<textarea name="reason" minlength="15" maxlength="2000" required></textarea></label>
<button>{{ __('Open dispute') }}</button></form>
@endif
@endsection