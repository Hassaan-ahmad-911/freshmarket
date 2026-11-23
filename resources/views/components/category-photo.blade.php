@props(['category'])
{{-- Display-only category photo stored at categories/{slug}.jpg on the public disk (seeded demo photos). --}}
@php($path = "categories/{$category->slug}.jpg")
<div x-data="imageLoader" :class="{ 'is-loaded': loaded }" {{ $attributes->merge(['class' => 'skeleton overflow-hidden']) }}>
    @if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path))
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" alt="" loading="lazy" decoding="async"
             class="h-full w-full object-cover transition duration-700 ease-out group-hover:scale-110">
    @else
        <div class="h-full w-full bg-gradient-to-br from-brand-200 to-brand-500"></div>
    @endif
</div>
