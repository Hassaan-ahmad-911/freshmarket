{{-- Supplier area sidebar (lg and up). Expects $links ([label, href, routePattern, icon]) and $supplier. --}}
<aside class="hidden w-64 shrink-0 lg:block">
    <div class="sticky top-24 space-y-6 py-8">
        <a href="{{ $supplier->isApproved() ? route('stores.show', $supplier) : route('supplier.dashboard') }}"
           class="card group flex items-center gap-3 p-3 transition hover:border-brand-200">
            <x-supplier-logo :supplier="$supplier" class="h-11 w-11 text-sm shadow-soft" />
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-ink group-hover:text-brand-700">{{ $supplier->name }}</p>
                <x-status-badge :status="$supplier->status" class="mt-1 py-0.5" />
            </div>
        </a>

        <nav class="space-y-1" aria-label="Store">
            @foreach ($links as [$label, $href, $pattern, $icon])
                @php($active = request()->routeIs($pattern))
                <a href="{{ $href }}" @class([
                    'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                    'bg-brand-600 text-white shadow-glow' => $active,
                    'text-stone-600 hover:bg-white hover:text-ink hover:shadow-soft' => ! $active,
                ])>
                    <x-icon :name="$icon" class="{{ $active ? 'h-5 w-5 text-white' : 'h-5 w-5 text-stone-400 group-hover:text-brand-600' }}" />
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <a href="{{ route('products.index') }}" class="flex items-center gap-2 px-3 text-sm text-stone-500 hover:text-brand-700">
            <x-icon name="eye" class="h-4 w-4" /> View the marketplace
        </a>
    </div>
</aside>
