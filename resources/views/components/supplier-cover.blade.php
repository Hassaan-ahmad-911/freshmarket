@props(['supplier'])
{{-- Display-only store cover photo at suppliers/covers/{slug}.jpg (seeded demo photos); green gradient otherwise. --}}
@php($path = "suppliers/covers/{$supplier->slug}.jpg")
<div x-data="imageLoader" :class="{ 'is-loaded': loaded }" {{ $attributes->merge(['class' => 'skeleton overflow-hidden']) }}>
    @if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path))
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" alt="" loading="lazy" decoding="async"
             class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-105">
    @else
        <div class="h-full w-full bg-gradient-to-br from-brand-500 via-brand-600 to-brand-800"></div>
    @endif
</div>
