@extends('layouts.app')

@section('title', 'Sign up')

@section('content')
<div class="auth-card">
  <div class="page-head">
    <div>
      <h1>Create an account</h1>
      <p class="subtitle">Start sending messages to the future.</p>
    </div>
  </div>
  <form class="card" method="post" action="/register" novalidate>
    <input type="hidden" name="_token" value="{{ csrf_token() }}">

    <div class="field{{ $errors->has('email') ? ' has-error' : '' }}">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" autocomplete="email" required value="{{ $values['email'] ?? '' }}"@if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error"@endif>
      @if ($errors->has('email'))
      <span class="error" id="email-error">{{ $errors->first('email') }}</span>
      @endif
    </div>

    <div class="field{{ $errors->has('password') ? ' has-error' : '' }}">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" autocomplete="new-password" required @if ($errors->has('password'))aria-invalid="true" aria-describedby="password-error password-hint"@else aria-describedby="password-hint"@endif>
      @if ($errors->has('password'))
      <span class="error" id="password-error">{{ $errors->first('password') }}</span>
      @endif
      <span class="hint" id="password-hint">At least 8 characters.</span>
    </div>

    <div class="field{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
      <label for="password_confirmation">Confirm password</label>
      <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required @if ($errors->has('password_confirmation'))aria-invalid="true" aria-describedby="password_confirmation-error"@endif>
      @if ($errors->has('password_confirmation'))
      <span class="error" id="password_confirmation-error">{{ $errors->first('password_confirmation') }}</span>
      @endif
    </div>

    <button type="submit" class="btn btn-primary btn-block">Sign up</button>
    <p class="form-footer">Already have an account? <a href="/login">Log in</a></p>
  </form>
</div>
@endsection
