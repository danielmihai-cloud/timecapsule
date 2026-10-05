@extends('layouts.app')

@section('title', 'New capsule')

@php
    // $values = what the user typed (kept after a validation error).
    // $errors = one message per field (see app/Http/Requests/StoreCapsuleRequest.php).
    $old = fn (string $field) => $values[$field] ?? '';
@endphp

@section('content')
<div class="narrow">
  <a class="back-link" href="/">
    @include('partials.icon', ['name' => 'arrow-left'])
    Back to my capsules
  </a>
  <div class="page-head">
    <h1>New capsule</h1>
  </div>
  <form class="card" method="post" action="/capsules" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_token" value="{{ csrf_token() }}">

    <div class="field{{ $errors->has('title') ? ' has-error' : '' }}">
      <label for="title">Title</label>
      <input type="text" id="title" name="title" maxlength="120" required value="{{ $old('title') }}"@if ($errors->has('title')) aria-invalid="true" aria-describedby="title-error"@endif>
      @if ($errors->has('title'))
      <span class="error" id="title-error">{{ $errors->first('title') }}</span>
      @endif
    </div>

    <div class="field{{ $errors->has('message') ? ' has-error' : '' }}">
      <label for="message">Message</label>
      <textarea id="message" name="message" maxlength="5000" required @if ($errors->has('message'))aria-invalid="true" aria-describedby="message-error"@endif>{{ $old('message') }}</textarea>
      @if ($errors->has('message'))
      <span class="error" id="message-error">{{ $errors->first('message') }}</span>
      @endif
    </div>

    {{-- "open_at" = local time "YYYY-MM-DDTHH:MM" (flatpickr or the browser's own field);
         "open_at_utc" is filled by public/js/app.js on submit (the same moment in UTC). --}}
    <div class="field{{ $errors->has('open_at') ? ' has-error' : '' }}">
      <label for="open_at">Open at</label>
      <input type="datetime-local" id="open_at" name="open_at" required value="{{ $old('open_at') }}" data-datetime-picker aria-describedby="open_at-hint{{ $errors->has('open_at') ? ' open_at-error' : '' }}"@if ($errors->has('open_at')) aria-invalid="true"@endif>
      <input type="hidden" name="open_at_utc" value="">
      <span class="hint" id="open_at-hint">Date and time in your time zone.</span>
      @if ($errors->has('open_at'))
      <span class="error" id="open_at-error">{{ $errors->first('open_at') }}</span>
      @endif
    </div>

    <div class="field{{ $errors->has('recipient_email') ? ' has-error' : '' }}">
      <label for="recipient_email">Recipient email <span class="optional">(optional)</span></label>
      <input type="email" id="recipient_email" name="recipient_email" value="{{ $old('recipient_email') }}"@if ($errors->has('recipient_email')) aria-invalid="true" aria-describedby="recipient_email-error recipient_email-hint"@else aria-describedby="recipient_email-hint"@endif>
      @if ($errors->has('recipient_email'))
      <span class="error" id="recipient_email-error">{{ $errors->first('recipient_email') }}</span>
      @endif
      <span class="hint" id="recipient_email-hint">We will notify this address when the capsule opens. Leave empty to notify yourself.</span>
    </div>

    <div class="field{{ $errors->has('attachment') ? ' has-error' : '' }}">
      <label for="attachment">Attachment <span class="optional">(optional)</span></label>
      <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf"@if ($errors->has('attachment')) aria-invalid="true" aria-describedby="attachment-error attachment-hint"@else aria-describedby="attachment-hint"@endif>
      @if ($errors->has('attachment'))
      <span class="error" id="attachment-error">{{ $errors->first('attachment') }}</span>
      @endif
      <span class="hint" id="attachment-hint">JPG, PNG, GIF, WEBP or PDF, up to {{ config('timecapsule.max_upload_mb') }} MB.</span>
    </div>

    <div class="field">
      <label class="checkbox">
        <input type="checkbox" name="is_public" value="1"@if ($old('is_public')) checked @endif>
        Show on the public wall after opening
      </label>
    </div>

    <button type="submit" class="btn btn-primary btn-block">
      @include('partials.icon', ['name' => 'lock'])
      Seal capsule
    </button>
  </form>
</div>
@endsection
