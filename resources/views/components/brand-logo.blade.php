@props(['light' => false, 'inline' => false])

@php
    $logo = 'images/cikscoffee-aroma.png';
    $source = $inline
        ? 'data:image/png;base64,'.base64_encode(file_get_contents(public_path($logo)))
        : asset($logo);
@endphp

<img {{ $attributes->class([
    'inline-block shrink-0 object-contain',
    'brightness-0 invert' => $light,
])->merge([
    'src' => $source,
    'alt' => 'Logo CiksCoffee',
    'width' => '40',
    'height' => '40',
    'decoding' => 'async',
]) }}>
