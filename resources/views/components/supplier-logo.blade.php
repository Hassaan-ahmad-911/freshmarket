@props(['supplier'])
@if ($supplier->logoUrl())
    <img src="{{ $supplier->logoUrl() }}" alt="{{ $supplier->name }} logo" loading="lazy"
         {{ $attributes->merge(['class' => 'rounded-full object-cover bg-white ring-2 ring-white']) }}>
@else
    <div {{ $attributes->merge(['class' => 'rounded-full bg-brand-100 text-brand-700 font-bold flex items-center justify-center ring-2 ring-white']) }}>
        {{ $supplier->initials() }}
    </div>
@endif
