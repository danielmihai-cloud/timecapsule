{{-- 419 = the CSRF token of the form is missing or expired (e.g. the session ended). --}}
@extends('errors.page', ['code' => 419, 'heading' => 'Access denied', 'text' => 'Your session expired. Please reload the page.'])
