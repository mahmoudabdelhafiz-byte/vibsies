@extends('layouts.public')
@section('title', $creator->display_name.' — Vibsies')
@section('content')
<article class="card">
 <h1>{{ $creator->display_name }}</h1>
 <p>{{ __('Category') }}: {{ $creator->category }}</p>
 <p>{{ __('Country') }}: {{ $creator->country_code }}</p>
 @if($creator->bio)<p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $creator->bio }}</p>@endif
</article>
@if($samples->isNotEmpty())
<h2>{{ __('Portfolio samples') }}</h2>
<section class="grid">@foreach($samples as $sample)
<article class="card"><h3>{{ $sample->title }}</h3>
@if($sample->description)<p>{{ $sample->description }}</p>@endif
<a href="{{ $sample->video_url }}" target="_blank" rel="noopener noreferrer nofollow">{{ __('Watch portfolio video') }}</a>
</article>@endforeach</section>
@endif
<h2>{{ __('Available packages') }}</h2>
<section class="grid">
@foreach($packages as $package)
<article class="card">
 <h3>{{ $package->title }}</h3>
 @if($package->description)<p>{{ $package->description }}</p>@endif
 <p>{{ __('Turnaround days') }}: {{ $package->turnaround_days }}</p>
 <p>{{ __('Revisions') }}: {{ $package->revision_limit }}</p>
 <p>{{ __('Creator price') }}: {{ number_format($package->price_minor/100,2) }} {{ $package->currency }}</p>
 <p>{{ __('Estimated platform fee') }}: {{ number_format($quotes[$package->id]['fee_minor']/100,2) }} {{ $package->currency }}</p>
 <p><strong>{{ __('Estimated customer total') }}: {{ number_format($quotes[$package->id]['total_minor']/100,2) }} {{ $package->currency }}</strong></p>
 <p>{{ __('Bookings require an account. Payments are disabled during development.') }}</p>
 @auth
 @if(auth()->id() !== $creator->user_id)
 <form method="post" action="{{ route('bookings.store') }}">@csrf
  <input type="hidden" name="creator_package_id" value="{{ $package->id }}">
  <label for="portfolio-brief-{{ $package->id }}">{{ __('Describe your video requirements') }}</label>
  <textarea id="portfolio-brief-{{ $package->id }}" name="brief" rows="4" minlength="20" maxlength="5000" required></textarea>
  <button type="submit">{{ __('Request booking') }}</button>
 </form>
 @else<p>{{ __('You cannot book your own package.') }}</p>@endif
 @else<a href="{{ route('login') }}">{{ __('Log in to request booking') }}</a>@endauth
</article>
@endforeach
</section>
<p><a href="{{ route('home') }}">{{ __('Find creators') }}</a></p>
@endsection
