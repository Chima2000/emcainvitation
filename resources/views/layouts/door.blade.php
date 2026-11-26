<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light" data-admin-theme="emca" data-color-theme="emca">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', __('Door')) | {{ config('app.name') }}</title>
        @include('layouts.partials.admin-head')
        <link rel="stylesheet" href="{{ asset('admin/assets/css/emca-admin.css') }}?v={{ filemtime(public_path('admin/assets/css/emca-admin.css')) }}">
        <style>
            html,
            body {
                min-height: 100%;
                background: #1f1a1e;
            }

            .jumuika-auth-wall {
                position: fixed;
                inset: -28px;
                z-index: 0;
                display: grid;
                grid-template-columns: repeat(5, minmax(0, 1fr));
                grid-auto-rows: 210px;
                align-content: start;
                gap: 0;
                pointer-events: none;
                animation: jumuika-wall-drift 60s linear infinite alternate;
            }

            .jumuika-auth-wall img {
                display: block;
                width: 100%;
                height: 100%;
                object-fit: cover;
                object-position: center 30%;
            }

            .jumuika-auth-shade {
                position: fixed;
                inset: 0;
                z-index: 1;
                background: radial-gradient(circle at center, rgba(12, 10, 14, 0.32) 0, rgba(12, 10, 14, 0.42) 55%, rgba(12, 10, 14, 0.58) 100%);
                pointer-events: none;
            }

            .door-shell {
                position: relative;
                z-index: 2;
            }

            .door-shell .row > div {
                flex: 0 1 760px;
                width: 100%;
                max-width: 760px;
            }

            .door-shell .card {
                border: 0;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
            }

            @keyframes jumuika-wall-drift {
                from {
                    transform: translateY(0);
                }

                to {
                    transform: translateY(-120px);
                }
            }

            @media (max-width: 991.98px) {
                .jumuika-auth-wall {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                    grid-auto-rows: 170px;
                }
            }

            @media (max-width: 575.98px) {
                .jumuika-auth-wall {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    grid-auto-rows: 150px;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .jumuika-auth-wall {
                    animation: none;
                }
            }
        </style>
    </head>
    <body>
        @include('layouts.partials.back-to-top')
        @php
            $wallPhotos = collect([
                'images/home/occasion-wedding.jpg',
                'images/auth/login-2.jpg',
                'images/occasions/celebration.jpg',
                'images/home/hero.jpg',
                'images/auth/login-15.jpg',
                'images/home/upcoming.jpg',
                'images/features/intro.jpg',
                'images/auth/login-6.jpg',
                'images/occasions/wedding.jpg',
                'images/home/occasion-celebration.jpg',
                'images/auth/login-9.jpg',
                'images/features/pledges.jpg',
                'images/home/occasion-sendoff.jpg',
            ]);
            $wallPhotos = $wallPhotos->concat($wallPhotos)->concat($wallPhotos)->take(35);
        @endphp
        <div class="jumuika-auth-wall" aria-hidden="true">
            @foreach ($wallPhotos as $wallPhoto)
                <img src="{{ asset($wallPhoto) }}" alt="" loading="{{ $loop->index < 10 ? 'eager' : 'lazy' }}" decoding="async">
            @endforeach
        </div>
        <div class="jumuika-auth-shade" aria-hidden="true"></div>
        <main class="container py-5 door-shell">
            <div class="row justify-content-center">
                <div class="col-lg-5">
                    @yield('content')
                </div>
            </div>
        </main>
        <script src="{{ asset('admin/assets/js/emca-back-to-top.js') }}?v={{ filemtime(public_path('admin/assets/js/emca-back-to-top.js')) }}"></script>
    </body>
</html>
