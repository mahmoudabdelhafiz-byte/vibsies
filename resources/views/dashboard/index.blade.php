@extends('layouts.public')
@section('title',__('Dashboard'))
@section('content')
<h1>{{ __('Welcome') }}, {{ auth()->user()->name }}</h1>
<p><a href="{{ route('creator.setup') }}">{{ __('Creator studio') }}</a> · <a href="{{ route('business.index') }}">{{ __('Business accounts') }}</a></p>
<form method="post" action="{{ route('logout') }}">@csrf<button>{{ __('Log out') }}</button></form>
@if(session('notice'))<p role="status">{{ session('notice') }}</p>@endif
<h2>{{ __('Notifications') }} <small>({{ $unreadCount }} {{ __('unread') }})</small></h2>
@if($unreadCount > 0)
<form method="post" action="{{ route('notifications.read-all') }}">@csrf<button>{{ __('Mark all as read') }}</button></form>
@endif
@forelse($notifications as $notification)
<article class="card">
 @if(($notification->data['event']??null)==='verification_renewal')
 <a href="{{ route('creator.setup') }}">{{ __('Verification renewal reminder') }} — {{ __($notification->data['verification_type']??'Verification') }} ({{ $notification->data['expires_on']??'' }})</a>
 @elseif(isset($notification->data['booking_id']))
 <a href="{{ route('bookings.show', $notification->data['booking_id']) }}">{{ __('Booking') }} — {{ __($notification->data['event']??'Update') }}</a>
 @else
 <span>{{ __('Notification') }}</span>
 @endif
 <small>{{ $notification->created_at->diffForHumans() }}</small>
 @if(!$notification->read_at)
 <form method="post" action="{{ route('notifications.read',$notification->id) }}">@csrf
 <button>{{ __('Mark as read') }}</button></form>
 @else <small>{{ __('Read') }}</small> @endif
</article>
@empty<p>{{ __('No notifications yet') }}</p>@endforelse
{{ $notifications->links() }}
<h2>{{ __('My bookings') }}</h2>
@forelse($customerBookings as $booking)<article class="card"><a href="{{ route('bookings.show',$booking) }}">{{ $booking->creator?->display_name ?? __('Booking') }}</a> — {{ __('Status') }}: {{ __($booking->status) }} — {{ number_format($booking->price_minor/100,2) }} {{ $booking->currency }}</article>@empty<p>{{ __('No bookings') }}</p>@endforelse
{{ $customerBookings->links() }}
<h2>{{ __('Creator studio') }}: {{ $creator?->display_name ?? __('Not yet registered') }}</h2>
<h3>{{ __('Incoming bookings') }}</h3>
@forelse($incomingBookings as $booking)<article class="card"><a href="{{ route('bookings.show',$booking) }}">{{ $booking->customer?->name ?? __('Booking') }}</a> — {{ __('Status') }}: {{ __($booking->status) }} — {{ number_format($booking->price_minor/100,2) }} {{ $booking->currency }}<p>{{ \Illuminate\Support\Str::limit($booking->brief,140) }}</p>@if($booking->status==='requested')<form method="post" action="{{ route('bookings.decision',$booking) }}">@csrf<button name="decision" value="accepted">{{ __('Accept') }}</button> <button name="decision" value="declined">{{ __('Decline') }}</button></form>@endif</article>@empty<p>{{ __('No incoming bookings') }}</p>@endforelse
@if($creator){{ $incomingBookings->links() }}@endif
@endsection