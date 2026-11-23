<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <x-supplier-logo :supplier="$supplier" class="h-10 w-10 text-sm" />
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $supplier->name }}</h2>
                <x-supplier-status-badge :status="$supplier->status" />
            </div>
            @if ($supplier->isApproved())
                <a href="{{ route('stores.show', $supplier) }}" class="text-sm text-emerald-700 hover:underline">View public store page &rarr;</a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($supplier->isPending())
                <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                    <strong>Your store is waiting for approval.</strong> You can already fill in your store profile and add products —
                    customers will see them as soon as an admin approves your store.
                </div>
            @elseif ($supplier->isSuspended())
                <div class="rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-800">
                    <strong>Your store is suspended</strong> and hidden from customers. Please contact support.
                </div>
            @endif

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ([
                    ['Products', $productCount, route('supplier.products.index')],
                    ['Visible to customers', $activeCount, route('supplier.products.index')],
                    ['Low on stock', $lowStock->count(), null],
                    ['Staff members', $staffCount, Auth::user()->can('manageStaff', $supplier) ? route('supplier.staff.index') : null],
                ] as [$label, $value, $link])
                    <div class="bg-white rounded-lg shadow-sm p-5">
                        <p class="text-sm text-gray-500">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-bold">{{ $value }}</p>
                        @if ($link)<a href="{{ $link }}" class="mt-1 inline-block text-xs text-emerald-700 hover:underline">Manage</a>@endif
                    </div>
                @endforeach
            </div>

            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold">Low-stock alerts</h3>
                    <a href="{{ route('supplier.products.create') }}" class="text-sm text-emerald-700 hover:underline">+ Add product</a>
                </div>
                @if ($lowStock->isEmpty())
                    <p class="p-5 text-sm text-gray-500">All products are well stocked.</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($lowStock as $product)
                            <li class="px-5 py-3 flex items-center justify-between text-sm">
                                <span>{{ $product->name }}</span>
                                <span class="flex items-center gap-4">
                                    <span class="{{ $product->stock === 0 ? 'text-red-600' : 'text-amber-600' }} font-medium">
                                        {{ $product->stock === 0 ? 'Out of stock' : $product->stock.' left' }}
                                    </span>
                                    <a href="{{ route('supplier.products.edit', $product) }}" class="text-emerald-700 hover:underline">Restock</a>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
