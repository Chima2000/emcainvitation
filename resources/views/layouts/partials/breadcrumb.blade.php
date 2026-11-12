<section class="page-title" style="background-image: url({{ $photoUrl ?? asset('images/'.($photo ?? 'page-titles/features.jpg')) }});">
    <div class="auto-container">
        <div class="title-outer text-center">
            <h1 class="h1 title">{{ $title }}</h1>
            @isset($lead)
                <p class="text mx-auto">{{ $lead }}</p>
            @endisset
            <ul class="page-breadcrumb">
                <li><a href="{{ route('home') }}">{{ __('Home') }}</a></li>
                <li>{{ $title }}</li>
            </ul>
        </div>
    </div>
</section>
