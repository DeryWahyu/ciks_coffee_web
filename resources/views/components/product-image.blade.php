@props(['product', 'sizes' => '(max-width: 640px) 50vw, 320px'])
@php $sources = app(\App\Services\ImageVariants::class)->productSources($product->image); @endphp
<img src="{{ $sources['src'] }}" @if($sources['srcset']) srcset="{{ $sources['srcset'] }}" sizes="{{ $sizes }}" @endif
    alt="{{ $product->name }}" loading="lazy" decoding="async" {{ $attributes }}>
