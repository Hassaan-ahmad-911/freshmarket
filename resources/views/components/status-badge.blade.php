@props(['status'])
{{-- Works with any status enum that has label() and badgeClasses(). --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap '.$status->badgeClasses()]) }}>
    {{ $status->label() }}
</span>
