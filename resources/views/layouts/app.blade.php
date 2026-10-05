@php
    // Who is looking at the page? (Guest mode: always the built-in guest user.)
    $authEnabled = config('timecapsule.auth_enabled');
    try {
        $currentUser = auth()->user();
    } catch (\Throwable) {
        $currentUser = null; // e.g. the database is down: the error page must still render
    }
    $showFullNav = ! $authEnabled || $currentUser !== null;
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title') · TimeCapsule</title>
  <link rel="stylesheet" href="/vendor/flatpickr/flatpickr.min.css">
  <link rel="stylesheet" href="/css/app.css">
  <script src="/vendor/flatpickr/flatpickr.min.js" defer></script>
  <script src="/js/app.js" defer></script>
</head>
<body>

<header class="site-header">
  <div class="container">
    <a class="logo" href="/">
      @include('partials.icon', ['name' => 'hourglass'])
      TimeCapsule
    </a>
    <nav class="nav" aria-label="Main">
      @if ($showFullNav)
      <a href="/"@if (request()->is('/')) aria-current="page"@endif>My capsules</a>
      @endif
      <a href="/wall"@if (request()->is('wall')) aria-current="page"@endif>Public wall</a>
      @if ($showFullNav)
      <a href="/notifications"@if (request()->is('notifications')) aria-current="page"@endif>Notifications</a>
      @endif
    </nav>
    <div class="user-box">
      @if (! $authEnabled)
      <span>Guest</span>
      @elseif ($currentUser)
      <span>{{ $currentUser->email }}</span>
      <form method="post" action="/logout">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <button type="submit" class="btn-link">
          @include('partials.icon', ['name' => 'log-out'])
          Log out
        </button>
      </form>
      @else
      <a class="btn btn-secondary" href="/login">Log in</a>
      <a class="btn btn-primary" href="/register">Sign up</a>
      @endif
    </div>
  </div>
</header>

<main>
  <div class="container">
    @if (session('success'))
    <div class="flash flash-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
    <div class="flash flash-error" role="alert">{{ session('error') }}</div>
    @endif

    @yield('content')
  </div>
</main>

{{-- Infrastructure info: which machine answered, which database and which file storage it uses. --}}
<footer class="site-footer">
  <div class="container">
    Served by <code>{{ gethostname() }}</code> · DB: <code>{{ config('database.connections.'.config('database.default').'.host') }}</code> · Files: <code>local disk ({{ config('timecapsule.upload_dir') }})</code>
  </div>
</footer>

</body>
</html>
