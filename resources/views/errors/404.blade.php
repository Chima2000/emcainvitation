@php
    $backUrl = url()->previous(route('home'));
@endphp

@extends('errors.layout', [
    'code' => 404,
    'icon' => 'fa-regular fa-compass',
    'photo' => 'page-titles/upcoming.jpg',
    'heading' => __('Page not found'),
    'subtitle' => __('This page is not on the guest list'),
    'text' => __('The link may be old or mistyped, or the page has moved. Try one of the pages below or go back to the home page.'),
])

@section('actions')
    <x-site-button :href="route('home')">{{ __('Back to home') }}</x-site-button>
    @if (! in_array($backUrl, [url()->full(), route('home')], true))
        <a href="{{ $backUrl }}" class="theme-btn btn-style-two emca-btn-outline">{{ __('Go back') }}</a>
    @endif
@endsection
