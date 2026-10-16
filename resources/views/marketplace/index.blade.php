@extends('layouts.public')
@section('title',__('Find creators').' — Vibsies')
@section('content')
<h1>{{ __('Find a creator for your next video') }}</h1>
<form method="get" action="{{ route('home') }}"><input name="q" aria-label="{{ __('Search creators') }}" placeholder="{{ __('Search creators') }}" value="{{ request('q') }}"><button>{{ __('Search') }}</button></form>
<section class="grid">
@forelse($packages as $package)
<article class="card"><h2>{{ $package->creator->display_name }}</h2><p>{{ $package->title }}</p><p>{{ $package->description }}</p><strong>{{ number_format($package->price_minor/100,2) }} {{ $package->currency }}</strong>
<p>{{ __('Estimated platform fee') }}: {{ number_format(intdiv($package->price_minor*15+50,100)/100,2) }} {{ $package->currency }}</p>
<p><strong>{{ __('Estimated customer total') }}: {{ number_format(($package->price_minor+intdiv($package->price_minor*15+50,100))/100,2) }} {{ $package->currency }}</strong></p><p>{{ __('Bookings require an account. Payments are disabled during development.') }}</p>
@auth
@if(auth()->id() !== $package->creator->user_id)
<form method="post" action="{{ route('bookings.store') }}">
@csrf
<input type="hidden" name="creator_package_id" value="{{ $package->id }}">
<label for="brief-{{ $package->id }}">{{ __('Describe your video requirements') }}</label>
<textarea id="brief-{{ $package->id }}" name="brief" rows="4" minlength="20" maxlength="5000" required placeholder="{{ __('Tell the creator about your brand, audience, video format and deadline') }}">{{ old('creator_package_id')===$package->id ? old('brief') : '' }}</textarea>
<button type="submit">{{ __('Request booking') }}</button>
</form>
@else<p>{{ __('You cannot book your own package.') }}</p>@endif
@else<a href="{{ route('login') }}">{{ __('Log in to request booking') }}</a>@endauth</article>
@empty<p>{{ __('No approved creators listed yet.') }}</p>@endforelse
</section>
{{ $packages->links() }}
@endsection