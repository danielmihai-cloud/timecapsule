<form method="post" action="/capsules/{{ $capsule->id }}/delete" onsubmit="return confirm('Delete this capsule forever?')">
  <input type="hidden" name="_token" value="{{ csrf_token() }}">
  <button type="submit" class="btn btn-danger">
    @include('partials.icon', ['name' => 'trash'])
    Delete
  </button>
</form>
