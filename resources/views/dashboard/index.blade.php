@extends('layouts.public')
@section('title',__('Dashboard'))
@section('content')
<h1>{{ __('Welcome') }}, {{ auth()->user()->name }}</h1>
<p><a href="{{ route('creator.setup') }}">{{ __('Creator studio') }}</a> · <a href="{{ route('business.index') }}">{{ __('Business accounts') }}</a></p>
<form method="post" action="{{ route('logout') }}">@csrf<button>{{ __('Log out') }}</button></form>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
<h2>{{ __('My bookings') }}</h2>
@forelse($customerBookings as $booking)<article class="card"><a href="{{ route('bookings.show',$booking) }}">{{ __('Booking') }} {{ $booking->id }}</a> — {{ $booking->status }}</article>@empty<p>{{ __('No bookings') }}</p>@endforelse
{{ $customerBookings->links() }}
<h2>{{ __('Creator studio') }}: {{ $creator?->display_name ?? __('Not yet registered') }}</h2>
<h3>{{ __('Incoming bookings') }}</h3>
@forelse($incomingBookings as $booking)<article class="card"><a href="{{ route('bookings.show',$booking) }}">{{ __('Booking') }} {{ $booking->id }}</a></article>@empty<p>{{ __('No incoming bookings') }}</p>@endforelse
@endsection