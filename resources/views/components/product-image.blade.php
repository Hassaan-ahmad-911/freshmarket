@props(['product', 'url' => null, 'eager' => false])
{{-- Product photo with a shimmer skeleton while it loads. Falls back to the category photo, then to a neutral placeholder. --}}
@php
    $src = $url ?? $product->mainImageUrl();
    if (! $src && $product->category && \Illuminate\Support\Facades\Storage::disk('public')->exists("categories/{$product->category->slug}.jpg")) {
        $src = \Illuminate\Support\Facades\Storage::disk('public')->url("categories/{$product->category->slug}.jpg");
    }
@endphp
<div x-data="imageLoader" :class="{ 'is-loaded': loaded }" {{ $attributes->merge(['class' => 'skeleton overflow-hidden']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $product->name }}" data-product-image
             @if (! $eager) loading="lazy" @endif decoding="async"
             class="relative h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.06]">
    @else
        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-50 to-stone-100 text-brand-300">
            <x-icon name="leaf" class="h-10 w-10" />
        </div>
    @endif
</div>
