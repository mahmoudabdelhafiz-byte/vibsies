@extends('layouts.public')
@section('title',__('Find creators').' — Vibsies')
@section('content')
<h1>{{ __('Find a creator for your next video') }}</h1>
<form method="get" action="{{ route('home') }}"><input name="q" aria-label="{{ __('Search creators') }}" placeholder="{{ __('Search creators') }}" value="{{ request('q') }}"><button>{{ __('Search') }}</button></form>
<section class="grid">
@forelse($packages as $package)
<article class="card"><h2>{{ $package->creator->display_name }}</h2><p>{{ $package->title }}</p><p>{{ $package->description }}</p><strong>{{ number_format($package->price_minor/100,2) }} {{ $package->currency }}</strong><p>{{ __('Bookings require an account. Payments are disabled during development.') }}</p></article>
@empty<p>{{ __('No approved creators listed yet.') }}</p>@endforelse
</section>
{{ $packages->links() }}
@endsection