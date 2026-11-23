@props(['product', 'size' => 'sm'])
{{--
    Display-only demo rating. FreshMarket has no review system yet, so this is a stable
    placeholder derived from the product (same value on every page load), labelled as such.
--}}
@php
    $seed = crc32($product->slug ?? (string) $product->id);
    $rating = 4.3 + ($seed % 7) / 10;      // 4.3 – 4.9
    $count = 18 + ($seed % 160);
@endphp
<div {{ $attributes->merge(['class' => 'flex items-center gap-1 text-xs text-stone-500']) }} title="Demo rating — reviews aren't built yet">
    <x-icon name="star" solid class="{{ $size === 'lg' ? 'h-5 w-5' : 'h-3.5 w-3.5' }} text-amber-400" />
    <span class="font-semibold text-stone-700">{{ number_format($rating, 1) }}</span>
    <span>({{ $count }})</span>
</div>
