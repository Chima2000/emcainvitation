<div class="flex flex-col gap-3 md:flex-row md:items-center">
    <a href="{{ route('events.upcoming') }}" class="hover:text-forest-800">{{ __('Events') }}</a>
    <a href="{{ route('templates.index') }}" class="hover:text-forest-800">{{ __('Templates') }}</a>
    <a href="{{ route('features') }}" class="hover:text-forest-800">{{ __('Features') }}</a>
    <a href="{{ route('pricing') }}" class="hover:text-forest-800">{{ __('Pricing') }}</a>
    <a href="{{ route('home') }}#demo" class="hover:text-forest-800">{{ __('Book a demo') }}</a>
    @auth
        <a href="{{ route('dashboard') }}" class="rounded-full bg-forest-700 px-4 py-2 text-center text-white">{{ __('Dashboard') }}</a>
    @else
        <a href="{{ route('login') }}" class="hover:text-forest-800">{{ __('Sign in') }}</a>
        <a href="{{ route('register') }}" class="rounded-full bg-forest-700 px-4 py-2 text-center text-white">{{ __('Launch platform') }}</a>
    @endauth
</div>
