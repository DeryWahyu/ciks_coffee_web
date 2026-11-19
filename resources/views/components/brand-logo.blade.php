@props(['light' => false, 'inline' => false, 'wordmark' => false])

@php
    $logo = $wordmark ? 'images/branding/cikscoffee-wordmark.png' : 'images/branding/cikscoffee-c-mark.png';
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
    'width' => $wordmark ? '96' : '40',
    'height' => '40',
    'decoding' => 'async',
]) }}>
