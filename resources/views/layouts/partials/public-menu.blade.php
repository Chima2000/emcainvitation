<li @class(['current' => request()->routeIs('home')])><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
<li @class(['current' => request()->routeIs('features')])><a href="{{ route('features') }}">{{ __('Features') }}</a></li>
<li @class(['current' => request()->routeIs('templates.index')])><a href="{{ route('templates.index') }}">{{ __('Templates') }}</a></li>
<li @class(['current' => request()->routeIs('events.upcoming')])><a href="{{ route('events.upcoming') }}">{{ __('Events') }}</a></li>
<li @class(['current' => request()->routeIs('pricing')])><a href="{{ route('pricing') }}">{{ __('Pricing') }}</a></li>
<li><a href="{{ route('home') }}#demo">{{ __('Book a demo') }}</a></li>
@auth
    <li><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
@endauth
