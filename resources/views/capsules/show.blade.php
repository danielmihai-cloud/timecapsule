@extends('layouts.app')

@section('title', $capsule->title)

@section('content')
<div class="narrow">
  @if ($isOwner)
  <a class="back-link" href="/">
    @include('partials.icon', ['name' => 'arrow-left'])
    Back to my capsules
  </a>
  @else
  <a class="back-link" href="/wall">
    @include('partials.icon', ['name' => 'arrow-left'])
    Back to the wall
  </a>
  @endif

  @if (! $capsule->isOpened())
  {{-- Sealed: only the title and the countdown, never the message or the file. --}}
  <div class="card sealed-view">
    <div class="seal">
      @include('partials.icon', ['name' => 'lock'])
    </div>
    <h1>{{ $capsule->title }}</h1>
    <p class="subtitle">This capsule is sealed until <x-local-time :at="$capsule->open_at" />.</p>
    <p class="countdown">{{ $capsule->countdown() }}</p>
    @if ($isOwner)
    <div class="actions center">
      @include('capsules._delete', ['capsule' => $capsule])
    </div>
    @endif
  </div>
  @else
  <article class="card">
    <span class="badge badge-opened">
      @include('partials.icon', ['name' => 'lock-open'])
      Opened
    </span>
    <h1 style="margin-top:8px">{{ $capsule->title }}</h1>
    <p class="subtitle">Sealed on <x-local-time :at="$capsule->created_at" /> · opened on <x-local-time :at="$capsule->opened_at" /></p>
    <div class="letter">{{ $capsule->message }}</div>

    @if ($capsule->hasFile() && $capsule->isImage())
    <img class="attachment-image" src="/capsules/{{ $capsule->id }}/file" alt="Attachment: {{ $capsule->file_name }}">
    @endif

    @if ($capsule->hasFile() || $isOwner)
    <div class="actions">
      @if ($capsule->hasFile())
      <a class="btn btn-secondary" href="/capsules/{{ $capsule->id }}/file?download=1">
        @include('partials.icon', ['name' => 'download'])
        Download {{ $capsule->file_name }}
      </a>
      @endif
      @if ($isOwner)
      @include('capsules._delete', ['capsule' => $capsule])
      @endif
    </div>
    @endif
  </article>
  @endif
</div>
@endsection
