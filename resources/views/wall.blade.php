@extends('layouts.app')

@section('title', 'Public wall')

@section('content')
<div class="page-head">
  <h1>Public wall</h1>
</div>

@if ($capsules->isEmpty())
<div class="card empty">
  @include('partials.icon', ['name' => 'hourglass'])
  <h2>The wall is empty</h2>
  <p>Public capsules appear here once they open.</p>
</div>
@else
<div class="grid">
  @foreach ($capsules as $capsule)
  @include('capsules._card', ['capsule' => $capsule, 'showAuthor' => true])
  @endforeach
</div>
@endif
@endsection
