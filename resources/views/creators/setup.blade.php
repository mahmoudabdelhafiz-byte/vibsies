@extends('layouts.public')
@section('title',__('Creator studio'))
@section('content')
<h1>{{ __('Creator studio') }}</h1>
<form method="post" action="{{ route('creator.store') }}">@csrf
<label>{{ __('Display name') }}<input name="display_name" value="{{ old('display_name',$creator?->display_name) }}" required></label>
<label>{{ __('Category') }}<input name="category" value="{{ old('category',$creator?->category) }}" required></label>
<label>{{ __('Bio') }}<textarea name="bio">{{ old('bio',$creator?->bio) }}</textarea></label>
<label>{{ __('Country') }}<select name="country_code"><option value="EG" @selected(old('country_code',$creator?->country_code)==='EG')>{{ __('Egypt') }}</option><option value="SA" @selected(old('country_code',$creator?->country_code)==='SA')>{{ __('Saudi Arabia') }}</option></select></label>
<button>{{ __('Save creator profile') }}</button></form>
@if($creator)
<h2>{{ __('Portfolio samples') }}</h2>
<p>{{ __('Submit a YouTube or Vimeo video URL for approval before it appears publicly.') }}</p>
<form method="post" action="{{ route('creator.samples.store') }}">@csrf
<label>{{ __('Title') }}<input name="title" maxlength="160" required></label>
<label>{{ __('Description') }}<textarea name="description" maxlength="1000"></textarea></label>
<label>{{ __('Video URL') }}<input name="video_url" type="url" maxlength="500" required></label>
<button>{{ __('Submit sample for review') }}</button></form>
@foreach($samples as $sample)
<article class="card"><p>{{ $sample->title }} — {{ $sample->moderation_status }}</p>
<form method="post" action="{{ route('creator.samples.update',$sample) }}">@csrf @method('PUT')
<label>{{ __('Title') }}<input name="title" value="{{ $sample->title }}" maxlength="160" required></label>
<label>{{ __('Description') }}<textarea name="description" maxlength="1000">{{ $sample->description }}</textarea></label>
<label>{{ __('Video URL') }}<input type="url" name="video_url" value="{{ $sample->video_url }}" maxlength="500" required></label>
<button>{{ __('Save and resubmit for review') }}</button></form>
<form method="post" action="{{ route('creator.samples.destroy',$sample) }}">@csrf @method('DELETE')
<button>{{ __('Remove sample') }}</button></form></article>
@endforeach
<h2>{{ __('Create package') }}</h2>
<form method="post" action="{{ route('creator.package') }}">@csrf
<label>{{ __('Package title') }}<input name="title" required></label>
<label>{{ __('Description') }}<textarea name="description"></textarea></label>
<label>{{ __('Price (EGP)') }}<input name="price" type="number" min="1" step="0.01" required></label>
<label>{{ __('Turnaround days') }}<input name="turnaround_days" type="number" value="7" min="1" max="90" required></label>
<label>{{ __('Revisions') }}<input name="revision_limit" type="number" value="1" min="0" max="10" required></label>
<button>{{ __('Create package') }}</button></form>
<h2>{{ __('Request verification') }}</h2>
<p>{{ __('Verification requests require manual review. Never submit passwords or banking credentials.') }}</p>
<form method="post" action="{{ route('verification.submit') }}">@csrf
<label>{{ __('Verification type') }}<select name="type"><option value="identity">{{ __('Identity') }}</option><option value="social">{{ __('Social profile') }}</option><option value="advertising_license">{{ __('Advertising license') }}</option></select></label>
<label>{{ __('Reference') }}<input name="reference" minlength="4" maxlength="200" required></label>
<button>{{ __('Submit for review') }}</button></form>
@endif

<h2>{{ __('Rejected packages — revise and resubmit') }}</h2>
@foreach($rejectedPackages as $package)
<article class="card">
 <form method="post" action="{{ route('creator.packages.resubmit',$package) }}">@csrf
  <label>{{ __('Package title') }}<input name="title" value="{{ old('title',$package->title) }}" required></label>
  <label>{{ __('Description') }}<textarea name="description">{{ old('description',$package->description) }}</textarea></label>
  <label>{{ __('Price (EGP)') }}<input name="price" type="number" step="0.01" min="1" value="{{ number_format($package->price_minor/100,2,'.','') }}" required></label>
  <label>{{ __('Turnaround days') }}<input name="turnaround_days" type="number" min="1" max="90" value="{{ $package->turnaround_days }}" required></label>
  <label>{{ __('Revisions') }}<input name="revision_limit" type="number" min="0" max="10" value="{{ $package->revision_limit }}" required></label>
  <button>{{ __('Resubmit package') }}</button>
 </form>
</article>
@endforeach
@endsection