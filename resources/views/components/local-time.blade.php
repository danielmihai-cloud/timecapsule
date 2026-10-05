{{-- A moment shown in UTC; public/js/app.js rewrites it in the visitor's time zone. Usage: <x-local-time :at="$capsule->open_at" /> (Keep this file without a final newline, or a space appears before a following ".".) --}}
@props(['at'])
@php($utc = $at->copy()->utc())
<time data-local datetime="{{ $utc->format('Y-m-d\TH:i:s\Z') }}">{{ $utc->format('j M Y, H:i') }} UTC</time>