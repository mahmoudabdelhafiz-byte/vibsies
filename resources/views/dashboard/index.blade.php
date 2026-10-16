@extends('layouts.public')
@section('title',__('Dashboard'))
@section('content')
<h1>{{ __('Welcome') }}, {{ auth()->user()->name }}</h1>
<p><a href="{{ route('creator.setup') }}">{{ __('Creator studio') }}</a> · <a href="{{ route('business.index') }}">{{ __('Business accounts') }}</a></p>
<form method="post" action="{{ route('logout') }}">@csrf<button>{{ __('Log out') }}</button></form>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
<h2>{{ __('My bookings') }}</h2>
@forelse($customerBookings as $booking)<article class="card"><a href="{{ route('bookings.show',$booking) }}">{{ $booking->creator?->display_name ?? __('Booking') }}</a> — {{ __('Status') }}: {{ __($booking->status) }} — {{ number_format($booking->price_minor/100,2) }} {{ $booking->currency }}</article>@empty<p>{{ __('No bookings') }}</p>@endforelse
{{ $customerBookings->links() }}
<h2>{{ __('Creator studio') }}: {{ $creator?->display_name ?? __('Not yet registered') }}</h2>
<h3>{{ __('Incoming bookings') }}</h3>
@forelse($incomingBookings as $booking)<article class="card"><a href="{{ route('bookings.show',$booking) }}">{{ $booking->customer?->name ?? __('Booking') }}</a> — {{ __('Status') }}: {{ __($booking->status) }} — {{ number_format($booking->price_minor/100,2) }} {{ $booking->currency }}<p>{{ \Illuminate\Support\Str::limit($booking->brief,140) }}</p>@if($booking->status==='requested')<form method="post" action="{{ route('bookings.decision',$booking) }}">@csrf<button name="decision" value="accepted">{{ __('Accept') }}</button> <button name="decision" value="declined">{{ __('Decline') }}</button></form>@endif</article>@empty<p>{{ __('No incoming bookings') }}</p>@endforelse
@if($creator){{ $incomingBookings->links() }}@endif
@endsection