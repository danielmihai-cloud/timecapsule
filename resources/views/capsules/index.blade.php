@extends('layouts.app')

@section('title', 'My capsules')

@section('content')
<div class="page-head">
  <h1>My capsules</h1>
  <a class="btn btn-primary" href="/capsules/new">
    @include('partials.icon', ['name' => 'plus'])
    New capsule
  </a>
</div>

@if ($capsules->isEmpty())
<div class="card empty">
  @include('partials.icon', ['name' => 'hourglass'])
  <h2>No capsules yet</h2>
  <p>Write a message to your future self and seal it until the right day.</p>
  <a class="btn btn-primary" href="/capsules/new">Create your first capsule</a>
</div>
@else
<div class="grid">
  @foreach ($capsules as $capsule)
  @include('capsules._card', ['capsule' => $capsule])
  @endforeach
</div>
@endif
@endsection
