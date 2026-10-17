@extends('layouts.public')
@section('title',__('Register'))
@section('content')
<h1>{{ __('Join Vibsies') }}</h1>
<form method="post" action="{{ route('register.submit') }}">@csrf
<label>{{ __('Name') }}<input name="name" value="{{ old('name') }}" required></label>
<label>{{ __('Email') }}<input type="email" name="email" value="{{ old('email') }}" required></label>
<label>{{ __('Password') }}<input type="password" name="password" autocomplete="new-password" required></label>
<label>{{ __('Confirm password') }}<input type="password" name="password_confirmation" autocomplete="new-password" required></label>
<button>{{ __('Register') }}</button>
</form>
@endsection