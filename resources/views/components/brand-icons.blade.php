@php
    $iconVersion = substr(hash_file('sha256', public_path('images/branding/cikscoffee-favicon-64.png')), 0, 12);
@endphp
<link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ $iconVersion }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/cikscoffee-favicon-32.png') }}?v={{ $iconVersion }}">
<link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/branding/cikscoffee-favicon-64.png') }}?v={{ $iconVersion }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/branding/cikscoffee-apple-touch-icon.png') }}?v={{ $iconVersion }}">
