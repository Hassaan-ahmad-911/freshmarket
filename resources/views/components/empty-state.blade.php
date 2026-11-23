@props(['icon' => 'sparkles', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'card flex flex-col items-center px-6 py-14 text-center']) }}>
    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 ring-8 ring-brand-50/50">
        <x-icon :name="$icon" class="h-7 w-7" />
    </span>
    <h3 class="mt-5 text-base font-semibold text-ink">{{ $title }}</h3>
    @if ($text)
        <p class="mt-1 max-w-sm text-sm text-stone-500">{{ $text }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
