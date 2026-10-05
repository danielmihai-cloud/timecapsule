{{-- Shared error page. Laravel picks errors/{status}.blade.php automatically; those files extend this one. --}}
@extends('layouts.app')

@section('title', $heading)

@section('content')
<div class="card error-page">
  <p class="error-code">{{ $code }}</p>
  <h1>{{ $heading }}</h1>
  <p class="subtitle">{{ $text }}</p>
  <div class="actions center">
    <a class="btn btn-secondary" href="/">Go home</a>
  </div>
</div>
@endsection
