@props(['product', 'url' => null])
{{-- The product's main photo, or a soft placeholder with the category emoji. --}}
@php($src = $url ?? $product->mainImageUrl())
@if ($src)
    <img src="{{ $src }}" alt="{{ $product->name }}" loading="lazy" {{ $attributes->merge(['class' => 'object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'flex items-center justify-center bg-gradient-to-br from-emerald-50 to-lime-100']) }}>
        <span class="text-4xl" aria-hidden="true">{{ $product->category?->icon ?? '🧺' }}</span>
    </div>
@endif
