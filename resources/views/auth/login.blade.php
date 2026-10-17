@extends('layouts.public')
@section('title',__('Log in'))
@section('content')
<h1>{{ __('Log in') }}</h1>
<form method="post" action="{{ route('login.submit') }}">@csrf
<label>{{ __('Email') }}<input name="email" type="email" value="{{ old('email') }}" autocomplete="email" required></label>
<label>{{ __('Password') }}<input name="password" type="password" autocomplete="current-password" required></label>
<button>{{ __('Log in') }}</button>
</form>
@endsection