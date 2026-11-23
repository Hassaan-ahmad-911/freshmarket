@props(['supplier'])
@if ($supplier->logoUrl())
    <img src="{{ $supplier->logoUrl() }}" alt="{{ $supplier->name }} logo" {{ $attributes->merge(['class' => 'rounded-full object-cover bg-white']) }}>
@else
    <div {{ $attributes->merge(['class' => 'rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center']) }}>
        {{ $supplier->initials() }}
    </div>
@endif
