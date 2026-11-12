@include('layouts.partials.favicon')
<link rel="stylesheet" href="{{ asset('admin/assets/libs/flaticon/css/all/all.css') }}">
<link rel="stylesheet" href="{{ asset('admin/assets/libs/lucide/lucide.css') }}">
<link rel="stylesheet" href="{{ asset('admin/assets/libs/fontawesome/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('admin/assets/libs/simplebar/simplebar.css') }}">
<link rel="stylesheet" href="{{ asset('admin/assets/libs/node-waves/waves.css') }}">
<link rel="stylesheet" href="{{ asset('admin/assets/libs/bootstrap-select/css/bootstrap-select.min.css') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<link rel="stylesheet" href="{{ asset('admin/assets/css/styles.css') }}?v={{ filemtime(public_path('admin/assets/css/styles.css')) }}">
