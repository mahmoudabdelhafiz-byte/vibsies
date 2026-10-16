@extends('layouts.public')
@section('title',__('Booking request'))
@section('content')
<h1>{{ __('Booking request') }}</h1>
<p>{{ __('Status') }}: {{ $booking->status }}</p><p>{{ $booking->brief }}</p>
<p>{{ __('Creator price') }}: {{ number_format($booking->price_minor/100,2) }} {{ $booking->currency }}</p>
<p>{{ __('Platform fee') }}: {{ number_format($booking->fee_minor/100,2) }} {{ $booking->currency }}</p>
<p><strong>{{ __('Customer total') }}: {{ number_format(($booking->price_minor + $booking->fee_minor)/100,2) }} {{ $booking->currency }}</strong></p>
<p role="status">{{ __('No payment has been collected. Checkout is disabled.') }}</p>
@if($booking->agreement)<p><a href="{{ route('bookings.agreement',$booking) }}">{{ __('View agreement') }}</a></p>@endif
<p><a href="{{ route('bookings.fulfillment',$booking) }}">{{ __('Deliveries and disputes') }}</a></p>
@if($booking->creator->user_id===auth()->id() && $booking->status==='requested')
<form method="post" action="{{ route('bookings.decision',$booking) }}">@csrf
<button name="decision" value="accepted">{{ __('Accept') }}</button>
<button name="decision" value="declined">{{ __('Decline') }}</button></form>@endif
@endsection