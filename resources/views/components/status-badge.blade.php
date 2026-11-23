@props(['status'])
{{-- Works with any status enum that has label() and badgeClasses(). In-progress states get a softly pulsing dot. --}}
@php($live = in_array($status->value, ['pending_payment', 'pending', 'accepted', 'preparing', 'dispatched', 'blocked'], true))
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap ring-1 ring-inset ring-black/5 transition-colors duration-500 '.$status->badgeClasses()]) }}>
    <span class="relative flex h-1.5 w-1.5">
        @if ($live)
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-current opacity-60"></span>
        @endif
        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-current"></span>
    </span>
    {{ $status->label() }}
</span>
