<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm text-stone-500">Welcome back, {{ strtok(Auth::user()->name, ' ') }}</p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ $supplier->name }}</h1>
            </div>
            <div class="flex items-center gap-3">
                <x-supplier-status-badge :status="$supplier->status" />
                @if ($supplier->isApproved())
                    <a href="{{ route('stores.show', $supplier) }}" class="btn-secondary"><x-icon name="eye" class="h-4 w-4" /> View public store page</a>
                @endif
                <a href="{{ route('supplier.products.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Add product</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8 px-4 pb-12 pt-6 sm:px-6 lg:px-0">
        @if ($supplier->isPending())
            <div class="flex items-start gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800 animate-fade-up">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700"><x-icon name="clock" /></span>
                <p><strong>Your store is waiting for approval.</strong> You can already fill in your store profile and add products —
                    customers will see them as soon as an admin approves your store.</p>
            </div>
        @elseif ($supplier->isSuspended())
            <div class="flex items-start gap-4 rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-800 animate-fade-up">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600"><x-icon name="warning" /></span>
                <p><strong>Your store is suspended</strong> and hidden from customers. Please contact support.</p>
            </div>
        @endif

        {{-- Stat cards (numbers count up from 0) --}}
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4" data-reveal-stagger>
            @foreach ([
                ['Products', $productCount, route('supplier.products.index'), 'cube', 'bg-brand-50 text-brand-700'],
                ['Visible to customers', $activeCount, route('supplier.products.index'), 'eye', 'bg-sky-50 text-sky-700'],
                ['Low on stock', $lowStock->count(), null, 'warning', 'bg-amber-50 text-amber-700'],
                ['Staff members', $staffCount, Auth::user()->can('manageStaff', $supplier) ? route('supplier.staff.index') : null, 'users', 'bg-violet-50 text-violet-700'],
            ] as [$label, $value, $link, $icon, $tone])
                <div data-reveal class="card group relative overflow-hidden p-5 transition duration-300 hover:-translate-y-0.5 hover:shadow-lift">
                    <div class="flex items-start justify-between">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $tone }}"><x-icon :name="$icon" /></span>
                        @if ($link)
                            <a href="{{ $link }}" class="text-xs font-semibold text-stone-400 transition hover:text-brand-700">Manage</a>
                        @endif
                    </div>
                    <p class="mt-5 text-sm text-stone-500">{{ $label }}</p>
                    <p class="mt-1 text-3xl font-extrabold tracking-tight text-ink" x-data="countUp({{ (int) $value }})" x-text="display">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        {{-- Low stock --}}
        <section class="card overflow-hidden" data-reveal>
            <div class="flex items-center justify-between border-b border-stone-100 px-5 py-4">
                <div class="flex items-center gap-2">
                    <x-icon name="warning" class="h-5 w-5 text-amber-500" />
                    <h3 class="font-semibold text-ink">Low-stock alerts</h3>
                </div>
                <a href="{{ route('supplier.products.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">All products</a>
            </div>
            @if ($lowStock->isEmpty())
                <div class="flex items-center gap-3 p-5 text-sm text-stone-500"><x-icon name="check-circle" class="text-brand-600" /> All products are well stocked.</div>
            @else
                <ul class="divide-y divide-stone-100">
                    @foreach ($lowStock as $product)
                        <li class="flex items-center justify-between gap-4 px-5 py-3 text-sm">
                            <span class="flex items-center gap-3">
                                <x-product-image :product="$product" class="h-11 w-11 rounded-lg" />
                                <span class="font-medium text-ink">{{ $product->name }}</span>
                            </span>
                            <span class="flex items-center gap-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $product->stock === 0 ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-700' }}">
                                    {{ $product->stock === 0 ? 'Out of stock' : $product->stock.' left' }}
                                </span>
                                <a href="{{ route('supplier.products.edit', $product) }}" class="btn-secondary px-3 py-1.5 text-xs">Restock</a>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-app-layout>
