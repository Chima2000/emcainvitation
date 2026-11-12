@extends('errors.layout', [
    'code' => $code ?? 408,
    'icon' => 'fa-regular fa-clock',
    'photo' => 'page-titles/pricing.jpg',
    'heading' => __('That took too long'),
    'subtitle' => __('The server did not answer in time'),
    'text' => __('Your request may still have gone through. Check before you try again so nothing is saved or sent twice.'),
])

@section('actions')
    <x-site-button :href="request()->isMethod('GET') ? url()->full() : url()->previous(route('home'))">{{ __('Try again') }}</x-site-button>
    <a href="{{ route('home') }}" class="theme-btn btn-style-two emca-btn-outline">{{ __('Back to home') }}</a>
@endsection
