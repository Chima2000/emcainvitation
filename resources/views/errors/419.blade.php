@extends('errors.layout', [
    'code' => 419,
    'icon' => 'fa-regular fa-hourglass-end',
    'photo' => 'page-titles/privacy.jpg',
    'heading' => __('Page expired'),
    'subtitle' => __('This page waited too long'),
    'text' => __('For your security, forms expire when a page stays open for a long time. Refresh the page, then fill in the form and send it again.'),
])

@section('actions')
    <x-site-button :href="url()->previous(route('home'))">{{ __('Refresh and try again') }}</x-site-button>
    @guest
        <a href="{{ route('login') }}" class="theme-btn btn-style-two emca-btn-outline">{{ __('Sign in') }}</a>
    @else
        <a href="{{ route('home') }}" class="theme-btn btn-style-two emca-btn-outline">{{ __('Back to home') }}</a>
    @endguest
@endsection
