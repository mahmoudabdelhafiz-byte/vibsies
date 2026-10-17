@extends('layouts.public')
@section('title',__('Business accounts'))
@section('content')
<h1>{{ __('Business accounts') }}</h1>
@forelse($businesses as $business)<article class="card"><h2>{{ $business->name }}</h2><p>{{ $business->country_code }}</p></article>@empty<p>{{ __('No businesses yet') }}</p>@endforelse
<h2>{{ __('Add business') }}</h2>
<form method="post" action="{{ route('business.store') }}">@csrf
<label>{{ __('Name') }}<input name="name" value="{{ old('name') }}" required maxlength="160"></label>
<label>{{ __('Country') }}<select name="country_code"><option value="EG">{{ __('Egypt') }}</option><option value="SA">{{ __('Saudi Arabia') }}</option></select></label>
<button>{{ __('Add business') }}</button>
</form>
@endsection