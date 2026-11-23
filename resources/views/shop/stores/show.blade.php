<x-shop-layout :title="$supplier->name">
    <section class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row gap-6 sm:items-center">
            <x-supplier-logo :supplier="$supplier" class="h-20 w-20 text-2xl shrink-0" />
            <div class="flex-1">
                <h1 class="text-2xl font-bold">{{ $supplier->name }}</h1>
                @if ($supplier->description)
                    <p class="mt-1 text-gray-600 max-w-2xl">{{ $supplier->description }}</p>
                @endif
                <div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500">
                    <span>Minimum order: <strong class="text-gray-700">{{ $supplier->min_order_amount->isZero() ? 'none' : $supplier->min_order_amount }}</strong></span>
                    @if ($supplier->deliveryAreas->isNotEmpty())
                        <span>Delivers to: <strong class="text-gray-700">{{ $supplier->deliveryAreas->pluck('name')->join(', ') }}</strong></span>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-4 gap-6">
        <aside>
            @include('shop.partials.filters', ['action' => route('stores.show', $supplier), 'hideArea' => true])
        </aside>
        <section class="lg:col-span-3">
            @if ($products->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center text-gray-500">No products found.</div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
                <div class="mt-8">{{ $products->links() }}</div>
            @endif
        </section>
    </div>
</x-shop-layout>
