{{-- One capsule card, used on "My capsules" and on the public wall ($showAuthor = true). --}}
<a class="card capsule-card{{ $capsule->isOpened() ? ' is-opened' : '' }}" href="/capsules/{{ $capsule->id }}">
  @if ($capsule->isOpened())
  <span class="badge badge-opened">
    @include('partials.icon', ['name' => 'lock-open'])
    Opened
  </span>
  @else
  <span class="badge badge-sealed">
    @include('partials.icon', ['name' => 'lock'])
    Sealed
  </span>
  @endif
  <h2>{{ $capsule->title }}</h2>
  @if ($capsule->isOpened())
  <p class="excerpt">{{ $capsule->excerpt() }}</p>
  @endif
  <div class="capsule-meta">
    <span>
      @include('partials.icon', ['name' => 'calendar'])
      <x-local-time :at="$capsule->open_at" />
    </span>
    @unless ($capsule->isOpened())
    <span>{{ $capsule->countdown() }}</span>
    @endunless
    @if ($capsule->hasFile())
    <span>
      @include('partials.icon', ['name' => 'paperclip'])
      1 file
    </span>
    @endif
    @if ($showAuthor ?? false)
    <span>by {{ $capsule->user->displayName() }}</span>
    @endif
  </div>
</a>
