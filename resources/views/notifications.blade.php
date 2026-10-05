@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="page-head">
  <div>
    <h1>Notifications</h1>
    <p class="subtitle">Emails the app would have sent. Nothing is delivered yet.</p>
  </div>
</div>

@if ($notifications->isEmpty())
<div class="card empty">
  @include('partials.icon', ['name' => 'bell'])
  <h2>No notifications yet</h2>
  <p>They appear when you create a capsule and when it opens.</p>
</div>
@else
<div class="card table-card">
  <table>
    <thead>
      <tr><th scope="col">Date</th><th scope="col">To</th><th scope="col">Message</th></tr>
    </thead>
    <tbody>
      @foreach ($notifications as $notification)
      <tr>
        <td class="nowrap"><x-local-time :at="$notification->created_at" /></td>
        <td>{{ $notification->recipient }}</td>
        <td>{{ $notification->message }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endif
@endsection
