@extends('layouts.public')
@section('title',__('Agreement'))
@section('content')
<h1>{{ __('Agreement') }} — {{ __('Booking') }} {{ $booking->id }}</h1>
<p>{{ __('Prototype consent only; not a certified signature or payment confirmation.') }}</p>
<pre style="white-space:pre-wrap;overflow-wrap:anywhere" dir="ltr">{{ json_encode($agreement->terms,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
<p>SHA-256: <code>{{ $agreement->terms_sha256 }}</code></p>
<p>{{ __('Customer') }}: {{ $agreement->customer_accepted_at ? __('Accepted') : __('Pending') }}</p>
<p>{{ __('Creator') }}: {{ $agreement->creator_accepted_at ? __('Accepted') : __('Pending') }}</p>
<form method="post" action="{{ route('bookings.accept-agreement',$booking) }}">@csrf<button>{{ __('Accept this agreement') }}</button></form>
@endsection